<?php

namespace Drupal\eu_cookie_compliance\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Provides a base class for a consent storage.
 *
 * @see \Drupal\eu_cookie_compliance\Plugin\ConsentStorageInterface
 * @see \Drupal\eu_cookie_compliance\Plugin\ConsentStorageManager
 * @see \Drupal\eu_cookie_compliance\Plugin\ConsentStorageManagerInterface
 * @see plugin_api
 */
abstract class ConsentStorageBase extends PluginBase implements ConsentStorageInterface {

  /**
   * The config factory.
   *
   * Subclasses should use the self::config() method, which may be overridden to
   * address specific needs when loading config, rather than this property
   * directly. See \Drupal\Core\Form\ConfigFormBase::config() for an example of
   * this.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a ConsentStorageBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function description() {
    return $this->pluginDefinition['description'];
  }

  /**
   * Get status.
   *
   * @return bool
   *   Current status.
   */
  public function getStatus() {
    return TRUE;
  }

  /**
   * Get the current revision of the privacy policy node.
   *
   * @return bool|int
   *   Returns the latest revision ID of the curreny privacy policy node, or
   *   FALSE if no priacy policy exists.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getCurrentPolicyNodeRevision() {
    $config = $this->configFactory->get('eu_cookie_compliance.settings');
    $cookie_policy_link = $config->get('popup_link');
    $cookie_policy_drupal_path = \Drupal::service('path_alias.manager')->getPathByAlias($cookie_policy_link, \Drupal::languageManager()->getCurrentLanguage()->getId());
    if (substr($cookie_policy_drupal_path, 0, 6) === '/node/') {
      $node_id = explode('/', $cookie_policy_drupal_path)[2];
      /** @var \Drupal\node\Entity\Node $node */
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);

      // Ensure the node has been loaded before accessing any properties.
      if (!$node) {
        return FALSE;
      }

      return $node->getRevisionId();
    }
    return FALSE;
  }

  /**
   * Register consent.
   *
   * In addition to the parameters passed to the function, consider storing the
   * uid, ip address, timestamp and the current revision of the cookie policy.
   *
   * @param string $consent_type
   *   "banner" if the consent is given to the cookie banner or the form ID when
   *   consent is given on a form.
   *
   * @return bool
   *   Returns TRUE when the consent has been stored successfully, FALSE on
   *   error.
   */
  abstract public function registerConsent($consent_type);

}
