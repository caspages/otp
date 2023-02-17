<?php

namespace Drupal\Tests\eu_cookie_compliance\Kernel;

use Drupal\eu_cookie_compliance\Entity\CookieCategory;
use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests EU cookie compliance migration.
 *
 * @group eu_cookie_compliance
 */
class EuCookieComplianceMigrateTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'eu_cookie_compliance',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getFixtureFilePath() {
    return implode(DIRECTORY_SEPARATOR, [
      \Drupal::service('extension.list.module')->getPath('eu_cookie_compliance'),
      'tests',
      'fixtures',
      'drupal7.php',
    ]);
  }

  /**
   * Tests EU Cookie Compliance settings migration.
   */
  public function testEuCookieComplianceSettingsMigration(): void {
    $expected_config = [
      'cookie_lifetime' => 99,
      'domain' => 'www.drupal7ama.test',
      'domain_all_sites' => TRUE,
      'popup_enabled' => TRUE,
      'method' => 'opt_in',
      'popup_info_template' => 'new',
      'enable_save_preferences_button' => TRUE,
      'save_preferences_button_label' => 'Save preferences',
      'accept_all_categories_button_label' => 'Accept all cookies',
      'disabled_javascripts' => 'public://example.js',
      'automatic_cookies_removal' => TRUE,
      'allowed_cookies' => 'category:cookie_123',
      'popup_info' => [
        'value' => '<h2>We use cookies on this site to enhance your user experience</h2><p>By clicking the Accept button, you agree to us doing so!!</p>',
        'format' => 'full_html',
      ],
      'use_mobile_message' => TRUE,
      'mobile_popup_info' => [
        'value' => '<h2>Mobile</h2>',
        'format' => 'full_html',
      ],
      'mobile_breakpoint' => 768,
      'popup_more_info_button_message' => 'No, give me more info',
      'disagree_button_label' => 'No, thanks',
      'withdraw_enabled' => TRUE,
      'withdraw_button_on_info_popup' => FALSE,
      'withdraw_message' => [
        'value' => '<h2>We use cookies on this site to enhance your user experience</h2><p>You have given your consent for us to set cookies.</p>',
        'format' => 'full_html',
      ],
      'withdraw_tab_button_label' => 'Privacy settings',
      'withdraw_action_button_label' => 'Withdraw consent',
      'popup_agreed_enabled' => TRUE,
      'popup_hide_agreed' => TRUE,
      'popup_agreed' => [
        'value' => '<h2>Thank you for accepting cookies</h2><p>You can now hide this message or find out more about cookies.</p>',
        'format' => 'full_html',
      ],
      'popup_find_more_button_message' => 'More info',
      'popup_hide_button_message' => 'Hide',
      'popup_link' => 'http://privacy_policy.com',
      'popup_link_new_window' => TRUE,
      'cookie_policy_version' => '1.0.0',
      'containing_element' => 'body',
      'popup_position' => FALSE,
      'use_bare_css' => FALSE,
      'popup_text_hex' => 'fff',
      'popup_bg_hex' => '0779bf',
      'popup_height' => 50,
      'popup_width' => '100%',
      'fixed_top_position' => TRUE,
      'popup_delay' => 1000,
      'disagree_do_not_show_popup' => TRUE,
      'reload_page' => TRUE,
      'popup_scrolling_confirmation' => FALSE,
      'cookie_name' => 'Chocochips',
      'cookie_value_disagreed' => '0',
      'cookie_value_agreed_show_thank_you' => '1',
      'cookie_value_agreed' => '2',
      'domains_option' => 1,
      'domains_list' => 'http',
      'exclude_paths' => '/node/11
/blog/21',
      'exclude_admin_theme' => TRUE,
      'exclude_uid_1' => TRUE,
      'better_support_for_screen_readers' => TRUE,
      'cookie_session' => 0,
      'consent_storage_method' => 'do_not_store',
    ];

    $this->executeMigrations([
      'eu_cookie_compliance_settings',
    ]);

    $config = $this->config('eu_cookie_compliance.settings')->getRawData();
    $this->assertEquals($expected_config, $config);

    $expected_config_cat1 = [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'id' => 'category_1',
      'label' => 'Category 1',
      'description' => 'Test category.',
      'checkbox_default_state' => 'checked',
      'weight' => 0,
    ];
    $expected_config_cat2 = [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'id' => 'category_2',
      'label' => 'Category 2',
      'description' => 'asdfghjkl',
      'checkbox_default_state' => 'required',
      'weight' => 0,
    ];
    $this->executeMigrations([
      'eu_cookie_compliance_category',
    ]);

    $category_1 = CookieCategory::load('category_1')->toArray();
    unset($category_1['uuid']);
    $this->assertSame($expected_config_cat1, $category_1);

    $category_2 = CookieCategory::load('category_2')->toArray();
    unset($category_2['uuid']);
    $this->assertSame($expected_config_cat2, $category_2);
  }

}
