(function ($, Drupal) {
  Drupal.behaviors.leafletMapFullscreenButtonLinkFix = {
    attach: function (context, settings) {
      $(document).bind('leaflet.map', function(event, map, lMap) {
        $('.leaflet-control-fullscreen-button').html("<span class='sr-only'>View/exit fullscreen link</span>");
      });
    }
  };
})(jQuery, Drupal);