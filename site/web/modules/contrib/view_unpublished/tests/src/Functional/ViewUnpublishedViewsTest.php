<?php

namespace Drupal\Tests\view_unpublished\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;

/**
 * Tests the View Unpublished module with views.
 *
 * @group view_unpublished
 */
class ViewUnpublishedViewsTest extends BrowserTestBase {

  use ContentTypeCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = ['view_unpublished', 'node', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Rebuild node access which we have to do after installing the module.
    $this->drupalLogin($this->rootUser);
    node_access_rebuild();
    $this->drupalLogout();

    $this->createContentType(['type' => 'page']);
    $this->createContentType(['type' => 'article']);
  }

  /**
   * Sets up the test.
   */
  public function testIt() {
    $page_node = $this->createNode(['type' => 'page']);
    $page_node->setUnPublished();
    $page_node->save();
    $article_node = $this->createNode(['type' => 'article']);
    $article_node->setUnPublished();
    $article_node->save();

    $this->drupalLogin($this->createUser(['view any unpublished content', 'access content overview']));
    $this->drupalGet('admin/content');
    $this->assertSession()->pageTextContains($page_node->label());
    $this->assertSession()->pageTextContains($article_node->label());

    $this->drupalLogin($this->createUser(['view any unpublished page content', 'access content overview']));
    $this->drupalGet('admin/content');
    $this->assertSession()->pageTextContains($page_node->label());
    $this->assertSession()->pageTextNotContains($article_node->label());

    $this->drupalLogin($this->createUser(['access content overview']));
    $this->drupalGet('admin/content');
    $this->assertSession()->pageTextNotContains($page_node->label());
    $this->assertSession()->pageTextNotContains($article_node->label());
  }

}
