<?php

namespace Drupal\eu_cookie_compliance\Plugin;

use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Collects available consent storages.
 */
interface ConsentStorageManagerInterface extends PluginManagerInterface {

  /**
   * Get all available eu_cookie_compliance storage plugin instances.
   *
   * @param array $configuration
   *   Export configuration (aka export options).
   *
   * @return \Drupal\eu_cookie_compliance\Plugin\ConsentStorageInterface[]
   *   An array of all available eu_cookie_compliance consent plugin instances.
   */
  public function getInstances(array $configuration = []);

  /**
   * Get consent storage plugins as options.
   *
   * @return array
   *   An associative array of options keyed by plugin id.
   */
  public function getOptions();

}
