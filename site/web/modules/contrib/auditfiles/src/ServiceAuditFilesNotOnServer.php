<?php

namespace Drupal\auditfiles;

use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\File\FileSystemInterface;

/**
 * Providing the service that used in not in database functionality.
 */
class ServiceAuditFilesNotOnServer {

  use StringTranslationTrait;
  use MessengerTrait;

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
   * The Date Formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * The File System service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Define constructor for string translation.
   */
  public function __construct(TranslationInterface $translation, ConfigFactory $config_factory, Connection $connection, DateFormatter $date_formatter, FileSystemInterface $file_system) {
    $this->stringTranslation = $translation;
    $this->configFactory = $config_factory;
    $this->connection = $connection;
    $this->dateFormatter = $date_formatter;
    $this->fileSystem = $file_system;
  }

  /**
   * Retrieves the file IDs to operate on.
   *
   * @return array
   *   The file IDs.
   */
  public function auditfilesNotOnServerGetFileList() {
    $config = $this->configFactory->get('auditfiles.settings');
    $file_ids = [];
    $maximum_records = $config->get('auditfiles_report_options_maximum_records');
    $connection = $this->connection;
    $query = $connection->select('file_managed', 'fm');
    $query->orderBy('changed', 'DESC');
    $query->range(0, $maximum_records);
    $query->fields('fm', ['fid', 'uri']);
    $results = $query->execute()->fetchAll();
    foreach ($results as $result) {
      $target = $this->fileSystem->realpath($result->uri);
      if (!file_exists($target)) {
        $file_ids[] = $result->fid;
      }
    }
    return $file_ids;
  }

  /**
   * Retrieves information about an individual file from the database.
   *
   * @param int $file_id
   *   The ID of the file to prepare for display.
   * @param string $date_format
   *   The Date format to prepair for display.
   *
   * @return array
   *   The row for the table on the report, with the file's
   *   information formatted for display.
   */
  public function auditfilesNotOnServerGetFileData($file_id, $date_format) {
    $connection = $this->connection;
    $query = $connection->select('file_managed', 'fm');
    $query->condition('fm.fid', $file_id);
    $query->fields('fm', [
      'fid',
      'uid',
      'filename',
      'uri',
      'filemime',
      'filesize',
      'created',
      'status',
    ]);
    $results = $query->execute()->fetchAll();
    $file = $results[0];
    return [
      'fid' => $file->fid,
      'uid' => $file->uid,
      'filename' => $file->filename,
      'uri' => $file->uri,
      'path' => $this->fileSystem->realpath($file->uri),
      'filemime' => $file->filemime,
      'filesize' => number_format($file->filesize),
      'datetime' => $this->dateFormatter->format($file->created, $date_format),
      'status' => ($file->status = 1) ? 'Permanent' : 'Temporary',
    ];
  }

  /**
   * Returns the header to use for the display table.
   *
   * @return array
   *   The header to use.
   */
  public function auditfilesNotOnServerGetHeader() {
    return [
      'fid' => [
        'data' => $this->t('File ID'),
      ],
      'uid' => [
        'data' => $this->t('User ID'),
      ],
      'filename' => [
        'data' => $this->t('Name'),
      ],
      'uri' => [
        'data' => $this->t('URI'),
      ],
      'path' => [
        'data' => $this->t('Path'),
      ],
      'filemime' => [
        'data' => $this->t('MIME'),
      ],
      'filesize' => [
        'data' => $this->t('Size'),
      ],
      'datetime' => [
        'data' => $this->t('When added'),
      ],
      'status' => [
        'data' => $this->t('Status'),
      ],
    ];
  }

  /**
   * Creates the batch for deleting files from the database.
   *
   * @param array $fileids
   *   The list of file IDs to be processed.
   *
   * @return array
   *   The definition of the batch.
   */
  public function auditfilesNotOnServerBatchDeleteCreateBatch(array $fileids) {
    $batch['error_message'] = $this->t('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesNotOnServerBatchFinishBatch';
    $batch['progress_message'] = $this->t('Completed @current of @total operations.');
    $batch['title'] = $this->t('Deleting files from the database');
    $operations = $file_ids = [];
    foreach ($fileids as $file_id) {
      if ($file_id != 0) {
        $file_ids[] = $file_id;
      }
    }
    foreach ($file_ids as $file_id) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesNotOnServerBatchDeleteProcessBatch',
        [$file_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Deletes the specified file from the database.
   *
   * @param int $file_id
   *   The ID of the file to delete from the database.
   */
  public function auditfilesNotOnServerBatchDeleteProcessFile($file_id) {
    $connection = $this->connection;
    $num_rows = $connection->delete('file_managed')
      ->condition('fid', $file_id)
      ->execute();
    if (empty($num_rows)) {

      $this->messenger()->addWarning(
        $this->t(
          'There was a problem deleting the record with file ID %fid from the file_managed table. Check the logs for more information.',
          ['%fid' => $file_id]
        )
      );
    }
    else {
      $this->messenger()->addStatus(
        $this->t(
          'Sucessfully deleted File ID : %fid from the file_managed table.',
          ['%fid' => $file_id]
        )
      );
    }
  }

}
