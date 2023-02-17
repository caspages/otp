<?php

namespace Drupal\Tests\editor_advanced_link\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\editor_advanced_link\Plugin\CKEditor5Plugin\AdvancedLink;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\ckeditor5\Traits\CKEditor5TestTrait;
use Drupal\ckeditor5\Plugin\Editor\CKEditor5;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * @coversDefaultClass \Drupal\editor_advanced_link\Plugin\CKEditor5Plugin\AdvancedLink
 * @group editor_advanced_link
 * @group ckeditor5
 * @internal
 */
class AdvancedLinkTest extends WebDriverTestBase {

  use CKEditor5TestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'ckeditor5',
    'editor_advanced_link',
    'node',
    'text',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'starterkit_theme';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    FilterFormat::create([
      'format' => 'test_format',
      'name' => 'Test format',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <em> <a href>',
          ],
        ],
      ],
    ])->save();
    Editor::create([
      'editor' => 'ckeditor5',
      'format' => 'test_format',
      'settings' => [
        'toolbar' => [
          'items' => [
            'link',
            'bold',
            'italic',
          ],
        ],
        'plugins' => [
          'editor_advanced_link_link' => AdvancedLink::DEFAULT_CONFIGURATION,
        ],
      ],
      'image_upload' => [
        'status' => FALSE,
      ],
    ])->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    // Create a sample node to test AdvancedLink on.
    $this->drupalCreateContentType(['type' => 'blog']);
    $this->createNode([
      'type' => 'blog',
      'title' => 'Animals with strange names',
      'body' => [
        'value' => '<p><a href="https://en.wikipedia.org/wiki/Llama">Llamas</a> are cool!</p>',
        'format' => 'test_format',
      ],
    ])->save();

    $this->drupalLogin($this->drupalCreateUser([
      'use text format test_format',
      'bypass node access',
    ]));
  }

  public function providerTest(): array {
    return [
      '<a aria-label>' => [
        'attribute_name' => 'aria-label',
        'input label' => 'ARIA label',
      ],
      '<a title>' => [
        'attribute_name' => 'title',
        'input label' => 'Title',
      ],
      '<a class>' => [
        'attribute_name' => 'class',
        'input label' => 'CSS classes',
      ],
      '<a id>' => [
        'attribute_name' => 'id',
        'input label' => 'ID',
      ],
      '<a rel>' => [
        'attribute_name' => 'rel',
        'input label' => 'Link relationship',
      ],
      '<a target="_blank">' => [
        'attribute_name' => 'target',
        'input label' => 'Open in new window',
        'is button' => TRUE,
      ],
    ];
  }

  /**
   * Tests that AdvancedLink enables setting additional link attributes.
   *
   * @dataProvider providerTest
   */
  public function test(string $attribute_name, string $expected_input_label, bool $is_button = FALSE) {
    // Update text format and editor to allow editing of this attribute through
    // the AdvancedLink plugin.
    $editor = Editor::load('test_format');
    $settings = $editor->getSettings();
    $settings['plugins']['editor_advanced_link_link']['enabled_attributes'][] = $attribute_name;
    $editor->setSettings($settings)
      ->save();
    $format = $editor->getFilterFormat();
    $filter_html_config = $format->filters('filter_html')
      ->getConfiguration();
    $filter_html_config['settings']['allowed_html'] .= ' ' . AdvancedLink::getAllowedHtmlForSupportedAttribute($attribute_name);
    $format
      ->setFilterConfig('filter_html', $filter_html_config)
      ->save();
    $this->assertSame([], array_map(
      function (ConstraintViolation $v) {
        return (string) $v->getMessage();
      },
      iterator_to_array(CKEditor5::validatePair(
        Editor::load('test_format'),
        FilterFormat::load('test_format')
      ))
    ));

    $this->drupalGet('/node/1/edit');
    $this->waitForEditor();
    $assert_session = $this->assertSession();
    $link = $assert_session->waitForElementVisible('css', '.ck-content a[href="https://en.wikipedia.org/wiki/Llama"]', 1000);

    // Confirm the attribute we'll set is not yet present.
    $this->assertStringNotContainsString($attribute_name, $this->getEditorDataAsHtmlString());

    // Assert structure of link form balloon.
    $link->click();
    $this->assertVisibleBalloon('.ck-link-actions');
    $this->getBalloonButton('Edit link')->click();
    $balloon = $this->assertVisibleBalloon('.ck-link-form');
    if (!$is_button) {
      $this->assertTrue($balloon->hasField($expected_input_label));
    }
    else {
      $this->assertTrue($balloon->hasButton($expected_input_label));
    }
    // Two inputs: 1 for the link URL, 1 for the attribute editable through
    // AdvancedLink.
    $this->assertCount(2, $balloon->findAll('css', 'input, button:not(.ck-button-save):not(.ck-button-cancel)'));

    // Confirm we can set the attribute using AdvancedLink's UI.
    if (!$is_button) {
      $balloon->fillField($expected_input_label, 'foobarbaz');
    }
    else {
      $balloon->pressButton($expected_input_label);
    }
    $balloon->submit();
    $this->assertStringContainsString($attribute_name, $this->getEditorDataAsHtmlString());
  }

}
