<?php

namespace Drupal\Tests\view_unpublished\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\views\Views;

/**
 * Tests the View Unpublished dependency issue.
 *
 * @group view_unpublished
 */
class ViewUnpublishedDependencyTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'node',
    'system',
    'text',
    'user',
    'view_unpublished',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig('node');
  }

  /**
   * Tests view_unpublished not added as dependency of content view.
   */
  public function testDependencyNotAdded() {
    // Check dependency before saving.
    $module_deps = $this->config('views.view.content')->get('dependencies.module');
    $this->assertArrayNotHasKey('view_unpublished', array_flip($module_deps));

    // Save and check again.
    $view = Views::getView('content');
    $view->save();
    $module_deps = $this->config('views.view.content')->get('dependencies.module');
    $this->assertArrayNotHasKey('view_unpublished', array_flip($module_deps));
  }

  /**
   * Tests the remove dependency install helper.
   */
  public function testDependencyRemoved() {
    $module_deps = $this->config('views.view.content')->get('dependencies.module');
    $module_deps[] = 'view_unpublished';
    $this->config('views.view.content')->set('dependencies.module', $module_deps)->save(TRUE);
    $this->container->get('view_unpublished.install_helper')->removeDependency();
    $module_deps = $this->config('views.view.content')->get('dependencies.module');
    $this->assertNotEmpty($module_deps);
    $this->assertArrayNotHasKey('view_unpublished', array_flip($module_deps));
  }

}
