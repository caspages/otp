<?php

namespace Drupal\auditfiles;

use Drupal\Component\Utility\Html;
use Drupal\Core\Messenger\MessengerTrait;

/**
 * Process batch files.
 */
class AuditFilesBatchProcess {

  use MessengerTrait;

  /**
   * Called when the batch is completed in 'not in database fumctionality'.
   */
  public static function auditfilesNotInDatabaseBatchFinishBatch($success, $results, $operations) {
    if ($success) {
      // Success action.
    }
    else {
      $error_operation = reset($operations);
      $this->messenger()->addError(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * The batch process for adding the file.
   *
   * @param string $filename
   *   File name that to be process.
   * @param array $context
   *   Used by the Batch API to keep track of data and pass it from one
   *   operation to the next.
   */
  public static function auditfilesNotInDatabaseBatchAddProcessBatch($filename, array &$context) {
    \Drupal::service('auditfiles.not_in_database')->auditfilesNotInDatabaseBatchAddProcessFile($filename);
    $context['results'][] = $filename;
    $context['message'] = t('Processed %filename.', ['%filename' => $filename]);
  }

  /**
   * The batch process for deleting the file.
   *
   * @param string $filename
   *   File name that to be process.
   * @param array $context
   *   Used by the Batch API to keep track of data and pass it from one
   *   operation to the next.
   */
  public static function auditfilesNotInDatabaseBatchDeleteProcessBatch($filename, array &$context) {
    \Drupal::service('auditfiles.not_in_database')->auditfilesNotInDatabaseBatchDeleteProcessFile($filename);
    $context['results'][] = Html::escape($filename);
    $context['message'] = t('Processed %filename.', ['%filename' => $filename]);
  }

  /**
   * Escapes any possible regular expression characters in the specified string.
   *
   * @param string $element
   *   The string to escape.
   * @param mixed $key
   *   The key or index for the array item passed into $element.
   * @param bool $makefilepath
   *   Set to TRUE to change elements to file paths at the same time.
   */
  public static function auditfilesMakePreg(&$element, $key = '', $makefilepath = FALSE) {
    if ($makefilepath) {
      $realpath = \Drupal::service('file_system')
        ->realpath(file_build_uri($element));
      if ($realpath) {
        $element = $realpath;
      }
    }
    $element = preg_quote($element);
  }

  /**
   * Called when the batch is complete in 'Not on server'.
   */
  public static function auditfilesNotOnServerBatchFinishBatch($success, $results, $operations) {
    if ($success) {
      // Do tasks.
    }
    else {
      $error_operation = reset($operations);
      $this->messenger()->addError(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * The batch process for deleting the file.
   *
   * Used by the Batch API to keep track of and pass data from one operation to
   * the next.
   */
  public static function auditfilesNotOnServerBatchDeleteProcessBatch($file_id, array &$context) {
    \Drupal::service('auditfiles.not_on_server')->auditfilesNotOnServerBatchDeleteProcessFile($file_id);
    $context['results'][] = $file_id;
    $context['message'] = t('Processed file ID %file_id.', ['%file_id' => $file_id]);
  }

  /**
   * The batch process for deleting the file of Managed not used functionality.
   *
   * Used by the Batch API to keep track of and pass data from one operation to
   * the next.
   */
  public static function auditfilesManagedNotUsedBatchDeleteProcessBatch($file_id, array &$context) {
    \Drupal::service('auditfiles.managed_not_used')->auditfilesManagedNotUsedBatchDeleteProcessFile($file_id);
    $context['results'][] = $file_id;
    $context['message'] = t('Processed file ID %file_id.', ['%file_id' => $file_id]);
  }

  /**
   * The function that is called when the batch is complete.
   */
  public static function auditfilesManagedNotUsedBatchFinishBatch($success, $results, $operations) {
    if (!$success) {
      $error_operation = reset($operations);
      $this->messenger()->addError(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * The batch process for deleting the file feature 'used not managed'.
   */
  public static function auditfilesUsedNotManagedBatchDeleteProcessBatch($file_id, array &$context) {
    \Drupal::service('auditfiles.used_not_managed')->auditfilesUsedNotManagedBatchDeleteProcessFile($file_id);
    $context['results'][] = $file_id;
    $context['message'] = t('Processed file ID %file_id.', ['%file_id' => $file_id]);
  }

  /**
   * Called when the batch is complete : functionality 'used not managed'.
   */
  public static function auditfilesUsedNotManagedBatchFinishBatch($success, $results, $operations) {
    if (!$success) {
      $error_operation = reset($operations);

      $this->messenger()->addError(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * The batch process for deleting the file.
   */
  public static function auditfilesUsedNotReferencedBatchDeleteProcessBatch($file_id, array &$context) {
    \Drupal::service('auditfiles.used_not_referenced')->auditfilesUsedNotReferencedBatchDeleteProcessFile($file_id);
    $context['results'][] = $file_id;
    $context['message'] = t('Processed file ID %file_id.', ['%file_id' => $file_id]);
  }

  /**
   * The function that is called when the batch is complete.
   */
  public static function auditfilesUsedNotReferencedBatchFinishBatch($success, $results, $operations) {
    if (!$success) {
      $error_operation = reset($operations);
      $this->messenger()->addError(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * The function that is called when the batch is complete.
   */
  public static function auditfilesReferencedNotUsedBatchFinishBatch($success, $results, $operations) {
    if (!$success) {
      $error_operation = reset($operations);
      $this->messenger()->addError(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * Used by the Batch API to keep track of and pass data from one operation.
   */
  public static function auditfilesReferencedNotUsedBatchAddProcessBatch($reference_id, array &$context) {
    \Drupal::service('auditfiles.referenced_not_used')->auditfilesReferencedNotUsedBatchAddProcessFile($reference_id);
    $context['results'][] = $reference_id;
    $context['message'] = t('Processed reference ID %file_id.', ['%file_id' => $reference_id]);
  }

  /**
   * Used by the Batch API to keep track of and pass data from one operation.
   */
  public static function auditfilesReferencedNotUsedBatchDeleteProcessBatch($reference_id, array &$context) {
    \Drupal::service('auditfiles.referenced_not_used')->auditfilesReferencedNotUsedBatchDeleteProcessFile($reference_id);
    $context['results'][] = $reference_id;
    $context['message'] = t('Processed reference ID %file_id.', ['%file_id' => $reference_id]);
  }

  /**
   * The function that is called when the batch is complete.
   */
  public static function auditfilesMergeFileReferencesBatchFinishBatch($success, $results, $operations) {
    if (!$success) {
      $error_operation = reset($operations);
      $this->messenger()->addError(
        t('An error occurred while processing @operation with arguments : @args',
          [
            '@operation' => $error_operation[0],
            '@args' => print_r($error_operation[0], TRUE),
          ]
        )
      );
    }
  }

  /**
   * The batch process for deleting the file.
   *
   * @param int $file_being_kept
   *   The file ID of the file to merge the other into.
   * @param int $file_being_merged
   *   The file ID of the file to merge.
   * @param array $context
   *   Used by the Batch API to keep track of and pass data from one operation
   *   to the next.
   */
  public static function auditfilesMergeFileReferencesBatchMergeProcessBatch($file_being_kept, $file_being_merged, array &$context) {
    \Drupal::service('auditfiles.merge_file_references')->auditfilesMergeFileReferencesBatchMergeProcessFile($file_being_kept, $file_being_merged);
    $context['results'][] = $file_being_merged;
    $context['message'] = t(
      'Merged file ID %file_being_merged into file ID %file_being_kept.',
      [
        '%file_being_kept' => $file_being_kept,
        '%file_being_merged' => $file_being_merged,
      ]
    );
  }

}
