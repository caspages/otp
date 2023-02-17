<?php

namespace Drupal\eu_cookie_compliance\Plugin\migrate\source;

use Drupal\migrate\Plugin\migrate\source\DummyQueryTrait;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * EU cookie compliance migrate source plugin.
 *
 * @MigrateSource(
 *   id = "eu_cookie_category",
 *   source_module = "eu_cookie_compliance"
 * )
 */
class EuCookieCategory extends DrupalSqlBase {

  use DummyQueryTrait;

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'machine_name' => 'Machine Name',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'machine_name' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    $categories = $this->getDatabase()
      ->select('variable', 'v')
      ->fields('v', ['value'])
      ->condition('v.name', 'eu_cookie_compliance_categories')
      ->execute()
      ->fetchField();

    $unserialised_categories = $categories !== FALSE ? unserialize($categories, ['allowed_classes' => FALSE]) : [];
    return new \ArrayIterator($unserialised_categories);
  }

  /**
   * {@inheritdoc}
   */
  public function count($refresh = FALSE): int {
    return (int) $this->initializeIterator()->count();
  }

}
