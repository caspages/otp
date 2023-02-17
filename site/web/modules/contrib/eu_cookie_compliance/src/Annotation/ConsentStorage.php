<?php

namespace Drupal\eu_cookie_compliance\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a consent storage annotation object.
 *
 * Plugin Namespace: Plugin\ConsentStorage.
 *
 * For a working example, see
 * \Drupal\eu_cookie_compliance\Plugin\ConsentStorage\BasicConsentStorage/registerConsent
 *
 * @see hook_eu_cookie_compliance_consent_storage_info_alter()
 * @see \Drupal\eu_cookie_compliance\Plugin\ConsentStorageInterface
 * @see \Drupal\eu_cookie_compliance\Plugin\ConsentStorageBase
 * @see \Drupal\eu_cookie_compliance\Plugin\ConsentStorageManager
 * @see plugin_api
 *
 * @Annotation
 */
class ConsentStorage extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the consent storage.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

  /**
   * A brief description of the consent storage.
   *
   * This will be shown when adding or configuring this consent storage.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $description = '';

}
