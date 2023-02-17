<?php

namespace Drupal\eu_cookie_compliance\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\eu_cookie_compliance\Plugin\EuCcClearCache;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Category form for the module.
 */
class CookieCategoryForm extends EntityForm {

  /**
   * The Cookie Category Storage Manager.
   *
   * @var \Drupal\eu_cookie_compliance\CategoryStorageManager
   */
  protected $categoryStorageManager;

  /**
   * EUCC Clear Cache Service.
   *
   * @var \Drupal\eu_cookie_compliance\Plugin\EuCcClearCache
   */
  protected $euccClearCache;

  /**
   * Constructs a CookieCategoryForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entityTypeManager.
   * @param \Drupal\eu_cookie_compliance\Plugin\EuCcClearCache $eucc_clear_cache
   *   The EU CC Clear Cache service.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, EuCcClearCache $eucc_clear_cache) {
    $this->entityTypeManager = $entityTypeManager;
    $this->categoryStorageManager = $entityTypeManager->getStorage('cookie_category');
    $this->euccClearCache = $eucc_clear_cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('eu_cookie_compliance.clear_cache')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $cookie_category = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $cookie_category->label(),
      '#description' => $this->t("The name that will be shown to the website visitor."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $cookie_category->id(),
      '#machine_name' => [
        'exists' => '\Drupal\eu_cookie_compliance\Entity\CookieCategory::load',
      ],
      '#changeable_state' => !$cookie_category->isNew(),
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $cookie_category->get('description'),
      '#description' => $this->t("The description that will be shown to the website visitor."),
      '#required' => FALSE,
    ];

    $form['checkbox_default_state'] = [
      '#type' => 'radios',
      '#title' => $this->t('Checkbox default state'),
      '#description' => $this->t("Determines the default state of this category's selection checkbox on the cookie consent popup."),
      '#default_value' => $cookie_category->get('checkbox_default_state') ?: 'unchecked',
      '#options' => $this->categoryStorageManager->getCheckboxDefaultStateOptionsList(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->euccClearCache->clearCache();
    $cookie_category = $this->entity;
    $status = $cookie_category->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addMessage($this->t('Created the %label Cookie category.', [
            '%label' => $cookie_category->label(),
          ]));
        break;

      default:
        $this->messenger()
          ->addMessage($this->t('Saved the %label Cookie category.', [
            '%label' => $cookie_category->label(),
          ]));
    }
    $form_state->setRedirectUrl($cookie_category->toUrl('collection'));
  }

  /**
   * {@inheritdoc}
   */
  protected function copyFormValuesToEntity(EntityInterface $entity, array $form, FormStateInterface $form_state) {
    parent::copyFormValuesToEntity($entity, $form, $form_state);
    // There is no weight on the edit form. Fetch all configurable cookie
    // categories ordered by weight and set the new cookie to be placed
    // after them.
    if (empty($entity->getWeight())) {
      $entity->setWeight($this->categoryStorageManager->getCookieCategoryNextWeight());
    }
  }

}
