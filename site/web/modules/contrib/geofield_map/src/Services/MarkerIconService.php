<?php

namespace Drupal\geofield_map\Services;

use Drupal;
use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\file\FileInterface;
use Drupal\file\Entity\File;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Yaml\Yaml;
use Drupal\Core\Url;
use Drupal\Core\Config\Config;
use Drupal\image\Entity\ImageStyle;
use Drupal\Core\Entity\EntityStorageException;
use Symfony\Component\Yaml\Exception\ParseException;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\File\Exception\NotRegularDirectoryException;

/**
 * Provides an Icon Managed File Service.
 */
class MarkerIconService {

  use StringTranslationTrait;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The translation manager.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $translationManager;

  /**
   * The Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityManager;

  /**
   * The module handler to invoke the alter hook.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The geofield map settings.
   *
   * @var array
   */
  protected $geofieldMapSettings;

  /**
   * The list of file upload validators.
   *
   * @var array
   */
  protected $fileUploadValidators;

  /**
   * The Default Icon Element.
   *
   * @var array
   */
  protected $defaultIconElement;

  /**
   * The Link Generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * A element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

  /**
   * The List of Markers Files.
   *
   * @var array
   */
  protected $markersFilesList = [];

  /**
   * The string containing the allowed file/image extensions.
   *
   * @var array
   */
  protected $allowedExtension;

  /**
   * The File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Set Geofield Map Default Icon Style.
   */
  protected function setDefaultIconStyle() {
    $image_style_path = $this->extensionPathResolver->getPath('module', 'geofield_map') . '/config/optional/image.style.geofield_map_default_icon_style.yml';
    $image_style_data = Yaml::parse(file_get_contents($image_style_path));
    $geofield_map_default_icon_style = $this->config->getEditable('image.style.geofield_map_default_icon_style');
    if ($geofield_map_default_icon_style instanceof Config) {
      $geofield_map_default_icon_style->setData($image_style_data)->save(TRUE);
    }
  }

  /**
   * Generate File Managed Url from fid, and image style.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file tp check.
   *
   * @return bool
   *   The bool result.
   */
  protected function fileIsManageableSvg(FileInterface $file): bool {
    return $this->moduleHandler->moduleExists('svg_image') && svg_image_is_file_svg($file);
  }

  /**
   * Returns the Markers Location Uri.
   *
   * @return string
   *   The markers' location uri.
   */
  protected function markersLocationUri(): string {
    return !empty($this->geofieldMapSettings->get('theming.markers_location.security') . $this->geofieldMapSettings->get('theming.markers_location.rel_path')) ? $this->geofieldMapSettings->get('theming.markers_location.security') . $this->geofieldMapSettings->get('theming.markers_location.rel_path') : 'public://geofieldmap_markers';
  }

  /**
   * Generates alphabetically ordered Markers Files/Icons list.
   *
   * @return array
   *   The Markers File/Icons list.
   */
  protected function setMarkersFilesList(): array {
    $markers_files_list = [];
    $regex = '/\.(' . preg_replace('/ +/', '|', preg_quote($this->allowedExtension)) . ')$/i';
    $security = $this->geofieldMapSettings->get('theming.markers_location.security');
    $rel_path = $this->geofieldMapSettings->get('theming.markers_location.rel_path');
    try {
      $files = $this->fileSystem->scanDirectory($security . $rel_path, $regex);
      $additional_markers_location = $this->geofieldMapSettings->get('theming.additional_markers_location');
      if (!empty($additional_markers_location)) {
        $additional_files = $this->fileSystem->scanDirectory($additional_markers_location, $regex);
        $files = array_merge($files, $additional_files);
      }
      ksort($files, SORT_STRING);
      foreach ($files as $k => $file) {
        $markers_files_list[$k] = $file->filename;
      }
    }
    catch (NotRegularDirectoryException $e) {
      // Theming.markers_location folder path.
      $theming_folder = $security . $rel_path;
      // Try to generate the theming.markers_location folder,
      // otherwise logs a warning.
      if (!$this->fileSystem->mkdir($theming_folder)) {
        $this->logger->warning($this->t("The '@folder' folder couldn't be created", [
          '@folder' => $theming_folder,
        ]));
      }
    }

    return $markers_files_list;
  }

  /**
   * Creates an absolute web-accessible URL string.
   *
   * @todo switch to this same method of the @file_url_generator Drupal Core
   *   (since 9.3+) service once we fork on a branch not supporting 8.x anymore.
   *
   * @param string $uri
   *   The URI to a file for which we need an external URL, or the path to a
   *   shipped file.
   * @param bool $relative
   *   Whether to return a relative or absolute URL.
   *
   * @return string
   *   An absolute string containing a URL that may be used to access the
   *   file.
   *
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   If a stream wrapper could not be found to generate an external URL.
   */
  protected function doGenerateString(string $uri, bool $relative): string {
    // Allow the URI to be altered, e.g. to serve a file from a CDN or static
    // file server.
    $this->moduleHandler->alter('file_url', $uri);

    $scheme = StreamWrapperManager::getScheme($uri);

    if (!$scheme) {
      $baseUrl = $relative ? base_path() : $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . base_path();
      return $this->generatePath($baseUrl, $uri);
    }
    elseif ($scheme == 'http' || $scheme == 'https' || $scheme == 'data') {
      // Check for HTTP and data URI-encoded URLs so that we don't have to
      // implement getExternalUrl() for the HTTP and data schemes.
      return $relative ? $this->transformRelative($uri) : $uri;
    }
    elseif ($wrapper = $this->streamWrapperManager->getViaUri($uri)) {
      // Attempt to return an external URL using the appropriate wrapper.
      $externalUrl = $wrapper->getExternalUrl();
      return $relative ? $this->transformRelative($externalUrl) : $externalUrl;
    }
    throw new InvalidStreamWrapperException();
  }

  /**
   * Generate a URL path.
   *
   * @todo switch to this same method of the @file_url_generator Drupal Core
   *   (since 9.3+) service once we fork on a branch not supporting 8.x anymore.
   *
   * @param string $base_url
   *   The base URL.
   * @param string $uri
   *   The URI.
   *
   * @return string
   *   The URL path.
   */
  protected function generatePath(string $base_url, string $uri): string {
    // Allow for:
    // - root-relative URIs (e.g. /foo.jpg in http://example.com/foo.jpg)
    // - protocol-relative URIs (e.g. //bar.jpg, which is expanded to
    //   http://example.com/bar.jpg by the browser when viewing a page over
    //   HTTP and to https://example.com/bar.jpg when viewing an HTTPS page)
    // Both types of relative URIs are characterized by a leading slash, hence
    // we can use a single check.
    if (mb_substr($uri, 0, 1) == '/') {
      return $uri;
    }
    else {
      // If this is not a properly formatted stream, then it is a shipped
      // file. Therefore, return the urlencoded URI with the base URL
      // prepended.
      $options = UrlHelper::parse($uri);
      $path = $base_url . UrlHelper::encodePath($options['path']);
      // Append the query.
      if ($options['query']) {
        $path .= '?' . UrlHelper::buildQuery($options['query']);
      }

      // Append fragment.
      if ($options['fragment']) {
        $path .= '#' . $options['fragment'];
      }

      return $path;
    }
  }

  /**
   * Constructor of the Icon Managed File Service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   File system service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\Render\ElementInfoManagerInterface $element_info
   *   The element info manager.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The stream wrapper manager.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    TranslationInterface $string_translation,
    FileSystemInterface $file_system,
    EntityTypeManagerInterface $entity_manager,
    ModuleHandlerInterface $module_handler,
    LinkGeneratorInterface $link_generator,
    ElementInfoManagerInterface $element_info,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    RequestStack $request_stack,
    ExtensionPathResolver $extension_path_resolver,
    LoggerChannelFactoryInterface $logger_factory
  ) {
    $this->config = $config_factory;
    $this->stringTranslation = $string_translation;
    $this->entityManager = $entity_manager;
    $this->moduleHandler = $module_handler;
    $this->link = $link_generator;
    $this->elementInfo = $element_info;
    $this->geofieldMapSettings = $config_factory->get('geofield_map.settings');
    $this->fileSystem = $file_system;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->requestStack = $request_stack;
    $this->extensionPathResolver = $extension_path_resolver;
    $this->logger = $logger_factory->get('geofield_map');
    $this->fileUploadValidators = [
      'file_validate_extensions' => !empty($this->geofieldMapSettings->get('theming.markers_extensions')) ? [$this->geofieldMapSettings->get('theming.markers_extensions')] : ['gif png jpg jpeg'],
      'geofield_map_file_validate_is_image' => [],
      'file_validate_size' => !empty($this->geofieldMapSettings->get('theming.markers_filesize')) ? [Bytes::toNumber($this->geofieldMapSettings->get('theming.markers_filesize'))] : [Bytes::toNumber('250 KB')],
    ];
    $this->defaultIconElement = [
      '#theme' => 'image',
      '#uri' => '',
    ];
    $this->allowedExtension = $this->geofieldMapSettings->get('theming.markers_extensions');
    $this->markersFilesList = $this->setMarkersFilesList();
  }

  /**
   * Get the default Icon Element.
   *
   * @return array
   *   The defaultIconElement element property.
   */
  public function getDefaultIconElement(): array {
    return $this->defaultIconElement;
  }

  /**
   * Validates the Icon Image statuses.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public static function validateIconImageStatus(array $element, FormStateInterface $form_state) {
    $clicked_button = end($form_state->getTriggeringElement()['#parents']);
    if (!empty($element['#value']['fids'][0])) {
      /* @var \Drupal\file\Entity\file $file */
      $file = File::load($element['#value']['fids'][0]);
      if (in_array($clicked_button, ['save_settings', 'submit'])) {
        $file->setPermanent();
        self::fileSave($file);
      }
      if ($clicked_button == 'remove_button') {
        $file->setTemporary();
        self::fileSave($file);
      }
    }
  }

  /**
   * Save a file, handling exception.
   *
   * @param \Drupal\file\Entity\File $file
   *   The file to save.
   */
  public static function fileSave(File $file) {
    try {
      $file->save();
    }
    catch (EntityStorageException $e) {
      Drupal::logger('geofield_map')->warning(t("The file couldn't be saved: @message", [
        '@message' => $e->getMessage(),
      ])
      );
    }
  }

  /**
   * Generate Icon File Managed Element.
   *
   * @param int|null $fid
   *   The file to save.
   * @param int|null $row_id
   *   The row id.
   *
   * @return array
   *   The icon preview element.
   */
  public function getIconFileManagedElement(int $fid = NULL, int $row_id = NULL): array {

    $upload_location = $this->markersLocationUri();

    // Essentially we use the managed_file type, extended with some
    // enhancements.
    $element = $this->elementInfo->getInfo('managed_file');

    // Add custom and specific attributes.
    $element['#row_id'] = $row_id;
    $element['#geofield_map_marker_icon_upload'] = TRUE;
    $element['#theme'] = 'image_widget';
    $element['#preview_image_style'] = 'geofield_map_default_icon_style';
    $element['#title'] = $this->t('Choose a Marker Icon file');
    $element['#title_display'] = 'invisible';
    $element['#default_value'] = !empty($fid) ? [$fid] : NULL;
    $element['#error_no_message'] = FALSE;
    $element['#upload_location'] = $upload_location;
    $element['#upload_validators'] = $this->fileUploadValidators;
    $element['#progress_message'] = $this->t('Please wait...');
    $element['#element_validate'] = [
      [get_class($this), 'validateIconImageStatus'],
    ];
    $element['#process'][] = [get_class($this), 'processSvgManagedFile'];
    return $element;
  }

  /**
   * React and further expands the managed_file element in case of SVG format.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element. Note that $element must be taken by reference here, so processed
   *   child elements are taken over into $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The updated element.
   */
  public static function processSvgManagedFile(array &$element, FormStateInterface $form_state, array &$complete_form): array {

    $file_is_svg = FALSE;

    if (isset($element['fids']) && !empty($element['fids']['#value'])) {
      $fid = $element['fids']['#value'][0];
      $file_is_svg = ($file = $element['#files'][$fid]) && \Drupal::service('geofield_map.marker_icon')->fileIsManageableSvg($file);
    }

    $element['is_svg'] = [
      '#type' => 'checkbox',
      '#value' => $file_is_svg,
      '#dafault_value' => $file_is_svg,
      '#attributes' => [
        'class' => ['visually-hidden'],
      ],
    ];

    return $element;
  }

  /**
   * Generate Icon File Select Element.
   *
   * @param string|null $file_uri
   *   The file uri to save.
   * @param int|string|null $row_id
   *   The row id.
   *
   * @return array
   *   The icon preview element.
   */
  public function getIconFileSelectElement($file_uri, $row_id = NULL): array {
    $options = array_merge(['none' => '_ none _'], $this->getMarkersFilesList());
    return [
      '#row_id' => $row_id,
      '#geofield_map_marker_icon_select' => TRUE,
      '#title' => $this->t('Marker'),
      '#type' => 'select',
      '#options' => $options,
      '#default_value' => $file_uri,
      '#description' => $this->t('Choose among the markers files available'),
    ];
  }

  /**
   * Generate Image Style Selection Element.
   *
   * @return array
   *   The Image Style Select element.
   */
  public function getImageStyleOptions(): array {
    $options = [
      'none' => $this->t('<- Original File ->'),
    ];

    if ($this->moduleHandler->moduleExists('image')) {

      // Always force the definition of the geofield_map_default_icon_style,
      // if not present.
      if (!ImageStyle::load('geofield_map_default_icon_style')) {
        try {
          $this->setDefaultIconStyle();
        }
        catch (ParseException $e) {
        }
      }

      $image_styles = ImageStyle::loadMultiple();
      /* @var \Drupal\image\ImageStyleInterface $style */
      foreach ($image_styles as $k => $style) {
        $options[$k] = Unicode::truncate($style->label(), 20, TRUE, TRUE);
      }
    }

    return $options;
  }

  /**
   * Generate File Upload Help Message.
   *
   * @return array
   *   The field upload help element.
   */
  public function getFileUploadHelp(): array {
    $element = [
      '#type' => 'html_tag',
      '#tag' => 'div',
      'file_upload_help' => [
        '#theme' => 'file_upload_help',
        '#upload_validators' => $this->fileUploadValidators,
        '#cardinality' => 1,
      ],
      'geofield_map_settings_link' => [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Customize this in  @geofield_map_settings_page_link', [
          '@geofield_map_settings_page_link' => $this->link->generate('Geofield Map Settings Page', Url::fromRoute('geofield_map.settings')),
        ]),
      ],
      '#attributes' => [
        'style' => ['style' => 'font-size:0.9em; color: gray; font-weight: normal'],
      ],
    ];

    // Check and initial setup for SVG file support.
    if (!$this->moduleHandler->moduleExists('svg_image')) {
      $element['svg_support'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('SVG Files support is disabled. Enabled it with @svg_image_link', [
          '@svg_image_link' => $this->link->generate('SVG Image Module', Url::fromUri('https://www.drupal.org/project/svg_image', [
            'absolute' => TRUE,
            'attributes' => ['target' => 'blank'],
          ])),
        ]),
      ];
    }
    else {
      $element['svg_support'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('SVG Files support enabled.'),
      ];
    }

    return $element;
  }

  /**
   * Generate File Select Help Message.
   *
   * @return array
   *   The field select help element.
   */
  public function getFileSelectHelp(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'div',
      '#value' => $this->t('Select among the files available in the Theming Markers Location:<br>@markers_location,<br>Looked extensions: @allowed_extensions<br>Customize this in: @geofield_map_settings_page_link', [
        '@markers_location' => $this->markersLocationUri(),
        '@allowed_extensions' => implode(', ', explode(' ', $this->allowedExtension)),
        '@geofield_map_settings_page_link' => $this->link->generate('Geofield Map Settings Page', Url::fromRoute('geofield_map.settings')),
      ]),
      '#attributes' => [
        'style' => ['style' => 'font-size:0.9em; color: gray; font-weight: normal'],
      ],
    ];
  }

  /**
   * Generate Legend Icon from Uploaded File.
   *
   * @param int $fid
   *   The file identifier.
   * @param string $image_style
   *   The image style identifier.
   *
   * @return array
   *   The icon view render array.
   */
  public function getLegendIconFromFid(int $fid, string $image_style = 'none'): array {
    $icon_element = [];
    try {
      /* @var \Drupal\file\Entity\file $file */
      $file = $this->entityManager->getStorage('file')->load($fid);
      if ($file instanceof FileInterface) {
        $this->defaultIconElement['#uri'] = $file->getFileUri();
        switch ($image_style) {
          case 'none':
            $icon_element = [
              '#weight' => -10,
              '#theme' => 'image',
              '#uri' => $file->getFileUri(),
            ];
            break;

          default:
            $icon_element = [
              '#weight' => -10,
              '#theme' => 'image_style',
              '#uri' => $file->getFileUri(),
              '#style_name' => '',
            ];
            if ($this->moduleHandler->moduleExists('image') && ImageStyle::load($image_style) && !$this->fileIsManageableSvg($file)) {
              $icon_element['#style_name'] = $image_style;
            }
            else {
              $icon_element = $this->defaultIconElement;
            }
        }
      }
    }
    catch (\Exception $e) {
      $this->logger->warning($e->getMessage());
    }
    return $icon_element;
  }

  /**
   * Generate Legend Icon from selected File Uri.
   *
   * @param string $file_uri
   *   The file uri to save.
   * @param string|int|null $icon_width
   *   The icon width.
   *
   * @return array
   *   The icon view render array.
   */
  public function getLegendIconFromFileUri(string $file_uri, $icon_width = NULL): array {
    return [
      '#theme' => 'image',
      '#uri' => $this->generateAbsoluteString($file_uri),
      '#attributes' => [
        'width' => $icon_width,
      ],
    ];
  }

  /**
   * Creates an absolute web-accessible URL string.
   *
   * @param string $uri
   *   The URI to a file for which we need an external URL, or the path to a
   *   shipped file.
   *
   * @return string
   *   An absolute string containing a URL that may be used to access the
   *   file.
   *
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   If a stream wrapper could not be found to generate an external URL.
   */
  public function generateAbsoluteString(string $uri): string {
    return $this->doGenerateString($uri, FALSE);
  }

  /**
   * Generate Uri from fid, and image style.
   *
   * @todo switch to this same method of the @file_url_generator Drupal Core
   *   (since 9.3+) service once we fork on a branch not supporting 8.x anymore.
   *
   * @param int|null $fid
   *   The file identifier.
   *
   * @return string
   *   The icon preview element.
   */
  public function getUriFromFid($fid = NULL): string {
    try {
      /* @var \Drupal\file\Entity\file $file */
      if (isset($fid) && $file = $this->entityManager->getStorage('file')->load($fid)) {
        return $file->getFileUri();
      }
    }
    catch (\Exception $e) {
      $this->logger->warning($e->getMessage());
    }
    return '';
  }

  /**
   * Transforms an absolute URL of a local file to a relative URL.
   *
   * @todo switch to this same method of the @file_url_generator Drupal Core
   *   (since 9.3+) service once we fork on a branch not supporting 8.x anymore.
   *
   * May be useful to prevent problems on multisite set-ups and prevent mixed
   * content errors when using HTTPS + HTTP.
   *
   * @param string $file_url
   *   A file URL of a local file as generated by
   *   \Drupal\Core\File\FileUrlGenerator::generate().
   * @param bool $root_relative
   *   (optional) TRUE if the URL should be relative to the root path or FALSE
   *   if relative to the Drupal base path.
   *
   * @return string
   *   If the file URL indeed pointed to a local file and was indeed absolute,
   *   then the transformed, relative URL to the local file. Otherwise: the
   *   original value of $file_url.
   */
  public function transformRelative(string $file_url, bool $root_relative = TRUE): string {
    // Unfortunately, we pretty much have to duplicate Symfony's
    // Request::getHttpHost() method because Request::getPort() may return NULL
    // instead of a port number.
    $request = $this->requestStack->getCurrentRequest();
    $host = $request->getHost();
    $scheme = $request->getScheme();
    $port = $request->getPort() ?: 80;

    // Files may be accessible on a different port than the web request.
    $file_url_port = parse_url($file_url, PHP_URL_PORT) ?? $port;
    if ($file_url_port != $port) {
      return $file_url;
    }

    if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443)) {
      $http_host = $host;
    }
    else {
      $http_host = $host . ':' . $port;
    }

    // If this should not be a root-relative path but relative to the drupal
    // base path, add it to the host to be removed from the URL as well.
    if (!$root_relative) {
      $http_host .= $request->getBasePath();
    }

    return preg_replace('|^https?://' . preg_quote($http_host, '|') . '|', '', $file_url);
  }

  /**
   * Generate the List of Markers Files.
   *
   * @return array
   *   The Markers Files list.
   */
  public function getMarkersFilesList(): array {
    return $this->markersFilesList;
  }

  /**
   * Generate File Managed Url from fid, and image style.
   *
   * @param int|null $fid
   *   The file identifier.
   * @param string $image_style
   *   The image style identifier.
   *
   * @return string
   *   The url path to the file id (image style).
   */
  public function getFileManagedUrl($fid = NULL, string $image_style = 'none'): string {
    try {
      /* @var \Drupal\file\Entity\file $file */
      if (isset($fid) && $file = $this->entityManager->getStorage('file')->load($fid)) {
        $uri = $file->getFileUri();
        if ($this->moduleHandler->moduleExists('image') && $image_style != 'none' && ImageStyle::load($image_style) && !$this->fileIsManageableSvg($file)) {
          $url = ImageStyle::load($image_style)->buildUrl($uri);
        }
        else {
          $url = $this->generateAbsoluteString($uri);
        }
        return $url;
      }
    }
    catch (\Exception $e) {
      $this->logger->warning($e->getMessage());
    }
    return '';
  }

  /**
   * Generate File Url from file uri.
   *
   * @param string|null $file_uri
   *   The file uri to save.
   *
   * @return string
   *   The url path to the file id (image style).
   */
  public function getFileSelectedUrl(string $file_uri = NULL): string {
    if (isset($file_uri)) {
      return $this->generateAbsoluteString($file_uri);
    }
    return '';
  }

}
