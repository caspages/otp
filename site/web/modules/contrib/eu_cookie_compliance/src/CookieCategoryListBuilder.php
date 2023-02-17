<?php

namespace Drupal\eu_cookie_compliance;

use Drupal\Core\Config\Entity\DraggableListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a listing of Cookie category entities.
 */
class CookieCategoryListBuilder extends DraggableListBuilder {

  /**
   * The entity storage class.
   *
   * @var \Drupal\eu_cookie_compliance\CategoryStorageManager
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'eu_cookie_compliance_category_overview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['sorter'] = '';
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['description'] = $this->t('Description');
    $header['checkbox_default_state'] = $this->t('Checkbox default state');

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    $mapping = $this->storage->getCheckboxDefaultStateOptionsList();
    $row['sorter'] = ['#markup' => ''];
    $row['label'] = $entity->label();
    $row['id'] = ['#markup' => $entity->id()];
    $row['description'] = ['#markup' => $entity->get('description')];
    $row['checkbox_default_state'] = ['#markup' => isset($mapping[$entity->get('checkbox_default_state')]) ? $mapping[$entity->get('checkbox_default_state')] : $mapping['unchecked']];

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $form['actions']['submit']['#value'] = $this->t('Save configuration');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    \Drupal::service('eu_cookie_compliance.clear_cache')->clearCache();
    parent::submitForm($form, $form_state);
  }

}
