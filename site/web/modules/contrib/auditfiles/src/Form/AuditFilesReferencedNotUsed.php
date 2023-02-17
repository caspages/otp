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
use Drupal\auditfiles\ServiceAuditFilesReferencedNotUsed;
use Drupal\Core\Pager\PagerManagerInterface;

/**
 * Form for Files referenced not used functionality.
 */
class AuditFilesReferencedNotUsed extends FormBase implements ConfirmFormInterface {

  use MessengerTrait;

  /**
   * The Config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactoryStorage;

  /**
   * The auditfiles.referenced_not_used service.
   *
   * @var Drupal\auditfiles\ServiceAuditFilesReferencedNotUsed
   */
  protected $filesReferencedNotUsed;

  /**
   * The pager.manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * Class Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration service.
   * @param \Drupal\auditfiles\ServiceAuditFilesReferencedNotUsed $files_referenced_not_used
   *   The auditfiles.referenced_not_used service.
   * @param \Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager.manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ServiceAuditFilesReferencedNotUsed $files_referenced_not_used, PagerManagerInterface $pager_manager) {
    $this->configFactoryStorage = $config_factory;
    $this->filesReferencedNotUsed = $files_referenced_not_used;
    $this->pagerManager = $pager_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('auditfiles.referenced_not_used'),
      $container->get('pager.manager')
    );
  }

  /**
   * Widget Id.
   */
  public function getFormId() {
    return 'audit_files_referenced_not_used';
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
    return 'AuditFilesReferencedNotUsed';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('auditfiles.audit_files_referencednotused');
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
      if (!empty($values)) {
        foreach ($values as $reference_id) {
          if (!empty($reference_id)) {
            $reference_id_parts = explode('.', $reference_id);
            if ($storage['op'] == 'add') {
              $message = $this->t('will be added to the file_usage table.');
            }
            elseif ($storage['op'] == 'delete') {
              $message = $this->t('will be deleted from the content.');
            }
            $form['changelist'][$reference_id] = [
              '#type' => 'hidden',
              '#value' => $reference_id,
              '#prefix' => '<li>' . $this->t('File ID') . ' <strong>' . $reference_id_parts[4] . '</strong> ' . $message,
              '#suffix' => "</li>\n",
            ];
          }
          else {
            unset($form_state->getValue('files')[$reference_id]);
          }
        }
      }
      if ($storage['op'] == 'add') {
        $form['#title'] = $this->t('Add these files to the database?');
      }
      elseif ($storage['op'] == 'delete') {
        $form['#title'] = $this->t('Delete these files from the server?');
      }
      $form['actions'] = [
        '#type' => 'actions',
      ];
      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->getConfirmText(),
        '#button_type' => 'primary',
        '#submit' => ['::confirmSubmissionHandler'],
      ];
      $form['actions']['cancel'] = ConfirmFormHelper::buildCancelLink($this, $this->getRequest());
      if (!isset($form['#theme'])) {
        $form['#theme'] = 'confirm_form';
      }
      return $form;
    }
    $file_data = $this->filesReferencedNotUsed->auditfilesReferencedNotUsedGetFileList();
    if (!empty($file_data)) {
      foreach ($file_data as $reference_id => $row_data) {
        $rows[$reference_id] = $this->filesReferencedNotUsed->auditfilesReferencedNotUsedGetFileData($row_data);
      }
    }
    if (!empty($rows)) {
      $items_per_page = $config->get('auditfiles_report_options_items_per_page');
      if (!empty($items_per_page)) {
        $current_page = $this->pagerManager->createPager(count($rows), $items_per_page)->getCurrentPage();
        $pages = array_chunk($rows, $items_per_page, TRUE);
      }
    }
    $maximum_records = $config->get('auditfiles_report_options_maximum_records');
    if (!empty($rows)) {
      if ($maximum_records > 0) {
        $file_count_message = $this->t('Found at least @count files referenced in content that are not in the file_usage table.');
      }
      else {
        $file_count_message = $this->t('Found @count files referenced in content that are not in the file_usage table.');
      }
      $form_count = $this->formatPlural(count($rows), $this->t('Found 1 file referenced in content that is not in the file_usage table.'), $file_count_message);
    }
    else {
      $form_count = $this->t('Found no files referenced in content that are not in the file_usage table.');
    }
    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => $this->filesReferencedNotUsed->auditfilesReferencedNotUsedGetHeader(),
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
      $form['actions']['add'] = [
        '#type' => 'submit',
        '#value' => $this->t('Add selected items to the file_usage table'),
        '#submit' => ['::submissionHandlerAddToFile'],
      ];
      $text = $this->t('or');
      $form['actions']['markup'] = [
        '#markup' => '&nbsp;' . $text . '&nbsp;',
      ];
      $form['actions']['delete'] = [
        '#type' => 'submit',
        '#value' => $this->t('Delete selected references'),
        '#submit' => ['::submissionHandlerDeleteFromFileUsage'],
      ];
      $form['pager'] = ['#type' => 'pager'];
    }
    return $form;
  }

  /**
   * Submit form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Submit form.
   */
  public function submissionHandlerAddToFile(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('files'))) {
      foreach ($form_state->getValue('files') as $file_id) {
        if (!empty($file_id)) {
          $storage = [
            'files' => $form_state->getValue('files'),
            'confirm' => TRUE,
            'op' => 'add',
          ];
          $form_state->setStorage($storage);
          $form_state->setRebuild();
        }
      }
      if (!isset($storage)) {
        $this->messenger()->addError(
          $this->t('No items were selected to operate on.')
        );
      }
    }
  }

  /**
   * Submit form.
   */
  public function submissionHandlerDeleteFromFileUsage(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('files'))) {
      foreach ($form_state->getValue('files') as $file_id) {
        if (!empty($file_id)) {
          $storage = [
            'files' => $form_state->getValue('files'),
            'confirm' => TRUE,
            'op' => 'delete',
          ];
          $form_state->setStorage($storage);
          $form_state->setRebuild();
        }
      }
      if (!isset($storage)) {
        $this->messenger()->addError(
          $this->t('No items were selected to operate on.')
        );
      }
    }
  }

  /**
   * Delete record from files.
   */
  public function confirmSubmissionHandler(array &$form, FormStateInterface $form_state) {
    $storage = &$form_state->getStorage();
    if ($storage['op'] == 'add') {
      batch_set($this->filesReferencedNotUsed->auditfilesReferencedNotUsedBatchAddCreateBatch($form_state->getValue('changelist')));
    }
    else {
      batch_set($this->filesReferencedNotUsed->auditfilesReferencedNotUsedBatchDeleteCreateBatch($form_state->getValue('changelist')));
    }
  }

}
