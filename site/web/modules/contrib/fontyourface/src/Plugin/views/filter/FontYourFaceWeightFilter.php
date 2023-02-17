<?php

namespace Drupal\fontyourface\Plugin\views\filter;

use Drupal\views\Plugin\views\filter\StringFilter;
use Drupal\Core\Form\FormStateInterface;

/**
 * Filter handler which allows to search based on font weight.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("fontyourface_font_weight")
 */
class FontYourFaceWeightFilter extends StringFilter {

  /**
   * Exposed filter options.
   *
   * @var bool
   */
  protected $alwaysMultiple = TRUE;

  /**
   * Provide simple equality operator.
   */
  public function operators() {
    return [
      '=' => [
        'title' => $this->t('Is equal to'),
        'short' => $this->t('='),
        'method' => 'opEqual',
        'values' => 1,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $options = [
      'All' => '- Any -',
      '100' => $this->t('100 (Thin)'),
      '200' => $this->t('200 (Extra Light, Ultra Light)'),
      '300' => $this->t('300 (Light)'),
      'normal' => $this->t('400 (Normal, Book, Regular)'),
      '500' => $this->t('500 (Medium)'),
      '600' => $this->t('600 (Semi Bold, Demi Bold)'),
      '700' => $this->t('700 (Bold)'),
      '800' => $this->t('800 (Extra Bold, Ultra Bold)'),
      '900' => $this->t('900 (Black, Heavy)'),
    ];

    $form['value'] = [
      '#type' => 'select',
      '#title' => $this->t('Font Weight'),
      '#options' => $options,
      '#default_value' => $this->value,
    ];

    if ($exposed = $form_state->get('exposed')) {
      $identifier = $this->options['expose']['identifier'];
      $user_input = $form_state->getUserInput();
      if (!isset($user_input[$identifier])) {
        $user_input[$identifier] = $this->value;
        $form_state->setUserInput($user_input);
      }
    }
  }

}
