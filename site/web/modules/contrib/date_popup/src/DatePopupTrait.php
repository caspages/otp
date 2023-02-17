<?php

namespace Drupal\date_popup;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Provides shared code between the Date and Datetime plugins.
 */
trait DatePopupTrait {

  /**
   * Applies the date storage format to default value.
   *
   * @param string $default_value
   *   The date value to be formatted.
   *
   * @return string
   *   The formatted value.
   */
  protected function setDefaultValue(string $default_value): string {
    if (!empty($default_value)) {
      $date = new DrupalDateTime($default_value);
      if (!$date->hasErrors()) {
        return $date->format(DateTimeItemInterface::DATE_STORAGE_FORMAT);
      }
    }
    return $default_value;
  }

  /**
   * Applies the HTML5 date popup to the views filter form.
   *
   * @param array &$form
   *   The form to apply it to.
   */
  protected function applyDatePopupToForm(array &$form): void {
    if (!empty($this->options['expose']['identifier'])) {
      $identifier = $this->options['expose']['identifier'];
      // Identify wrapper.
      $wrapper_key = $identifier . '_wrapper';
      if (isset($form[$wrapper_key])) {
        $element = &$form[$wrapper_key][$identifier];
      }
      else {
        $element = &$form[$identifier];
      }
      // Detect filters that are using min/max.
      if (isset($element['min'])) {
        $element['min']['#type'] = 'date';
        $element['max']['#type'] = 'date';
        if ($this->options['value']['type'] == 'offset') {
          $element['min']['#default_value'] = $this->setDefaultValue($element['min']['#default_value']);
          $element['max']['#default_value'] = $this->setDefaultValue($element['max']['#default_value']);
        }

        if (isset($element['value'])) {
          $element['value']['#type'] = 'date';
        }
      }
      else {
        $element['#type'] = 'date';
        $element['#default_value'] = $this->setDefaultValue($element['#default_value']);
      }
    }
  }

}
