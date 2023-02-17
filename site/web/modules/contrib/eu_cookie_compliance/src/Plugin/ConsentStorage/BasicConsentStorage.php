<?php

namespace Drupal\eu_cookie_compliance\Plugin\ConsentStorage;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\eu_cookie_compliance\Plugin\ConsentStorageBase;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a database storage for cookie consents.
 *
 * @ConsentStorage(
 *   id = "basic",
 *   name = @Translation("Basic storage"),
 *   description = @Translation("Basic storage")
 * )
 */
class BasicConsentStorage extends ConsentStorageBase {

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * BasicConsentStorage constructor.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $config_factory);

    $this->time = $time;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function registerConsent($consent_type) {
    $revision_id = $this->getCurrentPolicyNodeRevision();
    $timestamp = $this->time->getRequestTime();
    $ip_address = \Drupal::request()->getClientIp();
    $uid = \Drupal::currentUser()->id();

    \Drupal::database()->insert('eu_cookie_compliance_basic_consent')->fields(
      [
        'uid' => $uid,
        'ip_address' => $ip_address,
        'timestamp' => $timestamp,
        'revision_id' => $revision_id ? $revision_id : 0 ,
        'consent_type' => $consent_type,
      ]
    )->execute();
    return TRUE;
  }

}
