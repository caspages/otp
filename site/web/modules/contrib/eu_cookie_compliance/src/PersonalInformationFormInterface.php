<?php

namespace Drupal\eu_cookie_compliance;

use Drupal\Core\Form\FormStateInterface;

/**
 * Interface to describe forms that collect personal data.
 */
interface PersonalInformationFormInterface {

  /**
   * Inject the GDPR checkbox into the form.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state that accompanies the form.
   */
  public function formInjectGdprCheckbox(array &$form, FormStateInterface $form_state);

  /**
   * Process form submission in regards to GDPR checkbox.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state with the submitted values.
   */
  public function formSubmitGdprCheckbox(array $form, FormStateInterface $form_state);

}
