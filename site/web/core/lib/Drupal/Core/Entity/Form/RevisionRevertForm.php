<?php

namespace Drupal\Core\Entity\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for reverting an entity revision.
 *
 * @internal
 */
class RevisionRevertForm extends ConfirmFormBase implements EntityFormInterface {

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
   * Creates a new RevisionRevertForm instance.
   *
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $bundleInformation
   *   The bundle information.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   */
  public function __construct(DateFormatterInterface $dateFormatter, EntityTypeBundleInfoInterface $bundleInformation, MessengerInterface $messenger, TimeInterface $time, AccountInterface $currentUser) {
    $this->dateFormatter = $dateFormatter;
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
    return $this->revision->getEntityTypeId() . '_revision_revert';
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return $this->revision->getEntityTypeId() . '_revision_revert';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    if ($this->getEntity() instanceof RevisionLogInterface) {
      return $this->t('Are you sure you want to revert to the revision from %revision-date?', ['%revision-date' => $this->dateFormatter->format($this->getEntity()->getRevisionCreationTime())]);
    }
    return $this->t('Are you sure you want to revert the revision?');
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
    return $this->t('Revert');
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#submit'] = [
      '::submitForm',
      '::save',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $revisionId = $this->revision->getRevisionId();
    $revisionLabel = $this->revision->label();
    $bundleLabel = $this->getBundleLabel($this->revision);
    if ($this->revision instanceof RevisionLogInterface) {
      $originalRevisionTimestamp = $this->revision->getRevisionCreationTime();
    }

    $this->prepareRevision($this->revision, $form_state);

    if ($this->revision instanceof RevisionLogInterface) {
      $date = $this->dateFormatter->format($originalRevisionTimestamp);
      $this->messenger->addMessage($this->t('@type %title has been reverted to the revision from %revision-date.', [
        '@type' => $bundleLabel,
        '%title' => $revisionLabel,
        '%revision-date' => $date,
      ]));
    }
    else {
      $this->messenger->addMessage($this->t('@type %title has been reverted.', [
        '@type' => $bundleLabel,
        '%title' => $revisionLabel,
      ]));
    }

    $this->logger($this->revision->getEntityType()->getProvider())->notice('@type: reverted %title revision %revision.', [
      '@type' => $this->revision->bundle(),
      '%title' => $revisionLabel,
      '%revision' => $revisionId,
    ]);

    $versionHistoryUrl = $this->revision->toUrl('version-history');
    if ($versionHistoryUrl->access($this->currentUser())) {
      $form_state->setRedirectUrl($versionHistoryUrl);
    }

    if (!$form_state->getRedirect()) {
      $canonicalUrl = $this->revision->toUrl();
      if ($canonicalUrl->access($this->currentUser())) {
        $form_state->setRedirectUrl($canonicalUrl);
      }
    }
  }

  /**
   * Prepares a revision to be reverted.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $revision
   *   The revision to be reverted.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form.
   */
  protected function prepareRevision(RevisionableInterface $revision, FormStateInterface $formState): void {
    $revision->setNewRevision();
    $revision->isDefaultRevision(TRUE);
    assert($revision instanceof TranslatableRevisionableInterface);
    // Apply the same behavior in the node revisions.
    $revision->setRevisionTranslationAffected(TRUE);
    $time = $this->time->getRequestTime();
    if ($revision instanceof EntityChangedInterface) {
      $revision->setChangedTime($time);
    }
    if ($revision instanceof RevisionLogInterface) {
      $originalRevisionTimestamp = $revision->getRevisionCreationTime();
      $date = $this->dateFormatter->format($originalRevisionTimestamp);
      $revision->setRevisionLogMessage($this->t('Copy of the revision from %date.', ['%date' => $date]));
      $revision->setRevisionCreationTime($time);
      $revision->setRevisionUserId($this->currentUser()->id());
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
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->revision->save();
  }

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
