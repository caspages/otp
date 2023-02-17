<?php

namespace Drupal\auditfiles;

use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Database\Connection;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Define all methods that are used on Files not in database functionality.
 */
class ServiceAuditFilesNotInDatabase {

  use MessengerTrait;

  /**
   * The Translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * Config Factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The Stream Wrapper Manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManager
   */
  protected $streamWrapperManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * Mime Type Guesser service.
   *
   * @var \Drupal\Core\ProxyClass\File\MimeType\MimeTypeGuesser
   */
  protected $fileMimeTypeGuesser;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface
   */
  protected $uuidService;

  /**
   * The Date Formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Entity Type Manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Define constructor for string translation.
   */
  public function __construct(TranslationInterface $translation, ConfigFactoryInterface $config_factory, Connection $connection, StreamWrapperManager $stream_wrapper_manager, FileSystemInterface $file_system, AccountProxy $current_user, MimeTypeGuesser $file_mime_type_guesser, TimeInterface $time, UuidInterface $uuid, DateFormatter $date_formatter, EntityTypeManagerInterface $entity_type_manager) {
    $this->stringTranslation = $translation;
    $this->configFactory = $config_factory;
    $this->connection = $connection;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->fileSystem = $file_system;
    $this->currentUser = $current_user;
    $this->fileMimeTypeGuesser = $file_mime_type_guesser;
    $this->time = $time;
    $this->uuidService = $uuid;
    $this->dateFormatter = $date_formatter;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get the files that are not in database.
   */
  public function auditfilesNotInDatabaseGetReportsFiles() {
    $config = $this->configFactory->get('auditfiles.settings');
    $exclusions = $this->auditFilesGetExclusions();
    $report_files = [];
    $reported_files = [];
    $this->auditfilesNotInDatabaseGetFilesForReport('', $report_files, $exclusions);
    if (!empty($report_files)) {
      // Get the static paths necessary for processing the files.
      $file_system_stream = $config->get('auditfiles_file_system_path');
      // The full file system path to the Drupal root directory.
      $real_files_path = $this->fileSystem->realpath($file_system_stream . '://');
      // Get the chosen date format for displaying the file dates with.
      $date_format = $config->get('auditfiles_report_options_date_format') ? $config->get('auditfiles_report_options_date_format') : 'long';
      foreach ($report_files as $report_file) {
        // Check to see if the file is in the database.
        if (empty($report_file['path_from_files_root'])) {
          $file_to_check = $report_file['file_name'];
        }
        else {
          $file_to_check = $report_file['path_from_files_root'] . DIRECTORY_SEPARATOR . $report_file['file_name'];
        }
        // If the file is not in the database, add to the list for displaying.
        if (!$this->auditfilesNotInDatabaseIsFileInDatabase($file_to_check)) {
          // Gets the file's information (size, date, etc.) and assempbles the.
          // array for the table.
          $reported_files += $this->auditfilesNotInDatabaseFormatRowData($report_file, $real_files_path, $date_format);
        }
      }
    }
    return $reported_files;
  }

  /**
   * Get files for report.
   */
  public function auditfilesNotInDatabaseGetFilesForReport($path, array &$report_files, $exclusions) {
    $config = $this->configFactory->get('auditfiles.settings');
    $file_system_stream = $config->get('auditfiles_file_system_path');
    $real_files_path = $real_files_path = $this->fileSystem->realpath($file_system_stream . '://');
    $maximum_records = $config->get('auditfiles_report_options_maximum_records');
    if ($maximum_records > 0 && count($report_files) < $maximum_records) {
      $new_files = $this->auditfilesNotInDatabaseGetFiles($path, $exclusions);
      if (!empty($new_files)) {
        foreach ($new_files as $file) {
          // Check if the current item is a directory or a file.
          if (empty($file['path_from_files_root'])) {
            $item_path_check = $real_files_path . DIRECTORY_SEPARATOR . $file['file_name'];
          }
          else {
            $item_path_check = $real_files_path . DIRECTORY_SEPARATOR . $file['path_from_files_root'] . DIRECTORY_SEPARATOR . $file['file_name'];
          }
          if (is_dir($item_path_check)) {
            // The item is a directory, so go into it and get any files there.
            if (empty($path)) {
              $file_path = $file['file_name'];
            }
            else {
              $file_path = $path . DIRECTORY_SEPARATOR . $file['file_name'];
            }
            $this->auditfilesNotInDatabaseGetFilesForReport($file_path, $report_files, $exclusions);
          }
          else {
            // The item is a file, so add it to the list.
            $file['path_from_files_root'] = $this->auditfilesNotInDatabaseFixPathSeparators($file['path_from_files_root']);
            $report_files[] = $file;
          }
        }
      }
    }
  }

  /**
   * Checks if the specified file is in the database.
   *
   * @param string $filepathname
   *   The path and filename, from the "files" directory, of the file to check.
   *
   * @return bool
   *   Returns TRUE if the file was found in the database, or FALSE, if not.
   */
  public function auditfilesNotInDatabaseIsFileInDatabase($filepathname) {
    $file_uri = $this->auditfilesBuildUri($filepathname);
    $connection = $this->connection;
    $fid = $connection->select('file_managed', 'fm')
      ->condition('fm.uri', $file_uri)
      ->fields('fm', ['fid'])
      ->execute()
      ->fetchField();
    return empty($fid) ? FALSE : TRUE;
  }

  /**
   * Add files to record to display in reports.
   */
  public function auditfilesNotInDatabaseFormatRowData($file, $real_path, $date_format) {
    if (empty($file['path_from_files_root'])) {
      $filepathname = $file['file_name'];
    }
    else {
      $filepathname = $file['path_from_files_root'] . DIRECTORY_SEPARATOR . $file['file_name'];
    }
    $real_filepathname = $real_path . DIRECTORY_SEPARATOR . $filepathname;
    $filemime = $this->fileMimeTypeGuesser->guess($real_filepathname);
    $filesize = number_format(filesize($real_filepathname));
    if (!empty($date_format)) {
      $filemodtime = $this->dateFormatter->format(filemtime($real_filepathname), $date_format);
    }
    // Format the data for the table row.
    $row_data[$filepathname] = [
      'filepathname' => empty($filepathname) ? '' : $filepathname,
      'filemime' => empty($filemime) ? '' : $filemime,
      'filesize' => !isset($filesize) ? '' : $filesize,
      'filemodtime' => empty($filemodtime) ? '' : $filemodtime,
      'filename' => empty($file['file_name']) ? '' : $file['file_name'],
    ];
    return $row_data;
  }

  /**
   * Retrieves a list of files in the given path.
   *
   * @param string $path
   *   The path to search for files in.
   * @param string $exclusions
   *   The imploded list of exclusions from configuration.
   *
   * @return array
   *   The list of files and diretories found in the given path.
   */
  public function auditfilesNotInDatabaseGetFiles($path, $exclusions) {
    $config = $this->configFactory->get('auditfiles.settings');
    $file_system_stream = $config->get('auditfiles_file_system_path');
    $real_files_path = $real_files_path = $this->fileSystem->realpath($file_system_stream . '://');
    // The variable to store the data being returned.
    $file_list = [];
    $scan_path = empty($path) ? $real_files_path : $real_files_path . DIRECTORY_SEPARATOR . $path;
    // Get the files in the specified directory.
    $files = array_diff(scandir($scan_path), ['..', '.']);
    foreach ($files as $file) {
      // Check to see if this file should be included.
      if ($this->auditfilesNotInDatabaseIncludeFile($real_files_path . DIRECTORY_SEPARATOR . $path, $file, $exclusions)) {
        // The file is to be included, so add it to the data array.
        $file_list[] = [
          'file_name' => $file,
          'path_from_files_root' => $path,
        ];
      }
    }
    return $file_list;
  }

  /**
   * Corrects the separators of a file system's file path.
   *
   * Changes the separators of a file path, so they are match the ones
   * being used on the operating system the site is running on.
   *
   * @param string $path
   *   The path to correct.
   *
   * @return string
   *   The corrected path.
   */
  public function auditfilesNotInDatabaseFixPathSeparators($path) {
    $path = preg_replace('@\/\/@', DIRECTORY_SEPARATOR, $path);
    $path = preg_replace('@\\\\@', DIRECTORY_SEPARATOR, $path);
    return $path;
  }

  /**
   * Creates an exclusion string.
   *
   * This function creates a list of file and/or directory exclusions to be used
   * with a preg_* function.
   *
   * @return string
   *   The excluions.
   */
  public function auditfilesGetExclusions() {
    $config = $this->configFactory->get('auditfiles.settings');
    $exclusions_array = [];
    $files = $config->get('auditfiles_exclude_files');
    if ($files) {
      $exclude_files = explode(';', $files);
      array_walk($exclude_files, '\\Drupal\\auditfiles\\AuditFilesBatchProcess::auditfilesMakePreg', FALSE);
      $exclusions_array = array_merge($exclusions_array, $exclude_files);
    }
    $paths = $config->get('auditfiles_exclude_paths');
    if ($paths) {
      $exclude_paths = explode(';', $paths);
      array_walk($exclude_paths, '\\Drupal\\auditfiles\\AuditFilesBatchProcess::auditfilesMakePreg', TRUE);
      $exclusions_array = array_merge($exclusions_array, $exclude_paths);
    }
    // Exclude other file streams that may be defined and in use.
    $exclude_streams = [];
    $auditfiles_file_system_path = $config->get('auditfiles_file_system_path');
    $file_system_paths = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::LOCAL);
    foreach ($file_system_paths as $file_system_path_id => $file_system_path) {
      if ($file_system_path_id != $auditfiles_file_system_path) {
        $uri = $file_system_path_id . '://';
        if ($wrapper = $this->streamWrapperManager->getViaUri($uri)) {
          $exclude_streams[] = $wrapper->realpath();
        }
      }
    }
    array_walk($exclude_streams, '\\Drupal\\auditfiles\\AuditFilesBatchProcess::auditfilesMakePreg', FALSE);
    $exclusions_array = array_merge($exclusions_array, $exclude_streams);
    // Create the list of requested extension exclusions. (This is a little more
    // complicated.)
    $extensions = $config->get('auditfiles_exclude_extensions');
    if ($extensions) {
      $exclude_extensions = explode(';', $extensions);
      array_walk($exclude_extensions, '\\Drupal\\auditfiles\\AuditFilesBatchProcess::auditfilesMakePreg', FALSE);
      $extensions = implode('|', $exclude_extensions);
      $extensions = '(' . $extensions . ')$';
      $exclusions_array[] = $extensions;
    }
    // Implode exclusions array to a string.
    $exclusions = implode('|', $exclusions_array);
    // Return prepared exclusion string.
    return $exclusions;
  }

  /**
   * Checks to see if the file is being included.
   *
   * @param string $path
   *   The complete file system path to the file.
   * @param string $file
   *   The name of the file being checked.
   * @param string $exclusions
   *   The list of files and directories that are not to be included in the
   *   list of files to check.
   *
   * @return bool
   *   Returns TRUE, if the path or file is being included, or FALSE,
   *   if the path or file has been excluded.
   *
   * @todo Possibly add other file streams that are on the system but not the one
   *   being checked to the exclusions check.
   */
  public function auditfilesNotInDatabaseIncludeFile($path, $file, $exclusions) {
    if (empty($exclusions)) {
      return TRUE;
    }
    elseif (!preg_match('@' . $exclusions . '@', $file) && !preg_match('@' . $exclusions . '@', $path . DIRECTORY_SEPARATOR . $file)) {
      return TRUE;
    }
    // This path and/or file are being excluded.
    return FALSE;
  }

  /**
   * Returns the header to use for the display table.
   *
   * @return array
   *   The header to use.
   */
  public function auditfilesNotInDatabaseGetHeader() {
    return [
      'filepathname' => [
        'data' => $this->stringTranslation->translate('File pathname'),
      ],
      'filemime' => [
        'data' => $this->stringTranslation->translate('MIME'),
      ],
      'filesize' => [
        'data' => $this->stringTranslation->translate('Size (in bytes)'),
      ],
      'filemodtime' => [
        'data' => $this->stringTranslation->translate('Last modified'),
      ],
    ];
  }

  /**
   * Creates the batch for adding files to the database.
   *
   * @param array $fileids
   *   The list of file IDs to be processed.
   *
   * @return array
   *   The definition of the batch.
   */
  public function auditfilesNotInDatabaseBatchAddCreateBatch(array $fileids) {
    $batch['title'] = $this->stringTranslation->translate('Adding files to Drupal file management');
    $batch['error_message'] = $this->stringTranslation->translate('One or more errors were encountered processing the files.');
    $batch['finished'] = "\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesNotInDatabaseBatchFinishBatch";
    $batch['progress_message'] = $this->stringTranslation->translate('Completed @current of @total operations.');
    $operations = [];
    $file_ids = [];
    foreach ($fileids as $file_id) {
      if (!empty($file_id)) {
        $file_ids[] = $file_id;
      }
    }
    foreach ($file_ids as $file_id) {
      $operations[] = [
        "\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesNotInDatabaseBatchAddProcessBatch",
        [$file_id],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Adds the specified file to the database.
   *
   * @param string $filepathname
   *   The full pathname to the file to add to the database.
   */
  public function auditfilesNotInDatabaseBatchAddProcessFile($filepathname) {
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $file = new \StdClass();
    $file->uid = $user->get('uid')->value;
    $file->filename = trim(basename($filepathname));
    $file->uri = $this->auditfilesBuildUri($filepathname);
    $real_filenamepath = $this->fileSystem->realpath($file->uri);
    $file->filemime = $this->fileMimeTypeGuesser->guess($real_filenamepath);
    $file->filesize = filesize($real_filenamepath);
    $file->status = FILE_STATUS_PERMANENT;
    $file->timestamp = $this->time->getCurrentTime();
    $uuid = $this->uuidService->generate();
    $connection = $this->connection;
    $query = $connection->select('file_managed', 'fm');
    $query->condition('fm.uri', $file->uri);
    $query->fields('fm', ['fid']);
    $existing_file = $query->execute()->fetchField();
    if (empty($existing_file)) {
      $results = $this->connection->merge('file_managed')
        ->key(['fid' => NULL])
        ->fields([
          'fid' => NULL,
          'uuid' => $uuid,
          'langcode' => 'en',
          'uid' => $file->uid,
          'filename' => $file->filename,
          'uri' => $file->uri,
          'filemime' => $file->filemime,
          'filesize' => $file->filesize,
          'status' => $file->status,
          'created' => $file->timestamp,
          'changed' => $file->timestamp,
        ])->execute();
      if (empty($results)) {
        $this->messenger()->addStatus(
          $this->stringTranslation->translate('Failed to add %file to the database.', ['%file' => $filepathname])
        );
      }
      else {
        $this->messenger()->addStatus(
          $this->stringTranslation->translate('Sucessfully added %file to the database.', ['%file' => $filepathname])
        );
      }
    }
    else {
      $this->messenger()->addStatus(
        $this->stringTranslation->translate('The file %file is already in the database.', ['%file' => $filepathname])
      );
    }
  }

  /**
   * Creates the batch for deleting files from the server.
   *
   * @param array $file_names
   *   The list of file names to be processed.
   *
   * @return array
   *   The definition of the batch.
   */
  public function auditfilesNotInDatabaseBatchDeleteCreateBatch(array $file_names) {
    $batch['title'] = $this->stringTranslation->translate('Adding files to Drupal file management');
    $batch['error_message'] = $this->stringTranslation->translate('One or more errors were encountered processing the files.');
    $batch['finished'] = '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesNotInDatabaseBatchFinishBatch';
    $batch['progress_message'] = $this->stringTranslation->translate('Completed @current of @total operations.');
    $batch['title'] = $this->stringTranslation->translate('Deleting files from the server');
    $operations = [];
    $filenames = [];
    foreach ($file_names as $file_name) {
      if (!empty($file_name)) {
        $filenames[] = $file_name;
      }
    }
    foreach ($filenames as $filename) {
      $operations[] = [
        '\Drupal\auditfiles\AuditFilesBatchProcess::auditfilesNotInDatabaseBatchDeleteProcessBatch',
        [$filename],
      ];
    }
    $batch['operations'] = $operations;
    return $batch;
  }

  /**
   * Deletes the specified file from the server.
   *
   * @param string $filename
   *   The full pathname of the file to delete from the server.
   */
  public function auditfilesNotInDatabaseBatchDeleteProcessFile($filename) {
    $config = $this->configFactory->get('auditfiles.settings');
    $file_system_stream = $config->get('auditfiles_file_system_path');
    $real_files_path = $this->fileSystem->realpath($file_system_stream . '://');

    if ($this->fileSystem->delete($real_files_path . DIRECTORY_SEPARATOR . $filename)) {
      $this->messenger()->addStatus(
        $this->stringTranslation->translate('Sucessfully deleted %file from the server.', ['%file' => $filename])
      );
    }
    else {
      $this->messenger()->addStatus(
        $this->stringTranslation->translate('Failed to delete %file from the server.', ['%file' => $filename])
      );
    }
  }

  /**
   * Returns the internal path to the given file.
   */
  public function auditfilesBuildUri($file_pathname) {
    $config = $this->configFactory->get('auditfiles.settings');
    $file_system_stream = $config->get('auditfiles_file_system_path');
    return "$file_system_stream://$file_pathname";
  }

}
