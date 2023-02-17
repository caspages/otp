<?php

namespace Drupal\eu_cookie_compliance\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Transforms EU Cookie Compliance exclude paths according to D9 guidelines.
 *
 * @MigrateProcessPlugin(
 *   id = "eu_cookie_compliance_exclude_paths"
 * )
 */
class EuCookieComplianceExcludePaths extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $all_paths = '';
    foreach (explode("\r\n", $value) as $path) {
      $all_paths = $all_paths . '/' . $path . "\n";
    }
    return rtrim($all_paths);
  }

}
