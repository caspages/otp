<?php

declare(strict_types=1);

namespace Drupal\block_content_revision_ui\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Settings for Block Content Revision UI.
 *
 * @internal
 */
final class BlockContentRevisionUiSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'block_content_revision_ui_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['block_content_revision_ui.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('block_content_revision_ui.settings');

    $form['allow_revert_current_revision'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow reverting current revision'),
      '#default_value' => !empty($config->get('allow_revert_current_revision')),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('block_content_revision_ui.settings')
      ->set('allow_revert_current_revision', $form_state->getValue('allow_revert_current_revision'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
