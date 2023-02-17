<?php

namespace Drupal\auditfiles\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Datetime\Entity\DateFormat;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Use this class to create configuration form for module.
 */
class AuditFilesConfig extends ConfigFormBase {

  /**
   * The Stream Wrapper Manager service.
   *
   * @var \Drupal\Core\StreamWrapperInterface
   */
  protected $streamWrapperManager;

  /**
   * Class constructor.
   *
   * @param Drupal\Core\StreamWrapper\StreamWrapperManager $stream_wrapper
   *   The stream wrapper service.
   */
  public function __construct(StreamWrapperManager $stream_wrapper) {
    $this->streamWrapperManager = $stream_wrapper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('stream_wrapper_manager')
    );
  }

  /**
   * Widget Id.
   */
  public function getFormId() {
    return 'auditfiles_config';
  }

  /**
   * Create configurations Name.
   */
  protected function getEditableConfigNames() {
    return ['auditfiles.settings'];
  }

  /**
   * Create form for configurations.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('auditfiles.settings');
    $form['auditfiles_file_system_paths'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('File system paths'),
      '#collapsible' => TRUE,
    ];
    // Show the file system path select list.
    $file_system_paths = $this->streamWrapperManager->getWrappers(StreamWrapperInterface::LOCAL);
    $options = [];
    foreach ($file_system_paths as $file_system_path_id => $file_system_path) {
      $options[$file_system_path_id] = $file_system_path_id . ' : file_' . $file_system_path_id . '_path';
    }
    $form['auditfiles_file_system_paths']['auditfiles_file_system_path'] = [
      '#type' => 'select',
      '#title' => 'File system path',
      '#default_value' => $config->get('auditfiles_file_system_path'),
      '#options' => $options,
      '#description' => $this->t('Select the file system path to use when searching for and comparing files.'),
    ];

    $form['auditfiles_exclusions'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Exclusions'),
      '#collapsible' => TRUE,
    ];

    $form['auditfiles_exclusions']['auditfiles_exclude_files'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude these files'),
      '#default_value' => trim($config->get('auditfiles_exclude_files')),
      '#description' => $this->t('Enter a list of files to exclude, each separated by the semi-colon character (;).'),
    ];

    $form['auditfiles_exclusions']['auditfiles_exclude_extensions'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude these extensions'),
      '#default_value' => trim($config->get('auditfiles_exclude_extensions')),
      '#description' => $this->t('Enter a list of extensions to exclude, each separated by the semi-colon character (;). Do not include the leading dot.'),
    ];

    $form['auditfiles_exclusions']['auditfiles_exclude_paths'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Exclude these paths'),
      '#default_value' => trim($config->get('auditfiles_exclude_paths')),
      '#description' => $this->t('Enter a list of paths to exclude, each separated by the semi-colon character (;). Do not include the leading slash.'),
    ];

    $form['auditfiles_domains'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Domains'),
      '#collapsible' => TRUE,
    ];
    $form['auditfiles_domains']['auditfiles_include_domains'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Include references to these domains'),
      '#default_value' => trim($config->get('auditfiles_include_domains')),
      '#size' => 80,
      '#maxlength' => 1024,
      '#description' => $this->t('Enter a list of domains (e.g., www.example.com) pointing to your website, each separated by the semi-colon character (;). <br />When scanning content for file references (such as &lt;img&gt;tags), any absolute references using these domains will be included and rewritten to use relative references. Absolute references to domains not in this list will be considered to be external references and will not be audited or rewritten.'),
    ];

    $form['auditfiles_report_options'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Report options'),
      '#collapsible' => TRUE,
    ];

    $date_types = DateFormat::loadMultiple();
    foreach ($date_types as $machine_name => $format) {
      $date_formats[$machine_name] = $machine_name;
    }

    $form['auditfiles_report_options']['auditfiles_report_options_date_format'] = [
      '#type' => 'select',
      '#title' => 'Date format',
      '#default_value' => $config->get('auditfiles_report_options_date_format') ? $config->get('auditfiles_report_options_date_format') : '',
      '#options' => $date_formats,
      '#description' => $this->t('Select the date format to use when displaying file dates in the reports.'),
    ];

    $form['auditfiles_report_options']['auditfiles_report_options_items_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Number of items per page'),
      '#default_value' => $config->get('auditfiles_report_options_items_per_page'),
      '#size' => 10,
      '#description' => $this->t('Enter an integer representing the number of items to display on each page of a report.<br /> If there are more than this number on a page, then a pager will be used to display the additional items.<br /> Set this to 0 to show all items on a single page.'),
    ];

    $form['auditfiles_report_options']['auditfiles_report_options_maximum_records'] = [
      '#type' => 'number',
      '#title' => $this->t('Maximum records'),
      '#default_value' => $config->get('auditfiles_report_options_maximum_records'),
      '#size' => 10,
      '#description' => $this->t('Enter an integer representing the maximum number of records to return for each report.<br /> If any of the reports are timing out, set this to some positive integer to limit the number of records that are queried in the database. For reports where the limit is reached, a button to batch process the loading of the page will be available that will allow all records to be retrieved without timing out.<br /> Set this to 0 for no limit.'),
    ];

    $form['auditfiles_report_options']['auditfiles_report_options_batch_size'] = [
      '#type' => 'number',
      '#title' => $this->t('Batch size'),
      '#default_value' => $config->get('auditfiles_report_options_batch_size'),
      '#size' => 10,
      '#description' => $this->t('If you have a lot of files (100,000+), it will take an exponentially longer amount of time and memory to retrieve file data the longer it goes through the batch process. Decreasing the the number of files loaded, by setting this to a positive, non-zero integer, will keep the batch process down to a reasonable amount of time.<br /> Set this to 0 for no limit.'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit popup after login configurations.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('auditfiles.settings')
      ->set('auditfiles_file_system_path', $form_state->getValue('auditfiles_file_system_path'))
      ->set('auditfiles_exclude_files', $form_state->getValue('auditfiles_exclude_files'))
      ->set('auditfiles_exclude_extensions', $form_state->getValue('auditfiles_exclude_extensions'))
      ->set('auditfiles_exclude_paths', $form_state->getValue('auditfiles_exclude_paths'))
      ->set('auditfiles_include_domains', $form_state->getValue('auditfiles_include_domains'))
      ->set('auditfiles_report_options_items_per_page', $form_state->getValue('auditfiles_report_options_items_per_page'))
      ->set('auditfiles_report_options_maximum_records', $form_state->getValue('auditfiles_report_options_maximum_records'))
      ->set('auditfiles_report_options_batch_size', $form_state->getValue('auditfiles_report_options_batch_size'))
      ->set('auditfiles_report_options_date_format', $form_state->getValue('auditfiles_report_options_date_format'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
