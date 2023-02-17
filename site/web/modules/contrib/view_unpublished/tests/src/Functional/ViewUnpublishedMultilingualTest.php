<?php

namespace Drupal\Tests\view_unpublished\Functional;

use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\language\Plugin\LanguageNegotiation\LanguageNegotiationUrl;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the View Unpublished module on a multilingual site.
 *
 * @group view_unpublished
 */
class ViewUnpublishedMultilingualTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['view_unpublished', 'node', 'content_translation'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The test user.
   *
   * @var bool|\Drupal\user\Entity\User|false
   */
  protected $adminUser;

  /**
   * Sets up the test.
   */
  protected function setUp() {
    parent::setUp();

    // Create Basic page node type.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);

    $this->adminUser = $this->drupalCreateUser([
      'access administration pages',
      'administer languages',
      'administer content types',
      'administer nodes',
      'create page content',
      'edit any page content',
      'translate any entity',
      'create content translations',
    ]);
    $this->drupalLogin($this->adminUser);

    ConfigurableLanguage::createFromLangcode('it')->save();

    // Enable translation for the current entity type and ensure the change is
    // picked up.
    \Drupal::service('content_translation.manager')->setEnabled('node', 'page', TRUE);
    \Drupal::service('router.builder')->rebuild();
  }

  /**
   * Test view_unpublished with multilingual nodes.
   */
  public function testIt() {
    // Create Basic node content.
    $langcode = language_get_default_langcode('node', 'page');
    $title_key = 'title[0][value]';
    $body_key = 'body[0][value]';
    $status_key = 'status[value]';
    // Create node to edit.
    $edit = [];
    $edit[$title_key] = $this->randomMachineName(8);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$status_key] = 1;
    $this->drupalPostForm('node/add/page', $edit, $this->t('Save'));

    // Check the node language.
    $node = $this->getNodeByTitle($edit[$title_key]);
    $this->assertTrue($node->language()->getId() == $langcode, 'Language correctly set.');

    $this->drupalLogout();
    // Anonymous users should have access.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Unpublish the node and now anonymous users should not.
    $this->drupalLogin($this->adminUser);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$status_key] = 0;
    $this->drupalPostForm($node->toUrl('edit-form'), $edit, $this->t('Save'));
    $this->drupalLogout();
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(403);

    // Log in as a user who should have access to all unpublished content.
    $this->drupalLogin($this->drupalCreateUser(['view any unpublished content']));
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Publish the node again.
    $this->drupalLogin($this->adminUser);
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$status_key] = 1;
    $this->drupalPostForm($node->toUrl('edit-form'), $edit, $this->t('Save'));
    $this->drupalLogout();

    // Anonymous users should have access.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Add an italian translation.
    $this->drupalLogin($this->adminUser);
    $add_translation_url = Url::fromRoute('entity.node.content_translation_add',
      ['node' => $node->id(), 'source' => 'en', 'target' => 'it'],
      [
        'language' => ConfigurableLanguage::load('it'),
        'absolute' => FALSE,
      ]
    );
    $edit[$body_key] = $this->randomMachineName(16);
    $edit[$status_key] = 0;
    // Unpublish the node and now anonymous users should not have access.
    $edit[$status_key] = 0;
    $this->drupalPostForm($add_translation_url, $edit, $this->t('Save (this translation)'));
    // Reset the node so we pick up the translation.
    $node = $this->getNodeByTitle($edit[$title_key], TRUE);
    $this->drupalLogout();

    // Enable content language URL detection.
    $this->container->get('language_negotiator')->saveConfiguration(LanguageInterface::TYPE_CONTENT, [LanguageNegotiationUrl::METHOD_ID => 0]);

    // Anonymous users should have access to the english node.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    $translation_url = "it/node/{$node->id()}";

    // Anonymous users should not have access to the italian node.
    $this->drupalGet($translation_url);
    $this->assertSession()->statusCodeEquals(403);

    // Log in as a user who should have access to unpublished content.
    $this->drupalLogin($this->drupalCreateUser(['view any unpublished content']));
    $this->drupalGet($translation_url);
    $this->assertSession()->statusCodeEquals(200);

    // Log in as a user who should have access to unpublished page content.
    $this->drupalLogin($this->drupalCreateUser(['view any unpublished page content']));
    $this->drupalGet($translation_url);
    $this->assertSession()->statusCodeEquals(200);

    // Log in as a user without the unpublished permissions.
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet($translation_url);
    $this->assertSession()->statusCodeEquals(403);

    // Ensure access remains correct after rebuilding node access.
    node_access_rebuild();

    // Anonymous users should have access to the english node.
    $this->drupalGet($node->toUrl());
    $this->assertSession()->statusCodeEquals(200);

    // Anonymous users should not have access to the italian node.
    $this->drupalGet($translation_url);
    $this->assertSession()->statusCodeEquals(403);

    // Log in as a user who should have access to unpublished content.
    $this->drupalLogin($this->drupalCreateUser(['view any unpublished content']));
    $this->drupalGet($translation_url);
    $this->assertSession()->statusCodeEquals(200);

    // Log in as a user who should have access to unpublished page content.
    $this->drupalLogin($this->drupalCreateUser(['view any unpublished page content']));
    $this->drupalGet($translation_url);
    $this->assertSession()->statusCodeEquals(200);

    // Log in as a user without the unpublished permissions.
    $this->drupalLogin($this->drupalCreateUser());
    $this->drupalGet($translation_url);
    $this->assertSession()->statusCodeEquals(403);
  }

}
