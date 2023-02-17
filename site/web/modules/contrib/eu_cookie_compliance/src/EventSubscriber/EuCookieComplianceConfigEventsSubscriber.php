<?php

namespace Drupal\eu_cookie_compliance\EventSubscriber;

use Drupal\Core\Config\ConfigCrudEvent;
use Drupal\Core\Config\ConfigEvents;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\Component\Serialization\Json;

/**
 * Updates a javascript on config save.
 *
 * @package Drupal\eu_cookie_compliance\EventSubscriber
 */
class EuCookieComplianceConfigEventsSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() : array {
    return [
      ConfigEvents::SAVE => 'configSave',
    ];
  }

  /**
   * React to a config object being saved.
   *
   * @param \Drupal\Core\Config\ConfigCrudEvent $event
   *   Config crud event.
   */
  public function configSave(ConfigCrudEvent $event) {

    if (($event->getConfig()->getName() === 'eu_cookie_compliance.settings')) {

      $disabled_javascripts = $event->getConfig()->get('disabled_javascripts');
      if ($disabled_javascripts) {
        $script_snippet = 'window.euCookieComplianceLoadScripts = function(category) {' . $this->getDisabledJsScriptSnippet($disabled_javascripts) . "}";

        // Check if already directory exists.
        $directory = "public://eu_cookie_compliance";
        if (!is_dir($directory) || !is_writable($directory)) {
          $file_system = \Drupal::service('file_system');
          $file_system->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
        }
        $uri = $directory . "/eu_cookie_compliance.script.js";
        if (is_writable($directory)) {
          if ((float) \Drupal::VERSION < 9.3) {
            file_save_data($script_snippet, $uri, FileSystemInterface::EXISTS_REPLACE);
          }
          else {
            \Drupal::service('file.repository')
              ->writeData($script_snippet, $uri, FileSystemInterface::EXISTS_REPLACE);
          }
        }
        else {
          \Drupal::messenger()
            ->addError($this->t('Could not generate the EU Cookie Compliance JavaScript file that would be used for handling disabled JavaScripts. There may be a problem with your files folder.'));
        }
      }
    }
  }

  /**
   * Build a disabled javascript snippet.
   *
   * @param string $disabled_javascripts
   *   Javascripts.
   *
   * @return string
   *   The script content.
   */
  public function getDisabledJsScriptSnippet($disabled_javascripts) : string {

    $load_disabled_scripts = '';
    // Initialize a variable to keep libraries to we wish to disable.
    if ($disabled_javascripts !== '') {
      $load_disabled_scripts = '';
      $disabled_javascripts = _eu_cookie_compliance_explode_multiple_lines($disabled_javascripts);
      $disabled_javascripts = array_filter($disabled_javascripts, 'strlen');

      foreach ($disabled_javascripts as $key => $script) {
        $parts = explode('%3A', $script);
        $category = NULL;
        if (count($parts) > 1) {
          $category = array_shift($parts);
          $script = implode(':', $parts);
        }

        // Split the string if a | is present.
        // The second parameter (after the |) will be used to trigger a script
        // attach.
        $attach_name = '';
        if (strpos($script, '%7C') !== FALSE) {
          // Swallow a notice in case there are no behavior or library names.
          @list($script, $attach_name) = explode('%7C', $script);
        }

        _eu_cookie_compliance_convert_relative_uri($script);

        if (strpos($script, 'http') !== 0 && strpos($script, '//') !== 0) {
          $script = '/' . $script;
        }

        // Store the actual script name, since we will need it later.
        $disabled_javascripts[$key] = $script;

        if ($category !== NULL) {
          $load_disabled_scripts .= 'if (category === "' . $category . '") {';
        }
        $load_disabled_scripts .= 'var scriptTag = document.createElement("script");';
        $load_disabled_scripts .= 'scriptTag.src = ' . Json::encode($script) . ';';
        $load_disabled_scripts .= 'document.body.appendChild(scriptTag);';
        // The script will not immediately load, so we need to trigger the
        // attach in an interval function.
        if ($attach_name) {
          $load_disabled_scripts .= 'var EUCookieInterval' . $attach_name . '= setInterval(function() { if (Drupal.behaviors.' . $attach_name . ' !== undefined) { Drupal.behaviors.' . $attach_name . '.attach(document, drupalSettings);clearInterval(EUCookieInterval' . $attach_name . ')};}, 100);';
        }
        if ($category !== NULL) {
          $load_disabled_scripts .= '}';
        }
      }
    }
    return $load_disabled_scripts;
  }

}
