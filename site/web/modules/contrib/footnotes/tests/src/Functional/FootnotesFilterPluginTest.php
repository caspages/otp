<?php

namespace Drupal\Tests\footnotes\Functional;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;

/**
 * Contains Footnotes Filter plugin functionality tests.
 *
 * @group footnotes
 */
class FootnotesFilterPluginTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'fakeobjects',
    'footnotes',
    'node',
  ];

  /**
   * An user with permissions to proper permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Text format name.
   *
   * @var string
   */
  protected $formatName;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a filter admin user.
    $permissions = [
      'administer filters',
      'administer nodes',
      'access administration pages',
      'administer site configuration',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $this->formatName = strtolower($this->randomMachineName());

    $this->drupalLogin($this->adminUser);
    $this->createTextFormat();
    $this->drupalCreateContentType(['type' => 'page']);
  }

  /**
   * Tests CKEditor Filter plugin functionality.
   */
  public function testDefaultFunctionality() {
    // Verify a title with HTML entities is properly escaped.
    $text1 = 'This is the note one.';
    $note1 = '[fn]' . $text1 . '[/fn]';
    $text2 = 'And this is the note two.';
    $note2 = "<fn>$text2</fn>";

    $body = '<p>' . $this->randomMachineName(100) . $note1 . '</p><p>' .
      $this->randomMachineName(100) . $note2 . '</p>';

    // Create a node.
    $node = $this->drupalCreateNode([
      'title' => $this->randomString(),
      'body' => [
        0 => [
          'value' => $body,
          'format' => $this->formatName,
        ],
      ],
    ]);

    $this->drupalGet('node/' . $node->id());

    // Footnote with [fn].
    $this->assertNoRaw($note1);
    $this->assertText($text1);

    // Footnote with <fn>.
    $this->assertNoRaw($note2);
    $this->assertText($text2);

    // Css file:
    $this->assertRaw('/assets/css/footnotes.css');
    // @todo currently additional settings doesn't work as expected.
    // So we don't check additional settings for now.
    // $this->createTextFormat(TRUE);
    $text1 = 'This is the note one.';
    $note1 = "[fn value='1']{$text1}[/fn]";
    $text2 = 'And this is the note two.';
    $note2 = "<fn value='1'>{$text2}</fn>";

    $body = '<p>' . $this->randomMachineName(100) . $note1 . '</p><p>' .
      $this->randomMachineName(100) . $note2 . '</p>';

    // Create a node.
    $node = $this->drupalCreateNode([
      'title' => $this->randomString(),
      'body' => [
        0 => [
          'value' => $body,
          'format' => $this->formatName,
        ],
      ],
    ]);

    $this->drupalGet('node/' . $node->id());

    // Footnote with [fn].
    $this->assertNoRaw($note1);
    $this->assertText($text1);

    // Elements with the same value should be collapsed.
    // @todo This should work only if footnotes_collapse setting is enabled.
    $this->assertNoRaw($note2);
    $this->assertNoText($text2);
  }

  /**
   * Create a new text format.
   *
   * @param bool $additional_settings
   *   Indicates if filter settings should be enabled.
   */
  protected function createTextFormat($additional_settings = FALSE) {
    $button_groups = json_encode([
      [
        [
          'name' => 'Tools',
          'items' => ['Source', 'footnotes'],
        ],
      ],
    ]);

    $edit = [
      'format' => $this->formatName,
      'name' => $this->formatName,
      'roles[' . AccountInterface::AUTHENTICATED_ROLE . ']' => TRUE,
      'editor[editor]' => 'ckeditor',
      'filters[filter_footnotes][status]' => TRUE,
    ];
    $this->drupalGet("admin/config/content/formats/add");
    // Keep the "CKEditor" editor selected and click the "Configure" button.
    $this->drupalPostForm(NULL, $edit, 'editor_configure');
    $edit['editor[settings][toolbar][button_groups]'] = $button_groups;
    $edit['filters[filter_footnotes][settings][footnotes_collapse]'] = $button_groups;
    if ($additional_settings) {
      $edit['filters[filter_footnotes][settings][footnotes_collapse]'] = 1;
      $edit['filters[filter_footnotes][settings][footnotes_html]'] = 1;
    }
    $this->drupalPostForm(NULL, $edit, $this->t('Save configuration'));
    $this->assertText($this->t('Added text format @format.', ['@format' => $this->formatName]));
  }

}
