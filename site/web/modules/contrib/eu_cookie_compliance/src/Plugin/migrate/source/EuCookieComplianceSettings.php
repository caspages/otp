<?php

namespace Drupal\eu_cookie_compliance\Plugin\migrate\source;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\Variable;

/**
 * EU cookie compliance migrate source plugin.
 *
 * @MigrateSource(
 *   id = "eu_cookie_compliance_settings",
 *   source_module = "eu_cookie_compliance"
 * )
 */
class EuCookieComplianceSettings extends Variable {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityTypeManagerInterface $entity_type_manager) {
    $configuration['variables'] = [
      'eu_cookie_compliance',
      'eu_cookie_compliance_cookie_lifetime',
      'eu_cookie_compliance_domain',
      'eu_cookie_compliance_domain_all_sites',
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_type_manager);
  }

}
