<?php

namespace Drupal\Tests\image_resize_filter\Functional;

use Drupal\Core\File\FileSystemInterface;
use Drupal\comment\Tests\CommentTestTrait;
use Drupal\filter\Entity\FilterFormat;
use Drupal\file\Entity\File;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Functional tests to test the filter_image_resize filter.
 * @group image_resize_filter
 */
class ResizeImageTest extends BrowserTestBase {

  use CommentTestTrait;
  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['filter', 'file', 'image_resize_filter', 'node', 'comment'];

  /**
   * An authenticated user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $webUser;


  protected function setUp() {
    parent::setUp();

    // Setup Filtered HTML text format.
    $filtered_html_format = FilterFormat::create(array(
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'filters' => array(
        'filter_image_resize' => array(
          'status' => 1,
        ),
      ),
    ));
    $filtered_html_format->save();

    // Setup users.
    $this->webUser = $this->drupalCreateUser(array(
      'access content',
      'access comments',
      'post comments',
      'skip comment approval',
      $filtered_html_format->getPermissionName(),
    ));
    $this->drupalLogin($this->webUser);

    // Setup a node to comment and test on.
    $this->drupalCreateContentType(array('type' => 'page', 'name' => 'Basic page'));
    // Add a comment field.
    $this->addDefaultCommentField('node', 'page');
    $this->node = $this->drupalCreateNode();
  }

  /**
   * Test the resize feature.
   */
  public function testResizeImages() {
    $test_images = $this->getTestFiles('image');
    $image = $test_images[0];
    $uri = $image->uri;
    $file = File::create([
      'uri' => $uri,
      'uuid' => 'thisisauuid',
    ]);
    $file->save();
    $relative_path = file_url_transform_relative(file_create_url($uri));
    $images['inline-image'] = '<img alt="This is a description" data-entity-type="file" data-entity-uuid="' . $file->uuid() . '" height="50" src="' . $relative_path . '" width="44">';
    $comment = [];
    foreach ($images as $key => $img) {
      $comment[$key] = $img;
    }
    $edit = array(
      'comment_body[0][value]' => implode("\n", $comment),
    );
    $this->drupalPostForm('node/' . $this->node->id(), $edit, t('Save'));
    $expected = 'public://resize/' . $image->name . '-44x50.png';
    $expected_relative_path = file_url_transform_relative(file_create_url($expected));
    $this->assertNoRaw($relative_path, 'The original image is gone.');
    $this->assertRaw($expected_relative_path, 'The resize version was found.');
    $this->assertTrue(file_exists($expected), 'The resize file exists.');
  }

}
