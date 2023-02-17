<?php

namespace Drupal\Tests\footnotes\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\editor\Entity\Editor;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\filter\Entity\FilterFormat;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\ckeditor\Traits\CKEditorTestTrait;

/**
 * Contains Footnotes CKEditor plugin functionality tests.
 *
 * @group footnotes
 */
class FootnotesCkeditorPluginTest extends WebDriverTestBase {

  use StringTranslationTrait;
  use CKEditorTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The account.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * The FilterFormat config entity used for testing.
   *
   * @var \Drupal\filter\FilterFormatInterface
   */
  protected $filterFormat;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'node',
    'ckeditor',
    'filter',
    'ckeditor_test',
    'fakeobjects',
    'footnotes',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a text format and associate CKEditor.
    $this->filterFormat = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'filters' => [
        'filter_footnotes' => [
          'status' => TRUE,
          'settings' => [
            'footnotes_collapse' => 0,
            'footnotes_html' => 0,
          ],
        ],
      ],
    ]);
    $this->filterFormat->save();

    Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
      'settings' => [
        'toolbar' => [
          'rows' => [
            [
              [
                'name' => 'All the things',
                'items' => [
                  'Source',
                  'Bold',
                  'Italic',
                  'footnotes',
                ],
              ],
            ],
          ],
        ],
      ],
    ])->save();

    // Create a node type for testing.
    NodeType::create(['type' => 'page', 'name' => 'page'])->save();

    $field_storage = FieldStorageConfig::loadByName('node', 'body');

    // Create a body field instance for the 'page' node type.
    FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'page',
      'label' => 'Body',
      'settings' => ['display_summary' => TRUE],
      'required' => TRUE,
    ])->save();

    // Assign widget settings for the 'default' form mode.
    EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('body', ['type' => 'text_textarea_with_summary'])
      ->save();

    $this->account = $this->drupalCreateUser([
      'administer nodes',
      'create page content',
      'use text format filtered_html',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests CKEditor plugin functionality for body field.
   */
  public function testUi() {
    $session = $this->getSession();
    $assert_session = $this->assertSession();

    $this->drupalGet("node/add/page");
    $page = $session->getPage();

    $this->waitForEditor();
    $this->pressEditorButton('footnotes');
    $this->assertNotEmpty(
      $assert_session->waitForElementVisible('css', '.cke_1.cke_editor_edit-body-0-value_dialog')
    );
    $assert_session->elementTextContains('css', 'table.cke_dialog .cke_dialog_title', $this->t('Footnotes Dialog'));
    $assert_session->elementTextContains('css', '.cke_dialog_page_contents table tr:first-child', $this->t('Footnote text :'));
    $assert_session->elementTextContains('css', '.cke_dialog_page_contents table tr:last-child', $this->t('Value :'));
    $page->find('css', 'a.cke_dialog_ui_button_cancel')->click();

    $this->assertEmpty($assert_session->elementExists('css', '.cke_1.cke_editor_edit-body-0-value_dialog')->isVisible());

    $texts = ['Text one.', 'Text two.', 'Text tree', 'Text four', 'Text five'];
    foreach ($texts as $key => $value) {
      $this->pressEditorButton('footnotes');
      $this->assertNotEmpty(
        $assert_session->waitForElementVisible('css', '.cke_1.cke_editor_edit-body-0-value_dialog')
      );
      $assert_session->elementExists('css', '.cke_dialog_page_contents table tr:last-child input')->setValue($key);
      $assert_session->elementExists('css', '.cke_dialog_page_contents table tr:first-child input')->setValue($value);
      $page->find('css', 'a.cke_dialog_ui_button_ok')->click();

      $this->assertEmpty($assert_session->elementExists('css', '.cke_1.cke_editor_edit-body-0-value_dialog')->isVisible());
    }
    $this->pressEditorButton('source');
    $body_value = $assert_session->elementExists('css', '.cke .cke_contents .cke_source')->getValue();

    $body_value = str_replace(["\r\n", "\r", "\n"], "", $body_value);
    $body_value = trim($body_value);

    $expected_value = '<p>';
    foreach ($texts as $key => $value) {
      $expected_value .= '<fn value="' . $key . '">' . $value . '</fn>';
    }
    $expected_value .= '</p>';

    $this->assertEqual($body_value, $expected_value, $this->t('String, formed by CKEditor, is correct.'));
  }

}
