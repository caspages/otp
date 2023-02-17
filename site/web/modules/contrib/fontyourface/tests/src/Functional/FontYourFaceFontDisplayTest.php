<?php

namespace Drupal\Tests\fontyourface\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that font displays show css.
 *
 * @group fontyourface
 */
class FontYourFaceFontDisplayTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['views', 'fontyourface', 'websafe_fonts_test'];

  /**
   * A test user with permission to access the @font-your-face sections.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    // Create and log in an administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer font entities',
    ]);
    $this->drupalLogin($this->adminUser);

    // Set up default themes.
    \Drupal::service('theme_handler')->install(['bartik', 'seven']);
    $this->config('system.theme')
      ->set('default', 'bartik')
      ->set('admin', 'seven')
      ->save();

    // Enable Arial font.
    $this->drupalGet(Url::fromRoute('font.settings'));
    $this->submitForm(['load_all_enabled_fonts' => FALSE], 'Save configuration');
    $this->drupalGet(Url::fromRoute('font.settings'));
    $this->submitForm([], 'Import from websafe_fonts_test');
  }

  /**
   * Tests font not displayed even when Arial is loaded.
   */
  public function testFontNotDisplayed() {
    $this->drupalGet(url::fromRoute('entity.font.activate', ['font' => 1, 'js' => 'nojs']));
    $this->resetAll();
    // Assert no fonts load to start.
    $this->drupalGet('/node');
    $this->assertNoRaw('<meta name="Websafe Font" content="Arial" />');
  }

  /**
   * Tests font displayed once added in FontDisplay.
   */
  public function testFontDisplayedViaFontDisplayRule() {
    $this->drupalGet(url::fromRoute('entity.font.activate', ['font' => 1, 'js' => 'nojs']));

    $edit = [
      'label' => 'Headers',
      'id' => 'headers',
      'font_url' => 'https://en.wikipedia.org/wiki/Arial',
      'fallback' => '',
      'preset_selectors' => '.fontyourface h1, .fontyourface h2, .fontyourface h3, .fontyourface h4, .fontyourface h5, .fontyourface h6',
      'selectors' => '.fontyourface h1, .fontyourface h2, .fontyourface h3, .fontyourface h4, .fontyourface h5, .fontyourface h6',
      'theme' => 'bartik',
    ];
    $this->drupalGet(Url::fromRoute('entity.font_display.add_form'));
    $this->submitForm($edit, 'Save');
    $this->drupalGet(Url::fromRoute('entity.font_display.collection'));
    $this->resetAll();

    // Assert Arial loads in general bartik section.
    $this->drupalGet('/node');
    $this->assertSession()->responseContains('<meta name="Websafe Font" content="Arial" />');
    $this->assertSession()->responseContains("fontyourface/font_display/headers.css");
  }

}
