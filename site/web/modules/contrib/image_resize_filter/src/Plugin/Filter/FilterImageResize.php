<?php

namespace Drupal\image_resize_filter\Plugin\Filter;

use Drupal\Core\Config\Config;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Image\ImageFactory;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a filter to resize images.
 *
 * @Filter(
 *   id = "filter_image_resize",
 *   title = @Translation("Image Resize Filter: Resize images based on their given height and width attributes"),
 *   description = @Translation("Analyze the &lt;img&gt; tags and compare the given height and width attributes to the actual file. If the file dimensions are different than those given in the &lt;img&gt; tag, the image will be copied and the src attribute will be updated to point to the resized image."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE,
 *   settings = {
 *     "image_locations" = {
 *        "local" = 1,
 *        "remote" = 0
 *   },
 *   }
 * )
 */
class FilterImageResize extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * ImageFactory instance.
   *
   * @var \Drupal\Core\Image\ImageFactory
   */
  protected $imageFactory;

  /**
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $systemFileConfig;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * FilterImageResize constructor.
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity Repository.
   * @param \Drupal\Core\Image\ImageFactory $image_factory
   *   Image Factory.
   * @param \Drupal\Core\Config\Config $system_file_config
   *   The system file configuration.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    ImageFactory $image_factory,
    Config $system_file_config,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    FileSystemInterface $file_system) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->imageFactory = $image_factory;
    $this->systemFileConfig = $system_file_config;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('image.factory'),
      $container->get('config.factory')->get('system.file'),
      $container->get('stream_wrapper_manager'),
      $container->get('file_system')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    return new FilterProcessResult($this->getImages($text));
  }

  /**
   * Locate all images in a piece of text that need replacing.
   *
   *   An array of settings that will be used to identify which images need
   *   updating. Includes the following:
   *
   *   - image_locations: An array of acceptable image locations.
   *     of the following values: "remote". Remote image will be downloaded and
   *     saved locally. This procedure is intensive as the images need to
   *     be retrieved to have their dimensions checked.
   *
   * @param string $text
   *   The text to be updated with the new img src tags.
   *
   * @return string $images
   *   An list of images.
   */
  private function getImages($text) {
    $images = image_resize_filter_get_images($this->settings, $text);

    $search = [];
    $replace = [];

    foreach ($images as $image) {
      // Copy remote images locally.
      if ($image['location'] == 'remote') {
        $local_file_path = 'resize/remote/' . md5(file_get_contents($image['local_path'])) . '-' . $image['expected_size']['width'] . 'x' . $image['expected_size']['height'] . '.'. $image['extension'];
        // Once Drupal 8.7.x is unsupported remove this IF statement.
        if (floatval(\Drupal::VERSION) >= 8.8) {
          $image['destination'] = $this->systemFileConfig->get('default_scheme') . '://' . $local_file_path;
        }
        else {
          $image['destination'] = file_default_scheme() . '://' . $local_file_path;
        }
      }
      // Destination and local path are the same if we're just adding attributes.
      elseif (!$image['resize']) {
        $image['destination'] = $image['local_path'];
      }
      else {
        $path_info = image_resize_filter_pathinfo($image['local_path']);
        // Once Drupal 8.7.x is unsupported remove this IF statement.
        if (floatval(\Drupal::VERSION) >= 8.8) {
          $local_file_dir = $this->streamWrapperManager->getTarget($path_info['dirname']);
        }
        else {
          $local_file_dir = file_uri_target($path_info['dirname']);
        }
        $local_file_path = 'resize/' . ($local_file_dir ? $local_file_dir . '/' : '') . $path_info['filename'] . '-' . $image['expected_size']['width'] . 'x' . $image['expected_size']['height'] . '.' . $path_info['extension'];
        $image['destination'] = $path_info['scheme'] . '://' . $local_file_path;
      }

      if (!file_exists($image['destination'])) {
        // Basic flood prevention of resizing.
        $resize_threshold = 10;
        $flood = \Drupal::flood();
        if (!$flood->isAllowed('image_resize_filter_resize', $resize_threshold, 120)) {
          $this->messenger->addMessage(t('Image resize threshold of @count per minute reached. Some images have not been resized. Resave the content to resize remaining images.', ['@count' => floor($resize_threshold / 2)]), 'error', FALSE);
          continue;
        }
        $flood->register('image_resize_filter_resize', 120);

        // Create the resize directory.
        $directory = dirname($image['destination']);
        $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

        // Move remote images into place if they are already the right size.
        if ($image['location'] == 'remote' && !$image['resize']) {
          $handle = fopen($image['destination'], 'w');
          fwrite($handle, file_get_contents($image['local_path']));
          fclose($handle);
        }
        // Resize the local image if the sizes don't match.
        elseif ($image['resize']) {
          $copy = $this->fileSystem->copy($image['local_path'], $image['destination']);
          $res = $this->imageFactory->get($copy);
          if ($res) {
            // Image loaded successfully; resize.
            $res->resize($image['expected_size']['width'], $image['expected_size']['height']);
            $res->save();
          }
          else {
            // Image failed to load - type doesn't match extension or invalid; keep original file
            $handle = fopen($image['destination'], 'w');
            fwrite($handle, file_get_contents($image['local_path']));
            fclose($handle);
          }
        }
        @chmod($image['destination'], 0664);
      }

      // Delete our temporary file if this is a remote image.
      image_resize_filter_delete_temp_file($image['location'], $image['local_path']);

      // Replace the existing image source with the resized image.
      // Set the image we're currently updating in the callback function.
      $search[] = $image['img_tag'];
      $replace[] = image_resize_filter_image_tag($image, $this->settings);
    }

    return str_replace($search, $replace, $text);
  }

  /**
   * {@inheritdoc}
   */
  function settingsForm(array $form, FormStateInterface $form_state) {
    $settings['image_locations'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Resize images stored'),
      '#options' => [
        'local' => $this->t('Locally'),
        'remote' => $this->t('On remote servers (note: this copies <em>all</em> remote images locally)')
      ],
      '#default_value' => array_keys(array_filter($this->settings['image_locations'])),
      '#description' => $this->t('This option will determine which images will be analyzed for &lt;img&gt; tag differences. Enabling resizing of remote images can have performance impacts, as all images in the filtered text needs to be transferred via HTTP each time the filter cache is cleared.'),
    ];

    return $settings;
  }

}
