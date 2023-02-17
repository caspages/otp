/**
 * @file
 * EU Cookie Compliance admin script.
 */

(function ($) {
  function showHideThankYouFields(showHide) {
    if (showHide) {
      $('.form-item--popup-find-more-button-message, .form-item--popup-hide-button-message, .form-item--popup-hide-agreed').show();
      $('.form-item--popup-agreed-value').parent().show();

      $('#edit-popup-agreed-value').attr('required','required');
      $('#edit-popup-find-more-button-message').attr('required','required');
      $('#edit-popup-hide-button-message').attr('required','required');

      $('#edit-popup-agreed-value').parent().parent().find('label').addClass('form-required');
      $('#edit-popup-find-more-button-message').parent().find('label').addClass('form-required');
      $('#edit-popup-hide-button-message').parent().find('label').addClass('form-required');
    }
    else {
      $('.form-item--popup-find-more-button-message, .form-item--popup-hide-button-message, .form-item--popup-hide-agreed').hide();
      $('.form-item--popup-agreed-value').parent().hide();

      $('#edit-popup-agreed-value').removeAttr('required');
      $('#edit-popup-find-more-button-message').removeAttr('required');
      $('#edit-popup-hide-button-message').removeAttr('required');

      $('#edit-popup-agreed-value').parent().parent().find('label').removeClass('form-required');
      $('#edit-popup-find-more-button-message').parent().find('label').removeClass('form-required');
      $('#edit-popup-hide-button-message').parent().find('label').removeClass('form-required');
    }
  }

  $(function () {
    showHideThankYouFields(document.getElementById('edit-popup-agreed-enabled').checked === true);

    $('#edit-popup-agreed-enabled').click(function () {
      showHideThankYouFields(this.checked === true);
    });
  });

} (jQuery));
