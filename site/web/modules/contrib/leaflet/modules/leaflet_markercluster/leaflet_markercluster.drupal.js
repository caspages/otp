/**
 * We are overriding the adding features functionality of the Leaflet module.
 */

(function ($) {
  Drupal.Leaflet.prototype.add_features = function (mapid, features, initial) {
    const leaflet_markercluster_options = this.settings.leaflet_markercluster.options && this.settings.leaflet_markercluster.options.length > 0 ? JSON.parse(this.settings.leaflet_markercluster.options) : {};
    const leaflet_markercluster_inlcude_path = this.settings.leaflet_markercluster.include_path;

    const cluster_layer = new L.MarkerClusterGroup(leaflet_markercluster_options);
    for (let i = 0; i < features.length; i++) {
      let feature = features[i];
      let lFeature;

      // dealing with a layer group
      if (feature.group) {
        let lGroup = new L.MarkerClusterGroup(leaflet_markercluster_options);
        for (let groupKey in feature.features) {
          let groupFeature = feature.features[groupKey];
          lFeature = this.create_feature(groupFeature);
          if (lFeature !== undefined) {
            if (lFeature.setStyle) {
              feature.path = feature.path ? (feature.path instanceof Object ? feature.path : JSON.parse(feature.path)) : {};
              lFeature.setStyle(feature.path);
            }
            if (groupFeature.popup) {
              lFeature.bindPopup(groupFeature.popup);
            }
            lGroup.addLayer(lFeature);
          }
        }

        // Correctly handle the groups here
        this.add_overlay(feature.label, lGroup, false, mapid);
      }
      else {
        lFeature = this.create_feature(feature);
        if (lFeature !== undefined) {

          if (lFeature.setStyle) {
            feature.path = feature.path ? (feature.path instanceof Object ? feature.path : JSON.parse(feature.path)) : {};
            lFeature.setStyle(feature.path);
          }

          // If the Leaflet feature is extending the Path class (Polygon,
          // Polyline, Circle) don't add it to Markercluster.
          if (lFeature.setStyle && !leaflet_markercluster_inlcude_path) {
            this.lMap.addLayer(lFeature);
            if (feature.popup) {
              lFeature.bindPopup(feature.popup);
            }
          }
          else {
            // this.lMap.addLayer(lFeature);
            cluster_layer.addLayer(lFeature);
            if (feature.popup) {
              lFeature.bindPopup(feature.popup);
            }
          }
        }
      }

      // Allow others to do something with the feature that was just added to the map
      $(document).trigger('leaflet.feature', [lFeature, feature, this]);
    }

    // Add all markers to the map
    this.lMap.addLayer(cluster_layer)

    // Allow plugins to do things after features have been added.
    $(document).trigger('leaflet.features', [initial || false, this])
  };

})(jQuery);
