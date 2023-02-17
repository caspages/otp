<?php

namespace Drupal\Tests\editor_advanced_link\Functional;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the editor_advanced_link alterations on the durpallink dialog form.
 *
 * @group editor_advanced_link
 * @requires module ckeditor
 */
class AdvancedLinkDialogTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'filter',
    'editor_advanced_link',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * An instance of our custom text format.
   *
   * @var \Drupal\filter\Entity\FilterFormat
   */
  protected $format;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    if (!in_array('ckeditor', $this->container->get('extension.list.module')->reset()->getList(), TRUE)) {
      $this->markTestSkipped('CKEditor 4 module not available to install, skipping test.');
    }
    $this->container->get('module_installer')->install(['ckeditor']);
    $this->container = \Drupal::getContainer();

    // Create text format, associate CKEditor.
    $this->format = FilterFormat::create([
      'format' => 'eal_format',
      'name' => 'Editor Advanced Link format',
      'weight' => 0,
      'filters' => [],
    ]);
    $this->format->save();

    Editor::create([
      'format' => 'eal_format',
      'editor' => 'ckeditor',
    ])->save();

    // Customize the configuration.
    $this->container->get('plugin.manager.editor')->clearCachedDefinitions();

    $account = $this->drupalCreateUser([
      'use text format eal_format',
    ]);
    $this->drupalLogin($account);
  }

  /**
   * Test the link dialog fields.
   *
   * @dataProvider providerLinkDialogFieldsForm
   */
  public function testLinkDialogFieldsForm(array $filters, array $expected, bool $has_advanced) {
    // Reset filter format then add filters from the provider.
    foreach ($this->format->filters() as $filter_id => $filter_settings) {
      $this->format->removeFilter($filter_id);
    }
    foreach ($filters as $filter_id => $filter_settings) {
      $this->format->setFilterConfig($filter_id, $filter_settings);
    }
    $this->format->save();

    // Prepare browsing session.
    $session = $this->getSession();
    $page = $session->getPage();

    // Show the link dialog form.
    $this->drupalGet('editor/dialog/link/eal_format');
    $this->assertSession()->statusCodeEquals(200);

    // Check if all fields are as expected.
    foreach ($expected as $field_name => $field_visible) {
      if ($field_visible) {
        $this->assertSession()->fieldExists($field_name);
      }
      else {
        $this->assertSession()->fieldNotExists($field_name);
      }
    }

    // Check if the advanced details elements is as expected.
    if ($has_advanced) {
      $this->assertSession()->elementExists('css', '[data-drupal-selector="edit-advanced"]');
    }
    else {
      $this->assertSession()->elementNotExists('css', '[data-drupal-selector="edit-advanced"]');
    }
  }

  /**
   * Data provider for testLinkDialogFieldsForm().
   */
  public function providerLinkDialogFieldsForm() {
    $cases = [];

    $cases['all_enabled_no_filter'] = [
      [],
      [
        'attributes[href]' => TRUE,
        'attributes[title]' => TRUE,
        'attributes[aria-label]' => TRUE,
        'attributes[class]' => TRUE,
        'attributes[id]' => TRUE,
        'attributes[target]' => TRUE,
        'attributes[rel]' => TRUE,
      ],
      TRUE,
    ];

    $cases['all_enabled_with_filter'] = [
      [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<a href title aria-label class="*" id target rel>',
          ],
        ],
      ],
      [
        'attributes[href]' => TRUE,
        'attributes[title]' => TRUE,
        'attributes[aria-label]' => TRUE,
        'attributes[class]' => TRUE,
        'attributes[id]' => TRUE,
        'attributes[target]' => TRUE,
        'attributes[rel]' => TRUE,
      ],
      TRUE,
    ];

    $cases['aria_label_only'] = [
      [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<a href aria-label>',
          ],
        ],
      ],
      [
        'attributes[href]' => TRUE,
        'attributes[title]' => FALSE,
        'attributes[aria-label]' => TRUE,
        'attributes[class]' => FALSE,
        'attributes[id]' => FALSE,
        'attributes[target]' => FALSE,
        'attributes[rel]' => FALSE,
      ],
      TRUE,
    ];

    $cases['title_only'] = [
      [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<a href title>',
          ],
        ],
      ],
      [
        'attributes[href]' => TRUE,
        'attributes[title]' => TRUE,
        'attributes[aria-label]' => FALSE,
        'attributes[class]' => FALSE,
        'attributes[id]' => FALSE,
        'attributes[target]' => FALSE,
        'attributes[rel]' => FALSE,
      ],
      FALSE,
    ];

    $cases['all_disabled'] = [
      [
        'filter_html' => [
          'id' => 'filter_html',
          'provider' => 'filter',
          'status' => TRUE,
          'settings' => [
            'allowed_html' => '<a href>',
          ],
        ],
      ],
      [
        'attributes[href]' => TRUE,
        'attributes[title]' => FALSE,
        'attributes[aria-label]' => FALSE,
        'attributes[class]' => FALSE,
        'attributes[id]' => FALSE,
        'attributes[target]' => FALSE,
        'attributes[rel]' => FALSE,
      ],
      FALSE,
    ];

    return $cases;
  }

}
