/**
 * @file
 * Javascript for the Geolocation in Geofield Map.
 */

(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.geofieldMapGeolocation = {
    attach: function (context, settings) {

      let first_geofield_map = 0;
      let geolocation_position;

      // Trigger the HTML5 Geocoding only if defined.
      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(updateLocation, errorUpdateLocation);
      }

      // Update Location for each Geofield Map based on the HTML5 Geolocation
      // Position of the user.
      function updateLocation(position) {
        geolocation_position = position;
        // Set Location the HTML5 Geolocation Position for each Geofield Map
        // if not in the Geofield Field configuration page.
        if (!$(context).find("#edit-default-value-input").length && typeof geolocation_position !== 'undefined') {
          const geofield_maps_array = Object.entries(settings['geofield_map']).reverse();
          for (const [mapid, options] of geofield_maps_array) {
            if (mapid.includes('-0-value')) {
              first_geofield_map = 1;
            }
            else {
              first_geofield_map = 0;
            }
            if (options.geolocation) {
              updateGeoLocationFields($('#' + mapid, context).parents('.geofieldmap-widget-auto-geocode'), position, options);
            }
          }
        }
      }

      // Bind the "updateGeoLocationFields" click event to the
      // "geofield-html5-geocode-button" button.
      once('geofield_geolocation', 'input[name="geofield-html5-geocode-button"]').forEach(function (e) {
        $(e).click(function (e) {
          e.preventDefault();
          if (typeof geolocation_position !== 'undefined') {
            updateGeoLocationFields($(this).parents('.geofieldmap-widget-auto-geocode').parent(), geolocation_position, []);
          }
        });
      });

      // Update Geolocation Fields based on the user position,.
      function updateGeoLocationFields(fields, position, options) {
        if (options.length === 0 || (options['lat'] === 0 && options['lng'] === 0 && first_geofield_map === 1)) {
          fields.find('.geofield-lat').val(position.coords.latitude.toFixed(6)).trigger('change');
          fields.find('.geofield-lon').val(position.coords.longitude.toFixed(6)).trigger('change');
        }
      }

      // Error callback for getCurrentPosition.
      function errorUpdateLocation() {
        console.log("Couldn't find any HTML5 position");
      }
    }
  };
})(jQuery, Drupal);
