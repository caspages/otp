<?php

declare(strict_types=1);

namespace Drupal\editor_advanced_link\Plugin\CKEditor4To5Upgrade;

use Drupal\ckeditor5\HTMLRestrictions;
use Drupal\ckeditor5\Plugin\CKEditor4To5UpgradePluginInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\editor_advanced_link\Plugin\CKEditor5Plugin\AdvancedLink as CKEditor5Plugin;
use Drupal\filter\FilterFormatInterface;

/**
 * Provides the CKEditor 4 to 5 upgrade for Advanced Link.
 *
 * @CKEditor4To5Upgrade(
 *   id = "advanced_link",
 *   cke4_buttons = {},
 *   cke4_plugin_settings = {},
 *   cke5_plugin_elements_subset_configuration = {
 *    "editor_advanced_link_link",
 *   }
 * )
 */
class AdvancedLink extends PluginBase implements CKEditor4To5UpgradePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4ToolbarButtonToCKEditor5ToolbarItem(string $cke4_button, HTMLRestrictions $text_format_html_restrictions): ?array {
    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function mapCKEditor4SettingsToCKEditor5Configuration(string $cke4_plugin_id, array $cke4_plugin_settings): ?array {
    throw new \OutOfBoundsException();
  }

  /**
   * {@inheritdoc}
   */
  public function computeCKEditor5PluginSubsetConfiguration(string $cke5_plugin_id, FilterFormatInterface $text_format): ?array {
    switch ($cke5_plugin_id) {
      case 'editor_advanced_link_link':
        $restrictions = $text_format->getHtmlRestrictions();
        assert($restrictions === FALSE || array_key_exists('a', $restrictions['allowed']), 'This should only be called if this plugin is actually enabled, which requires <a> to be allowed.');
        // Enable all attributes when there are no HTML restrictions, or when
        // all attributes are allowed on <a>.
        if ($restrictions === FALSE || $restrictions['allowed']['a'] === TRUE) {
          return ['enabled_attributes' => array_keys(CKEditor5Plugin::SUPPORTED_ATTRIBUTES)];
        }

        // Otherwise, only enable attributes that allowed by the restrictions.
        $configuration = [];
        foreach (array_keys(CKEditor5Plugin::SUPPORTED_ATTRIBUTES) as $attribute) {
          $a_allowed_attributes = $restrictions['allowed']['a'] ?: [];
          // Check whether the attribute is allowed.
          // @see \Drupal\filter\Plugin\FilterInterface::getHTMLRestrictions()
          if (array_key_exists($attribute, $a_allowed_attributes) && $a_allowed_attributes[$attribute] === TRUE) {
            $configuration['enabled_attributes'][] = $attribute;
          }
          // Special case: the `target` attribute: not every attribute value
          // needs to be allowed; editor_advanced_link only allows setting the
          // value `_blank`.
          if ($attribute === 'target') {
            if (array_key_exists('target', $a_allowed_attributes) && is_array($a_allowed_attributes['target']) && array_key_exists('_blank', $a_allowed_attributes['target'])) {
              $configuration['enabled_attributes'][] = $attribute;
            }
          }
        }
        return $configuration;

      default:
        throw new \OutOfBoundsException();
    }
  }

}
