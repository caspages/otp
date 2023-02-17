<?php

namespace Drupal\eu_cookie_compliance\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the interface for consent storages.
 *
 * @see \Drupal\eu_cookie_compliance\Plugin\ConsentStorageBase
 * @see \Drupal\eu_cookie_compliance\Plugin\ConsentStorageManager
 * @see \Drupal\eu_cookie_compliance\Plugin\ConsentStorageManagerInterface
 * @see plugin_api
 */
interface ConsentStorageInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * Returns the consent storage label.
   *
   * @return string
   *   The consent storage label.
   */
  public function label();

  /**
   * Returns the consent storage description.
   *
   * @return string
   *   The consent storage description.
   */
  public function description();

}
