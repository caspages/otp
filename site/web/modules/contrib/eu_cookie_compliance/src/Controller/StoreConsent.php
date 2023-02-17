<?php

namespace Drupal\eu_cookie_compliance\Controller;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\eu_cookie_compliance\Plugin\ConsentStorageManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for JS call that stores consent.
 */
class StoreConsent extends ControllerBase {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Consent Storage.
   *
   * @var \Drupal\eu_cookie_compliance\Plugin\ConsentStorageManager
   */
  protected $consentStorage;

  /**
   * Constructs the SchemaListenerController object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\eu_cookie_compliance\Plugin\ConsentStorageManager $consent_storage
   *   The consent storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ConsentStorageManager $consent_storage) {
    $this->configFactory = $config_factory;
    $this->consentStorage = $consent_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('plugin.manager.eu_cookie_compliance.consent_storage')
    );
  }

  /**
   * Store consent.
   *
   * @param string $target
   *   The target.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Result of action.
   */
  public function store($target) {
    // Get the currently active plugin.
    $consent_storage_method = $this->configFactory
      ->get('eu_cookie_compliance.settings')
      ->get('consent_storage_method');
    // If we're not going to log consent, return NULL.
    if (!$consent_storage_method || $consent_storage_method === 'do_not_store') {
      return new JsonResponse(NULL);
    }

    // Get plugin.
    $consent_storage = $this->consentStorage->createInstance($consent_storage_method);
    // Register consent.
    $result = $consent_storage->registerConsent($target);
    // Return value.
    return new JsonResponse($result);
  }

}
