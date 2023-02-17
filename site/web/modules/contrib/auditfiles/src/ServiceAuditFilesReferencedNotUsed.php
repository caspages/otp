<?php

namespace Drupal\auditfiles;

use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * List all methods used in referenced not used functionality.
 */
class ServiceAuditFilesReferencedNotUsed {

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
   * The entity_type.manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Class constructor.
   *
   * @param Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param Drupal\Core\Config\ConfigFactory $config_factory
   *   The configuration service.
   * @param Drupal\Core\Database\Connection $connection
   *   The connection service.
   * @param Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The field manager service.
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(TranslationInterface $translation, ConfigFactory $config_factory, Connection $connection, EntityFieldManager $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->stringTranslation = $translation;
    $this->configFactory = $config_factory;
    $this->connection = $connection;
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Retrieves the file IDs to operate on.
   *
   * @return array
   *   The file IDs.
   */
  public function auditfilesReferencedNotUsedGetFileList() {
    $config = $this->configFactory->get('auditfiles.settings');
    $connection = $this->connection;
    $file_references = $files_referenced = [];
    // Get a list of all files that are referenced in content.
    $fields = $field_data = [];
    $fields[] = $this->entityFieldManager->getFieldMapByFieldType('image');
    $fields[] = $this->entityFieldManager->getFieldMapByFieldType('file');
    if ($fields) {
      $count = 0;
      foreach ($fields as $value) {
        foreach ($value as $table_prefix => $entity_type) {
          foreach ($entity_type as $key1 => $value1) {
            $field_data[$count]['table'] = $table_prefix . '__' . $key1;
            $field_data[$count]['column'] = $key1 . '_target_id';
            $field_data[$count]['entity_type'] = $table_prefix;
            $count++;
          }
        }
      }
      foreach ($field_data as $value) {
        $table = $value['table'];
        $column = $value['column'];
        $entity_type = $value['entity_type'];
        if ($this->connection->schema()->tableExists($table)) {
          $fu_query = $connection->select('file_usage', 'fu')->fields('fu', ['fid'])->execute()->fetchCol();
          $query = $connection->select($table, 't')->fields('t', ['entity_id', $column]);
          if (!empty($fu_query)) {
            $query->condition('t.' . $column, $fu_query, 'NOT IN');
          }
          $maximum_records = $config->get('auditfiles_report_options_maximum_records');
          if ($maximum_records > 0) {
            $query->range(0, $maximum_records);
          }
          $file_references = $query->execute()->fetchAll();
          foreach ($file_references as $file_reference) {
            $reference_id = $table . '.' . $column . '.' . $file_reference->entity_id . '.' . $entity_type . '.' . $file_reference->{$column};
            $files_referenced[$reference_id] = [
              'table' => $table,
              'column' => $column,
              'entity_id' => $file_reference->entity_id,
              'file_id' => $file_reference->{$column},
              'entity_type' => $entity_type,
            ];
          }
        }
      }
    }
    return $files_referenced;
  }

  /**
   * Retrieves information about an individual file from the database.
   *
   * @param array $row_data
   *   The data to use for creating the row.
   *
   * @return array
   *   The row for the table on the report, with the file's
   *   information formatted for display.
   */
  public function auditfilesReferencedNotUsedGetFileData(array $row_data) {
    $connection = $this->connection;
    $query = 'SELECT * FROM {' . $row_data['table'] . '} WHERE ' . $row_data['column'] . ' = ' . $row_data['file_id'];
    $result = $connection->query($query)->fetchAll();
    $result = reset($result);
    if ($row_data['entity_type'] == 'node') {
      $url = Url::fromUri('internal:/node/' . $result->entity_id);
      $entity_id_display = Link::fromTextAndUrl('node/' . $result->entity_id, $url)->toString();
    }
    else {
      $entity_id_display = $result->entity_id;
    }
    $row = [
      'file_id' => $result->{$row_data['column']},
      'entity_type' => $row_data['entity_type'],
      'bundle' => ['data' => $result->bundle, 'hidden' => TRUE],
      'entity_id' => ['data' => $result->entity_id, 'hidden' => TRUE],
      'entity_id_display' => $entity_id_display,
      'field' => $row_data['table'] . '.' . $row_data['column'],
      'table' => ['data' => $row_data['table'], 'hidden' => TRUE],
      'uri' => 'No file object exists for this reference.',
      'filename' => ['data' => '', 'hidden' => TRUE],
      'filemime' => '--',
      'filesize' => '--',
    ];
    // If there is a file in the file_managed table, add some of that
    // information to the row, too.
    $file_managed = $this->entityTypeManager->getStorage('file')->load($result->{$row_data['column']});
    if (!empty($file_managed)) {
      $row['uri'] = $file_managed->getFileuri();
      $row['filename'] = ['data' => $file_managed->getFilename(), 'hidden' => TRUE];
      $row['filemime'] = $file_managed->getMimeType();
      $row['filesize'] = $file_managed->getSize();
    }
    return $row;
  }

  /**
   * Returns the header to use for the display table.
   *
   * @return array
   *   The header to use.
   */
  public function auditfilesReferencedNotUsedGetHeader() {
    return [
      'file_id' => [
        'data' => $this->stringTranslation->translate('File ID'),
      ],
      'entity_type' => [
        'data' => $this->stringTranslation->translate('Referencing entity type'),
      ],
      'entity_id_display' => [
        'data' => $this->stringTranslation->translate('Referencing entity ID'),
      ],
      'field' => [
        'data' => $this->stringTranslation->translate('Field referenced in'),
      ],
      'uri' => [
        'data' => $this->stringTranslation->translate('URI'),
      ],
      'filemime' => [
        'data' => $this->stringTranslation->translate('MIME'),
      ],
      'filesize' => [
        'data' => $this->stringTranslation->translate('Size (in bytes)'),
      ],
    ];
  }

  /**
   * Creates the batch for adding files to the file_usage table.
   *
   * @param array $referenceids
   *   The list of IDs to be processed.
   *
   * @return array
   *   The definition of the batch.
   */
  public function auditfilesReferencedNotUsedBatchAddCreateBatch(array $referenceids) {
    $batch['error_message'] = $this->stringTranslation->translate('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesReferencedNotUsedBatchFinishBatch';
    $batch['progress_message'] = $this->stringTranslation->translate('Completed @current of @total operations.');
    $batch['title'] = $this->stringTranslation->translate('Adding files to the file_usage table');
    $operations = $reference_ids = [];
    foreach ($referenceids as $reference_id) {
      if (!empty($reference_id)) {
        $reference_ids[] = $reference_id;
      }
    }
    foreach ($reference_ids as $reference_id) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesReferencedNotUsedBatchAddProcessBatch',
        [$reference_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Adds the specified file to the file_usage table.
   *
   * @param string $reference_id
   *   The ID for keeping track of the reference.
   */
  public function auditfilesReferencedNotUsedBatchAddProcessFile($reference_id) {
    $reference_id_parts = explode('.', $reference_id);
    $connection = $this->connection;
    $data = [
      'fid' => $reference_id_parts[4],
      // @todo This is hard coded for now, but need to determine how to figure out
      // which module needs to be here.
      'module' => 'file',
      'type' => $reference_id_parts[3],
      'id' => $reference_id_parts[2],
      'count' => 1,
    ];
    // Make sure the file is not already in the database.
    $query = 'SELECT fid FROM file_usage
    WHERE fid = :fid AND module = :module AND type = :type AND id = :id';
    $existing_file = $connection->query(
      $query,
      [
        ':fid' => $data['fid'],
        ':module' => $data['module'],
        ':type' => $data['type'],
        ':id' => $data['id'],
      ]
    )->fetchAll();
    if (empty($existing_file)) {
      // The file is not already in the database, so add it.
      $connection->insert('file_usage')->fields($data)->execute();
    }
    else {
      $this->messenger()->addError(
        $this->stringTranslation->translate(
           'The file is already in the file_usage table (file id: "@fid", module: "@module", type: "@type", entity id: "@id").',
          [
            '@fid' => $data['fid'],
            '@module' => $data['module'],
            '@type' => $data['type'],
            '@id' => $data['id'],
          ]
        )
      );
    }
  }

  /**
   * Creates the batch for deleting file references from their content.
   *
   * @param array $referenceids
   *   The list of IDs to be processed.
   *
   * @return array
   *   The definition of the batch.
   */
  public function auditfilesReferencedNotUsedBatchDeleteCreateBatch(array $referenceids) {
    $batch['error_message'] = $this->stringTranslation->translate('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesReferencedNotUsedBatchFinishBatch';
    $batch['progress_message'] = $this->stringTranslation->translate('Completed @current of @total operations.');
    $batch['title'] = $this->stringTranslation->translate('Deleting file references from their content');
    $operations = $reference_ids = [];
    foreach ($referenceids as $reference_id) {
      if ($reference_id != '') {
        $reference_ids[] = $reference_id;
      }
    }
    // Fill in the $operations variable.
    foreach ($reference_ids as $reference_id) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesReferencedNotUsedBatchDeleteProcessBatch',
        [$reference_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Deletes the specified file from the database.
   *
   * @param string $reference_id
   *   The ID for keeping track of the reference.
   */
  public function auditfilesReferencedNotUsedBatchDeleteProcessFile($reference_id) {
    $reference_id_parts = explode('.', $reference_id);
    $connection = $this->connection;
    $num_rows = $connection->delete($reference_id_parts[0])
      ->condition($reference_id_parts[1], $reference_id_parts[4])
      ->execute();
    if (empty($num_rows)) {
      $this->messenger()->addWarning(
        $this->stringTranslation->translate(
          'There was a problem deleting the reference to file ID %fid in the %entity_type with ID %eid. Check the logs for more information.',
          [
            '%fid' => $reference_id_parts[4],
            '%entity_type' => $reference_id_parts[3],
            '%eid' => $reference_id_parts[2],
          ]
        )
      );
    }
    else {
      $this->messenger()->addStatus(
        $this->stringTranslation->translate(
          'file ID %fid  deleted successfully.',
          [
            '%fid' => $reference_id_parts[4],
          ]
        )
      );
    }
  }

}
