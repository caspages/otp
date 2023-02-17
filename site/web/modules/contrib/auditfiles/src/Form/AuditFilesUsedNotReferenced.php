<?php

namespace Drupal\auditfiles\Form;

use Drupal\Core\Form\ConfirmFormInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfirmFormHelper;
use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\auditfiles\ServiceAuditFilesUsedNotReferenced;
use Drupal\Core\Pager\PagerManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * File used but not referenced functionality.
 */
class AuditFilesUsedNotReferenced extends FormBase implements ConfirmFormInterface {
  use MessengerTrait;

  /**
   * The Config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactoryStorage;

  /**
   * The auditfiles.used_not_referenced service.
   *
   * @var \Drupal\auditfiles\ServiceAuditFilesUsedNotReferenced
   */
  protected $filesUsedNotReferenced;

  /**
   * The pager.manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class Constructor.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration service.
   * @param Drupal\auditfiles\ServiceAuditFilesUsedNotReferenced $files_used_not_referenced
   *   The auditfiles.used_not_referenced service.
   * @param Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager.manager service.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity_type.manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ServiceAuditFilesUsedNotReferenced $files_used_not_referenced, PagerManagerInterface $pager_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactoryStorage = $config_factory;
    $this->filesUsedNotReferenced = $files_used_not_referenced;
    $this->pagerManager = $pager_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('config.factory'), $container->get('auditfiles.used_not_referenced'), $container->get('pager.manager'), $container->get('entity_type.manager'));
  }

  /**
   * Widget Id.
   */
  public function getFormId() {
    return 'audit_files_used_not_referenced';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->t('This action cannot be undone.');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Confirm');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText() {
    return $this->t('Cancel');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormName() {
    return 'AuditFilesUsedNotReferenced';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('auditfiles.audit_files_usednotreferenced');
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t("Do you wan't to delete following record");
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactoryStorage->get('auditfiles.settings');
    $storage = &$form_state->getStorage();
    if (isset($storage['confirm'])) {
      $values = $form_state->getValue('files');
      $form['changelist'] = [
        '#prefix' => '<ul>',
        '#suffix' => '</ul>',
        '#tree' => TRUE,
      ];
      // Prepare the list of items to present to the user.
      if (!empty($values)) {
        foreach ($values as $file_id) {
          if (!empty($file_id)) {
            $file = $this->entityTypeManager->getStorage('file')->load($file_id);
            if (!empty($file)) {
              $form['changelist'][$file_id] = [
                '#type' => 'hidden',
                '#value' => $file_id,
                '#prefix' => '<li><strong>' . $file->getFilename() . '</strong> ' . $this->t('will be deleted from the file_usage table.'),
                '#suffix' => "</li>",
              ];
            }
          }
          else {
            unset($form_state->getValue('files')[$file_id]);
          }
        }
      }
      $form['#title'] = $this->t('Delete these items from the file_usage table?');
      $form['#attributes']['class'][] = 'confirmation';
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->getConfirmText(),
        '#button_type' => 'primary',
        '#submit' => ['::confirmSubmissionHandlerDeleteFile'],
      ];
      $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());
      if (!isset($form['#theme'])) {
        $form['#theme'] = 'confirm_form';
      }
      return $form;
    }
    $file_ids = $this->filesUsedNotReferenced->auditfilesUsedNotReferencedGetFileList();
    if (!empty($file_ids)) {
      foreach ($file_ids as $file_id) {
        $rows[$file_id] = $this->filesUsedNotReferenced->auditfilesUsedNotReferencedGetFileData($file_id);
      }
    }
    // Set up the pager.
    if (!empty($rows)) {
      $items_per_page = $config->get('auditfiles_report_options_items_per_page');
      if (!empty($items_per_page)) {
        $current_page = $this->pagerManager->createPager(count($rows), $items_per_page)->getCurrentPage();
        // Break the total data set into page sized chunks.
        $pages = array_chunk($rows, $items_per_page, TRUE);
      }
    }
    // Setup the record count and related messages.
    $maximum_records = $config->get('auditfiles_report_options_maximum_records');
    if (!empty($rows)) {
      if ($maximum_records > 0) {
        $file_count_message = $this->t('Found at least @count files in the file_usage table that are not referenced in content.');
      }
      else {
        $file_count_message = $this->t('Found @count files in the file_usage table that are not referenced in content.');
      }
      $form_count = $this->formatPlural(count($rows), $this->t('Found 1 file in the file_usage table that is not referenced in content.'), $file_count_message);
    }
    else {
      $form_count = $this->t('Found no files in the file_usage table that are not referenced in content.');
    }
    // Create the form table.
    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => $this->filesUsedNotReferenced->auditfilesUsedNotReferencedGetHeader(),
      '#empty' => $this->t('No items found.'),
      '#prefix' => '<div><em>' . $form_count . '</em></div>',
    ];
    // Add the data.
    if (!empty($rows) && !empty($pages)) {
      $form['files']['#options'] = $pages[$current_page];
    }
    elseif (!empty($rows)) {
      $form['files']['#options'] = $rows;
    }
    else {
      $form['files']['#options'] = [];
    }
    // Add any action buttons.
    if (!empty($rows)) {
      $form['actions'] = ['#type' => 'actions'];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete selected items from the file_usage table'),
        '#submit' => ['::submissionHandlerDeleteFile'],
      ];
      $form['pager'] = ['#type' => 'pager'];
    }
    return $form;
  }

  /**
   * Submit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}

  /**
   * Submit for confirmation.
   */
  public function submissionHandlerDeleteFile(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('files'))) {
      foreach ($form_state->getValue('files') as $file_id) {
        if (!empty($file_id)) {
          $storage = ['files' => $form_state->getValue('files'), 'confirm' => TRUE];
          $form_state->setStorage($storage);
          $form_state->setRebuild();
        }
      }
      if (!isset($storage)) {
        $this->messenger()->addError($this->t('No items were selected to operate on.'));
      }
    }
  }

  /**
   * Submit form after confirmation.
   */
  public function confirmSubmissionHandlerDeleteFile(array &$form, FormStateInterface $form_state) {
    batch_set($this->filesUsedNotReferenced->auditfilesUsedNotReferencedBatchDeleteCreateBatch($form_state->getValue('changelist')));
  }

}
