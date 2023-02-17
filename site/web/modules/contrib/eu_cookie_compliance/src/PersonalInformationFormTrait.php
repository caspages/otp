<?php

namespace Drupal\eu_cookie_compliance;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Trait that implements PersonalInformationFormInterface.
 */
trait PersonalInformationFormTrait {

  use StringTranslationTrait;

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Consent storage manager service.
   *
   * @var \Drupal\eu_cookie_compliance\Plugin\ConsentStorageManagerInterface
   */
  protected $consentStorageManager;

  /**
   * Inject the GDPR checkbox into the form.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state that accompanies the form.
   */
  public function formInjectGdprCheckbox(array &$form, FormStateInterface $form_state) {
    $gdpr_checkbox = [];
    $gdpr_checkbox['eu_compliance_cookie'] = [
      '#type' => 'checkbox',
      '#title' => $this->getGdprWording(),
      '#required' => TRUE,
      // @todo Would be nice if we could query current consent storage to see
      // if the user has already agreed to this data collection.
    ];

    // Try to inject right before the "actions" element. Otherwise just prepend
    // it to the end.
    if (isset($form['actions'])) {
      $actions_index = array_search('actions', array_keys($form));
      $before = array_slice($form, 0, $actions_index, TRUE);
      $after = array_slice($form, $actions_index, NULL, TRUE);
      $form = $before + $gdpr_checkbox + $after;
    }
    else {
      $form += $gdpr_checkbox;
    }
  }

  /**
   * Process form submission in regards to GDPR checkbox.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state with the submitted values.
   */
  public function formSubmitGdprCheckbox(array $form, FormStateInterface $form_state) {
    if ($form_state->getValue('eu_compliance_cookie') && $this->getConsentStorage()) {
      $this->getConsentStorage()->registerConsent($this->getFormId());
    }
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  abstract public function getFormId();

  /**
   * Translated wording that should accompany the GDPR checkbox.
   *
   * @return string
   *   String for the checkbox.
   */
  protected function getGdprWording() {
    $popup_link = $this->getConfig('eu_cookie_compliance.settings')->get('popup_link');
    if (UrlHelper::isExternal($popup_link)) {
      $popup_link = Url::fromUri($popup_link);
    }
    else {
      $popup_link = $popup_link === '<front>' ? '/' : $popup_link;
      $popup_link = Url::fromUserInput($popup_link);
    }

    return $this->t('I accept processing of my personal data. For more information, read the <a href="@url">privacy policy</a>,', [
      '@url' => $popup_link->toString(),
    ]);
  }

  /**
   * Get a config with a given name.
   *
   * @param string $config_name
   *   Name of the config to get.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   Config object.
   */
  protected function getConfig($config_name) {
    return $this->configFactory ? $this->configFactory->get($config_name) : \Drupal::config($config_name);
  }

  /**
   * Get active consent storage.
   *
   * @return null|\Drupal\eu_cookie_compliance\Plugin\ConsentStorageBase
   *   Consent storage object or NULL if one is not configured/available.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  protected function getConsentStorage() {
    $storage_manager = $this->consentStorageManager ? $this->consentStorageManager : \Drupal::service('plugin.manager.eu_cookie_compliance.consent_storage');
    $active_storage = $this->getConfig('eu_cookie_compliance.settings')->get('consent_storage_method');
    if ($active_storage && $storage_manager->hasDefinition($active_storage)) {
      return $storage_manager->createInstance($active_storage);
    }

    return NULL;
  }

}
