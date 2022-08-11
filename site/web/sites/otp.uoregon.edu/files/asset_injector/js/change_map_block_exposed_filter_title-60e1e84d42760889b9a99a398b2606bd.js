(function ($, Drupal) {
  Drupal.behaviors.theatersMapBlockExposedFilter = {
    attach: function (context, settings) {
      $("#views-exposed-form-oregon-theater-map-map-block label[for='edit-title']").attr('for', 'edit-title-map-block');
      $('#views-exposed-form-oregon-theater-map-map-block #edit-title').attr('id', 'edit-title-map-block');
      $("#views-exposed-form-oregon-theater-map-map-block label[for='edit-theater-city']").attr('for', 'edit-theater-city-map-block');
      $('#views-exposed-form-oregon-theater-map-map-block #edit-theater-city').attr('id', 'edit-theater-city-map-block');
      $('#views-exposed-form-oregon-theater-map-map-block #edit-submit-oregon-theater-map').attr('id', 'edit-submit-oregon-theater-map-block');
    }
  };
})(jQuery, Drupal);