<?php

namespace Drupal\Tests\jsonapi_image_styles\Functional;

use Drupal\Tests\jsonapi\Functional\JsonApiFunctionalTestBase;

/**
 * Provides helper methods for the module's functional tests.
 *
 * @internal
 */
abstract class JsonApiImageStylesFunctionalTestBase extends JsonApiFunctionalTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'jsonapi_image_styles',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $config = \Drupal::configFactory()->getEditable('jsonapi_image_styles.settings');
    $config->set('image_styles', [
      'large' => 'large',
      'thumbnail' => 'thumbnail',
    ]);
    $config->save(TRUE);
    $this->resetAll();
  }

}
