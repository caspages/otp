<?php

declare(strict_types=1);

namespace Drupal\editor_advanced_link\Plugin\CKEditor5Plugin;

use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableTrait;
use Drupal\ckeditor5\Plugin\CKEditor5PluginDefault;
use Drupal\ckeditor5\Plugin\CKEditor5PluginConfigurableInterface;
use Drupal\ckeditor5\Plugin\CKEditor5PluginElementsSubsetInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\EditorInterface;

/**
 * CKEditor 5 Advanced Link plugin.
 */
class AdvancedLink extends CKEditor5PluginDefault implements CKEditor5PluginConfigurableInterface, CKEditor5PluginElementsSubsetInterface {

  use CKEditor5PluginConfigurableTrait;

  /**
   * The default configuration for this plugin.
   *
   * @var string[][]
   */
  const DEFAULT_CONFIGURATION = [
    'enabled_attributes' => [],
  ];

  /**
   * All <a> attributes that this plugin supports.
   *
   * @var array
   */
  const SUPPORTED_ATTRIBUTES = [
    'aria-label' => TRUE,
    'title' => TRUE,
    'class' => TRUE,
    'id' => TRUE,
    'target' => ['_blank'],
    'rel' => TRUE,
  ];

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return static::DEFAULT_CONFIGURATION;
  }

  /**
   * Gets all valid choices for the "enabled_attributes" setting.
   *
   * @see editor_advanced_link.schema.yml
   *
   * @return string[]
   *   All valid choices.
   */
  public static function validChoices(): array {
    return array_keys(self::SUPPORTED_ATTRIBUTES);
  }

  /**
   * Gets all enabled attributes.
   *
   * @return string[]
   *   The values in the plugins.ckeditor5_link.enabled_attributes config.
   */
  private function getEnabledAttributes(): array {
    return $this->configuration['enabled_attributes'];
  }

  /**
   * {@inheritdoc}
   *
   * Form for choosing which additional <a> attributes are available.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['enabled_attributes'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Enabled attributes'),
      '#description' => $this->t('These are the attributes that will appear when creating or editing links.'),
    ];

    // UI labels corresponding to each of the supported attributes.
    $config_ui_labels = [
      'aria-label' => $this->t('ARIA label'),
      'title' => $this->t('Title'),
      'class' => $this->t('CSS classes'),
      'id' => $this->t('ID'),
      'target' => $this->t('Open in new window'),
      'rel' => $this->t('Link relationship'),
    ];
    assert(count(self::SUPPORTED_ATTRIBUTES) === count($config_ui_labels));

    foreach (array_keys(self::SUPPORTED_ATTRIBUTES) as $attribute) {
      $form['enabled_attributes'][$attribute] = [
        '#type' => 'checkbox',
        '#title' => $this->t('@label (<code>@attribute</code>)', [
          '@label' => $config_ui_labels[$attribute],
          '@attribute' => self::getAllowedHtmlForSupportedAttribute($attribute),
        ]),
        '#return_value' => $attribute,
      ];
      $form['enabled_attributes'][$attribute]['#default_value'] = in_array($attribute, $this->configuration['enabled_attributes'], TRUE);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Match the config schema structure at ckeditor5.plugin.editor_advanced_link_link.
    $form_value = $form_state->getValue('enabled_attributes');
    $config_value = array_values(array_filter($form_value));
    $form_state->setValue('enabled_attributes', $config_value);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['enabled_attributes'] = $form_state->getValue('enabled_attributes');
  }

  /**
   * {@inheritdoc}
   *
   * Filters the enabled attributes to those chosen in editor config.
   */
  public function getDynamicPluginConfig(array $static_plugin_config, EditorInterface $editor): array {
    $enabled_attributes = $this->getEnabledAttributes();
    $supported_attributes = $static_plugin_config['editorAdvancedLink']['options'];

    $config = [];
    if (in_array('target', $enabled_attributes, TRUE)) {
      $config['link']['decorators'][] = [
        'mode' => 'manual',
        'label' => $this->t('Open in new window'),
        'attributes' => [
          'target' => '_blank',
        ],
      ];
    }

    return $config + [
      'editorAdvancedLink' => [
        'options' => array_intersect($supported_attributes, $enabled_attributes),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getElementsSubset(): array {
    $subset = [];
    foreach ($this->getEnabledAttributes() as $attribute) {
      $subset[] = self::getAllowedHtmlForSupportedAttribute($attribute);
    }
    return $subset;
  }

  /**
   * Gets the allowed HTML string representation for a supported attribute.
   *
   * @param string $attribute
   *   One of self::SUPPORTED_ATTRIBUTES.
   *
   * @return string
   *   The corresponding allowed HTML string representation.
   */
  public static function getAllowedHtmlForSupportedAttribute(string $attribute): string {
    if (!array_key_exists($attribute, self::SUPPORTED_ATTRIBUTES)) {
      throw new \OutOfBoundsException();
    }

    $allowed_values = self::SUPPORTED_ATTRIBUTES[$attribute];
    if ($allowed_values === TRUE) {
      // For attributes for which any value can be created.
      return sprintf('<a %s>', $attribute);
    }
    else {
      // For attributes for which only a single value can be created.
      return sprintf('<a %s="%s">', $attribute, implode(' ', $allowed_values));
    }
  }

}
