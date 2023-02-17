/**
 * @file
 * Render a leaflet map with the timeline.
 */

(function ($, Drupal) {
  Drupal.behaviors.leafletMapTimeline = {
    attach: (context) => {
      // Declare map variable; initialized at the bottom of this function.
      let map;

      // Time slider control range bounds; min/max dates for features.
      // Set format for 1880 because of warning https://momentjs.com/guides/#/warnings/js-date/
      const minDate = moment("1880-1-1", "YYYY-MM-DD").toDate();
      const maxDate = moment("2020-12-31").toDate();

      // Dummy timeline, used to set the start / end bounds of the slider.
      const sliderBoundsTimeline = L.timeline({
        "type": "FeatureCollection", "features": [ { "type": "Feature",
          "properties": { "start": minDate, "end": maxDate, },
          "geometry": { "type": "Point", "coordinates": [0, 0], }, } ],
      });

      /* Define the default marker icon for the theater pins on the map. */
      const theaterIcon = L.icon({
        iconUrl: 'https://oregontheaterproject.uoregon.edu/themes/corporateplus/images/map_icons/film-roll.png',
        iconSize: [32, 32],
        iconAnchor: [16, 32],
        popupAnchor: [0, -15],
      });

      /**
       * Creates a marker with popup for a Theater GeoJSON feature.
       *
       * This function is to be provided as `L.timeline` option 'pointToLayer'.
       *
       * @param feature GeoJSON feature a marker should be generated for.
       * @param latlng Leaflet LatLng instance.
       *
       * @returns {*} Leaflet marker instance.
       */
      const getMarker = (feature, latlng) => {
        const popup_head_image = (feature.properties.image_url) ?
                                  '<div class="pin-description--image">\
                                    <a href="' + feature.properties.path + '">\
                                      <img src="' + feature.properties.image_url + '" alt="' + feature.properties.image_alt + '" typeof="Image" class="image-style-medium">\
                                    </a>\
                                  </div>'
                                  : '';
        return L.marker(latlng, {icon: theaterIcon})
                .bindPopup('\
                <article class="pin-description">\
                  <header class="pin-description--theater-name"><h1>\
                    <a href="' + feature.properties.path + '"> \
                    ' + feature.properties.name + '</a>\
                  </h1></header>\
                  ' + popup_head_image + '\
                  <div class="pin-description--year-range">\
                    (' + feature.properties.year_range_display + ')\
                  </div>\
                </article>');
      };

      /**
       * Produces a GeoJSON representation of the Theater JSON object passed in.
       *
       * @param json JSON:API serialized Theater nodes array object.
       *
       * @returns {
       *            {
       *              geometry: {coordinates: [number, number], type: string},
       *              type: string,
       *              properties: {
       *                start: Date, end: Date, year_range_display: string,
       *                image_url: string, image_alt: string,
       *                path: string, name: string
       *              }
       *            }
       *          } GeoJSON Theater feature.
       */
      const getTheaterGeoJSONFeature = (json) => {
        return json.data
        .filter((item) => item.attributes.field_location)
        .map((item) => {
          const dates = item.attributes.field_header_date;
          const dateStart = dates.value ?
            moment(dates.value).toDate() : minDate;
          const dateEnd = dates && dates.end_value ?
            moment(dates.end_value).toDate() : // Use the date specified.
            maxDate; // Use the last possible date in the timeline.
          const location = item.attributes.field_location;
          const head_image = item.relationships.field_header_image;
          const dateStartDisplay = moment(dateStart).format('YYYY');
          const dateEndDisplay = dateEnd != maxDate ? moment(dateEnd).format('YYYY') : 'Present';
          return {
            "type": "Feature",
            "properties": { // Other information can be added to properties.
              "path": item.attributes.path.alias,
              "name": item.attributes.title,
              "year_range_display": dateStartDisplay + " - " + dateEndDisplay,
              "start": dateStart,
              "end": dateEnd,
              // json.included has all related images.
              "image_url": head_image.data && json.included ?
                getImageUrl(json.included, head_image.data.id) : null,
              "image_alt": head_image.data ?
                head_image.data.meta.alt : item.attributes.title,
            },
            "geometry": {
              "type": location.geo_type,
              "coordinates": [ location.lon, location.lat ],
            },
          };
        });
      };

      /**
       * Find out the url based on the image file id in the JSON key "included".
       *
       * @param {*} imageArray, image object array from JSON key "included"
       * @param {*} imageId, image file id
       */
      const getImageUrl = (imageArray, imageId) => {
        if (!imageArray) {
          return null;
        }
        const targetImage = imageArray.filter((image) => image.id === imageId);
        if (!targetImage) {
          return null;
        }
        return targetImage[0].attributes.image_style_uri[0].medium; // Only select the first image.
      };

      /**
       * Map `json_data` objects to GeoJSON features and create a timeline.
       *
       * To be used as the callback function for ajax theater data loading.
       *
       * @param json_data A JSON:API specification compliant object.
       */
      function JSONLoaded(json_data) {
        /* Create cluster group with layer support */
        const cluster = L.markerClusterGroup.layerSupport().addTo(map);
        /* Create a timeline instance for the theaters in features. */
        const timeline = L.timeline({
          "type": "FeatureCollection",
          "features": getTheaterGeoJSONFeature(json_data)
        }, {
          pointToLayer: getMarker,
          onEachFeature: function (feature, layer) {
            cluster.checkIn(layer);
          }
        });
        /* Create slider controls instance for the timeline map. */
        const timelineControl = L.timelineSliderControl({
          formatOutput: (date) => moment(date).format("YYYY"),
          enableKeyboardControls: true,
          duration: 20000,
        });
        /*
         * Add the timeline and controls to the map.
         */
        timelineControl.addTo(map);
        timelineControl.addTimelines(timeline, sliderBoundsTimeline);
        // Intentionally not adding sliderBoundsTimeline to map.
        timeline.addTo(map);

        /* Change the date/time for the slider to a default date. */
        let defaultDate = moment('1920-06-15').valueOf();
        timelineControl.setTime(defaultDate);

        /* Inject year list markers for the slider. */
        const years = [];
        for (let current = 1880; current <= 2020; current += 10) {
          years.push(current);
        }
        const yearList = years.map((value, index) =>
          `<li${index % 2 === 1 ? ' class="short-list"' : ''} data-year="${value}">${value}</li>`
        );
        $('.leaflet-timeline-control').append(
          '<ol class="jump-to-year-list">\
            ' + yearList.join('') + '\
          </ol>'
        );
      }

      /*
       * Below is the Drupal way to ensure JavaScript code executes only once
       * per time meaningful content / context changes occur.
       *
       * Here our relevant context is when a container with ID
       * 'leaflet_map_timeline' (our block-created div) is attached to the DOM.
       */
      $('#leaflet_map_timeline', context)
        .once('leafletMapTimeline')
        .each(() => {

          /*
           * Initialize Leaflet Map.
           */
          // Tile setup.
          const esriWorldTopoMap = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Topo_Map/MapServer/tile/{z}/{y}/{x}', {
            attribution: 'Esri, HERE, Garmin, Intermap, INCREMENT P, GEBCO, USGS, FAO, NPS, NRCAN, GeoBase, IGN, Kadaster NL, Ordnance Survey, ' +
              'Esri Japan, METI, Esri China (Hong Kong), &copy; OpenStreetMap contributors, GIS User Community'
          });
          map = L.map('leaflet_map_timeline', {
            layers: [ esriWorldTopoMap ],
            center: [44.5216, -120.50000],
            zoom: 6,
            maxZoom: 18,
            minZoom: 6,
            scrollWheelZoom: false,
            tap: false,
            dragging: false,
            attributionControl: true,
          });
          map.attributionControl.setPosition('topright').addTo(map);
          /*
           * Do an AJAX lookup of theaters, which are passed to JSONLoaded().
           */
          $.ajax({
            url: "/jsonapi/node/theater?fields[node--theater]=title,path,field_location,field_header_date,field_header_image&include=field_header_image&page[limit]=250",
            method: "GET",
            headers: {"Accept": "application/vnd.api+json"},
            success: (data) => JSONLoaded(data),
          });

        });

    } // attach.
  }; // Drupal.behavior.leafletMapTimeline.
})(jQuery, Drupal);
;
jQuery(document).ready(function($) {
  $(window).scroll(function() {
    if($(this).scrollTop() != 0) {
      $(".to-top").addClass("show");
    } else {
      $(".to-top").removeClass("show");
    }
  });

  $(".to-top").click(function() {
    $("body,html").animate({scrollTop:0},800);
  });
});
;
