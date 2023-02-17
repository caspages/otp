<?php

namespace Drupal\auditfiles;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * List all methods used in files used not managed functionality.
 */
class ServiceAuditFilesUsedNotReferenced {

  use MessengerTrait;

  /**
   * The Translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The Configuration Factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The entityFieldManager connection.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * Define constructor.
   */
  public function __construct(TranslationInterface $translation, ConfigFactory $config_factory, Connection $connection, EntityFieldManager $entity_field_manager) {
    $this->stringTranslation = $translation;
    $this->configFactory = $config_factory;
    $this->connection = $connection;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Retrieves the file IDs to operate on.
   *
   * @return array
   *   The file IDs.
   */
  public function auditfilesUsedNotReferencedGetFileList() {

    $connection = $this->connection;
    $config = $this->configFactory->get('auditfiles.settings');
    $maximum_records = $config->get('auditfiles_report_options_maximum_records');
    $query = $connection->select('file_usage', 'fu')
      ->fields('fu', ['fid'])
      ->distinct();
    if ($maximum_records > 0) {
      $query->range(0, $maximum_records);
    }
    $files_in_file_usage = $query->execute()->fetchCol();
    $files_in_fields = $field_data = [];
    $fields[] = $this->entityFieldManager->getFieldMapByFieldType('image');
    $fields[] = $this->entityFieldManager->getFieldMapByFieldType('file');
    $count = 0;
    foreach ($fields as $value) {
      foreach ($value as $table_prefix => $entity_type) {
        foreach ($entity_type as $key1 => $value1) {
          $field_data[$count]['table'] = $table_prefix . '__' . $key1;
          $field_data[$count]['column'] = $key1 . '_target_id';
          $count++;
        }
      }
    }
    foreach ($field_data as $value) {
      $table = $value['table'];
      $column = $value['column'];
      if ($this->connection->schema()->tableExists($table)) {
        $query = "SELECT t.$column FROM {{$table}} AS t INNER JOIN {file_usage} AS f ON f.fid = t.$column";
        $result = $connection->query($query)->fetchCol();
        foreach ($result as $fid) {
          if (in_array($fid, $files_in_file_usage)) {
            $files_in_fields[] = $fid;
          }
        }
      }
    }
    return array_diff($files_in_file_usage, $files_in_fields);
  }

  /**
   * Retrieves information about an individual file from the database.
   *
   * @param int $file_id
   *   The ID of the file to prepare for display.
   *
   * @return array
   *   The row for the table on the report, with the file's
   *   information formatted for display.
   */
  public function auditfilesUsedNotReferencedGetFileData($file_id) {
    $connection = $this->connection;
    $file_managed = $connection->query("SELECT * FROM {file_managed} fm WHERE fid = $file_id")->fetchObject();
    if (empty($file_managed)) {
      $url = Url::fromUri('internal:/admin/reports/auditfiles/usednotmanaged');
      $result_link = Link::fromTextAndUrl($this->stringTranslation->translate('Used not managed'), $url)->toString();
      $row = [
        'fid' => $this->stringTranslation->translate('This file is not listed in the file_managed table. See the "%usednotmanaged" report.', ['%usednotmanaged' => $result_link]),
        'uri' => '',
        'usage' => '',
      ];
    }
    else {
      $usage = '<ul>';
      $results = $connection->query("SELECT * FROM {file_usage} WHERE fid = $file_id");
      foreach ($results as $file_usage) {
        $used_by = $file_usage->module;
        $type = $file_usage->type;
        $url = Url::fromUri('internal:/node/' . $file_usage->id);
        $result_link = Link::fromTextAndUrl($file_usage->id, $url)->toString();
        $used_in = ($file_usage->type == 'node') ? $result_link : $file_usage->id;
        $times_used = $file_usage->count;
        $usage .= '<li>' . $this->stringTranslation->translate(
          'Used by module: %used_by, as object type: %type, in content ID: %used_in; Times used: %times_used',
          [
            '%used_by' => $used_by,
            '%type' => $type,
            '%used_in' => $used_in,
            '%times_used' => $times_used,
          ]
        ) . '</li>';
      }
      $usage .= '</ul>';
      $usage = new FormattableMarkup($usage, []);
      $row = ['fid' => $file_id, 'uri' => $file_managed->uri, 'usage' => $usage];
    }
    return $row;
  }

  /**
   * Returns the header to use for the display table.
   *
   * @return array
   *   The header to use.
   */
  public function auditfilesUsedNotReferencedGetHeader() {
    return [
      'fid' => ['data' => $this->stringTranslation->translate('File ID')],
      'uri' => ['data' => $this->stringTranslation->translate('File URI')],
      'usage' => ['data' => $this->stringTranslation->translate('Usages')],
    ];
  }

  /**
   * Creates the batch for deleting files from the file_usage table.
   */
  public function auditfilesUsedNotReferencedBatchDeleteCreateBatch(array $fileids) {
    $batch['error_message'] = $this->stringTranslation->translate('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesUsedNotReferencedBatchFinishBatch';
    $batch['progress_message'] = $this->stringTranslation->translate('Completed @current of @total operations.');
    $batch['title'] = $this->stringTranslation->translate('Deleting files from the file_usage table');
    $operations = $file_ids = [];
    foreach ($fileids as $file_id) {
      if ($file_id != 0) {
        $file_ids[] = $file_id;
      }
    }
    foreach ($file_ids as $file_id) {
      $operations[] = ['\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesUsedNotReferencedBatchDeleteProcessBatch', [$file_id]];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Deletes the specified file from the file_usage table.
   *
   * @param int $file_id
   *   The ID of the file to delete from the database.
   */
  public function auditfilesUsedNotReferencedBatchDeleteProcessFile($file_id) {
    $connection = $this->connection;
    $num_rows = $connection->delete('file_usage')->condition('fid', $file_id)->execute();
    if (empty($num_rows)) {
      $this->messenger()->addWarning($this->stringTranslation->translate('There was a problem deleting the record with file ID %fid from the file_usage table. Check the logs for more information.', ['%fid' => $file_id]));
    }
    else {
      $this->messenger()->addStatus($this->stringTranslation->translate('Sucessfully deleted File ID : %fid from the file_usage table.', ['%fid' => $file_id]));
    }
  }

}
