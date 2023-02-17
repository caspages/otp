<?php

namespace Drupal\Core\Entity\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for deleting an entity revision.
 *
 * @internal
 */
class RevisionDeleteForm extends ConfirmFormBase implements EntityFormInterface {

  /**
   * The entity operation.
   *
   * @var string
   */
  protected $operation;

  /**
   * The entity revision.
   *
   * @var \Drupal\Core\Entity\RevisionableInterface
   */
  protected $revision;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The entity bundle information.
   *
   * @var \Drupal\Core\Entity\EntityTypeBundleInfoInterface
   */
  protected $bundleInformation;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Creates a new RevisionDeleteForm instance.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInformation
   *   The bundle information.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(DateFormatterInterface $dateFormatter, EntityTypeManagerInterface $entityTypeManager, EntityTypeBundleInfoInterface $bundleInformation, MessengerInterface $messenger, TimeInterface $time, AccountInterface $currentUser) {
    $this->dateFormatter = $dateFormatter;
    $this->entityTypeManager = $entityTypeManager;
    $this->bundleInformation = $bundleInformation;
    $this->messenger = $messenger;
    $this->time = $time;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('date.formatter'),
      $container->get('entity_type.manager'),
      $container->get('entity_type.bundle.info'),
      $container->get('messenger'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseFormId() {
    return $this->revision->getEntityTypeId() . '_revision_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->revision->getEntityTypeId() . '_revision_delete';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->getEntity() instanceof RevisionLogInterface) {
      return $this->t('Are you sure you want to delete the revision from %revision-date?', [
        '%revision-date' => $this->dateFormatter->format($this->getEntity()->getRevisionCreationTime()),
      ]);
    }
    return $this->t('Are you sure you want to delete the revision?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    if ($this->getEntity()->getEntityType()->hasLinkTemplate('version-history')) {
      return $this->getEntity()->toUrl('version-history');
    }
    return $this->getEntity()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entityTypeId = $this->revision->getEntityTypeId();
    $entityStorage = $this->entityTypeManager->getStorage($entityTypeId);
    $entityStorage->deleteRevision($this->revision->getRevisionId());

    $bundleLabel = $this->getBundleLabel($this->revision);
    $messengerArgs = [
      '@type' => $bundleLabel ? $bundleLabel : $this->revision->getEntityType()->getLabel(),
      '%title' => $this->revision->label(),
    ];
    if ($this->revision instanceof RevisionLogInterface) {
      $messengerArgs['%revision-date'] = $this->dateFormatter->format($this->revision->getRevisionCreationTime());
      $this->messenger->addStatus($this->t('Revision from %revision-date of @type %title has been deleted.', $messengerArgs));
    }
    else {
      $this->messenger->addStatus($this->t('Revision of @type %title has been deleted.', $messengerArgs));
    }

    $this->logger($this->revision->getEntityType()->getProvider())->notice('@type: deleted %title revision %revision.', [
      '@type' => $this->revision->bundle(),
      '%title' => $this->revision->label(),
      '%revision' => $this->revision->getRevisionId(),
    ]);

    // When there is one remaining revision or more, redirect to the version
    // history page.
    if ($this->revision->hasLinkTemplate('version-history')) {
      $query = $this->entityTypeManager->getStorage($entityTypeId)->getQuery();
      $remainingRevisions = $query
        ->accessCheck(FALSE)
        ->allRevisions()
        ->condition($this->revision->getEntityType()->getKey('id'), $this->revision->id())
        ->count()
        ->execute();
      $versionHistoryUrl = $this->revision->toUrl('version-history');
      if ($remainingRevisions && $versionHistoryUrl->access($this->currentUser())) {
        $form_state->setRedirectUrl($versionHistoryUrl);
      }
    }

    if (!$form_state->getRedirect()) {
      $canonicalUrl = $this->revision->toUrl();
      if ($canonicalUrl->access($this->currentUser())) {
        $form_state->setRedirectUrl($canonicalUrl);
      }
    }
  }

  /**
   * Returns the bundle label of an entity.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   The entity.
   *
   * @return string|null
   *   The bundle label.
   */
  protected function getBundleLabel(RevisionableInterface $entity): ?string {
    $bundleInfo = $this->bundleInformation->getBundleInfo($entity->getEntityTypeId());
    return isset($bundleInfo[$entity->bundle()]['label']) ? (string) $bundleInfo[$entity->bundle()]['label'] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setOperation($operation) {
    $this->operation = $operation;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOperation() {
    return $this->operation;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntity() {
    return $this->revision;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntity(EntityInterface $entity) {
    assert($entity instanceof RevisionableInterface);
    $this->revision = $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityFromRouteMatch(RouteMatchInterface $route_match, $entity_type_id) {
    return $route_match->getParameter($entity_type_id . '_revision');
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    return $this->revision;
  }

  /**
   * {@inheritdoc}
   *
   * The save() method is not used in RevisionDeleteForm. This
   * overrides the default implementation that saves the entity.
   *
   * Confirmation forms should override submitForm() instead for their logic.
   */
  public function save(array $form, FormStateInterface $form_state) {}

  /**
   * {@inheritdoc}
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEntityTypeManager(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  protected function currentUser() {
    return $this->currentUser;
  }

}
