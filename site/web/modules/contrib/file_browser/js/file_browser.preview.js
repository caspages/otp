/**
 * @file
 * file_browser.preview.js
 */

(function ($, Drupal) {

  "use strict";

  /**
   * Contains logic for the view widget.
   */
  Drupal.behaviors.fileBrowserPreview = {
    attach: function (context, settings) {
      var wrapper = once('file-browser-preview', '#file-browser-preview-wrapper');
      if (wrapper.length) {
        $(wrapper).find('select').on('change', function () {
          Drupal.ajax({
            url: settings.file_browser.preview_path + '/' + $(this).val(),
            wrapper: 'file-browser-preview-wrapper'
          }).execute();
        });
      }
    }
  };

}(jQuery, Drupal));
