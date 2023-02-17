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
use Drupal\auditfiles\ServiceAuditFilesUsedNotManaged;
use Drupal\Core\Pager\PagerManagerInterface;

/**
 * Class for file used but not managed.
 */
class AuditFilesUsedNotManaged extends FormBase implements ConfirmFormInterface {

  use MessengerTrait;

  /**
   * The Config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactoryStorage;

  /**
   * The auditfiles.managed_not_used service.
   *
   * @var \Drupal\auditfiles\ServiceAuditFilesUsedNotManaged
   */
  protected $filesUsedNotManaged;

  /**
   * The pager.manager service.
   *
   * @var \Drupal\Core\Pager\PagerManagerInterface
   */
  protected $pagerManager;

  /**
   * Class constructor.
   *
   * @param Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration service.
   * @param Drupal\auditfiles\ServiceAuditFilesUsedNotManaged $files_used_not_managed
   *   The auditfiles.used_not_managed service.
   * @param Drupal\Core\Pager\PagerManagerInterface $pager_manager
   *   The pager.manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ServiceAuditFilesUsedNotManaged $files_used_not_managed, PagerManagerInterface $pager_manager) {
    $this->configFactoryStorage = $config_factory;
    $this->filesUsedNotManaged = $files_used_not_managed;
    $this->pagerManager = $pager_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('auditfiles.used_not_managed'),
      $container->get('pager.manager')
    );
  }

  /**
   * Widget Id.
   */
  public function getFormId() {
    return 'audit_files_used_not_managed';
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
    return 'AuditFilesUsedNotManaged';
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('auditfiles.audit_files_usednotmanaged');
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
            $form['changelist'][$file_id] = [
              '#type' => 'hidden',
              '#value' => $file_id,
              '#prefix' => '<li>' . $this->t('File ID') . ' <strong>' . $file_id . '</strong> ' . $this->t('will be deleted from the file_usage table.'),
              '#suffix' => "</li>\n",
            ];
          }
          else {
            unset($form_state->getValue('files')[$file_id]);
          }
        }
      }
      $form['#title'] = $this->t('Delete these items from the file_usage table?');
      $form['#attributes']['class'][] = 'confirmation';
      $form['actions'] = [
        '#type' => 'actions',
      ];
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
    $file_ids = $this->filesUsedNotManaged->auditfilesUsedNotManagedGetFileList();
    if (!empty($file_ids)) {
      foreach ($file_ids as $file_id) {
        $rows[$file_id] = $this->filesUsedNotManaged->auditfilesUsedNotManagedGetFileData($file_id);
      }
    }
    // Set up the pager.
    if (!empty($rows)) {
      $items_per_page = $config->get('auditfiles_report_options_items_per_page');
      if (!empty($items_per_page)) {
        $current_page = $this->pagerManager->createPager(count($rows), $items_per_page)->getCurrentPage();
        $pages = array_chunk($rows, $items_per_page, TRUE);
      }
    }

    // Setup the record count and related messages.
    $maximum_records = $config->get('auditfiles_report_options_maximum_records');
    if (!empty($rows)) {
      if ($maximum_records > 0) {
        $file_count_message = $this->t('Found at least @count files in the file_usage table that are not in the file_managed table.');
      }
      else {
        $file_count_message = $this->t('Found @count files in the file_usage table that are not in the file_managed table.');
      }
      $form_count = $this->formatPlural(count($rows), $this->t('Found 1 file in the file_usage table that is not in the file_managed table.'), $file_count_message);
    }
    else {
      $form_count = $this->t('Found no files in the file_usage table that are not in the file_managed table.');
    }
    // Create the form table.
    $form['files'] = [
      '#type' => 'tableselect',
      '#header' => $this->filesUsedNotManaged->auditfilesUsedNotManagedGetHeader(),
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Submit handler for confirmation.
   */
  public function submissionHandlerDeleteFile(array &$form, FormStateInterface $form_state) {
    if (!empty($form_state->getValue('files'))) {
      foreach ($form_state->getValue('files') as $file_id) {
        if (!empty($file_id)) {
          $storage = [
            'files' => $form_state->getValue('files'),
            'confirm' => TRUE,
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
   * Submit handler for confirmation.
   */
  public function confirmSubmissionHandlerDeleteFile(array &$form, FormStateInterface $form_state) {
    batch_set($this->filesUsedNotManaged->auditfilesUsedNotManagedBatchDeleteCreateBatch($form_state->getValue('changelist')));
  }

}
