<?php

/**
 * @file
 * API documentation for Leaflet More Maps.
 */

/**
 * Alters the default list of maps added in _leaflet_more_maps_assemble_default_map_info().
 *
 * With this hook you can add or alter layers add by leaflet more maps module
 * to this page admin/config/system/leaflet-more-maps.
 *
 * @param array $map_info
 *   Map info array.
 */
function hook_leaflet_more_maps_list_alter(array &$map_info) {
  // Example adding new cartodb map.
  $attr_cartodb = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>';
  $default_settings = [
    'attributionControl' => TRUE,
    'closePopupOnClick'  => TRUE,
    'doubleClickZoom'    => TRUE,
    'dragging'           => TRUE,
    'fadeAnimation'      => TRUE,
    'layerControl'       => FALSE,
    'maxZoom'            => 18,
    'minZoom'            => 0,
    'scrollWheelZoom'    => TRUE,
    'touchZoom'          => TRUE,
    'trackResize'        => TRUE,
    'zoomAnimation'      => TRUE,
    'zoomControl'        => TRUE,
  ];
  $cartodb_names = [
    'light_all',
    'light_nolabels',
    'light_only_labels',
    'dark_all',
    'dark_nolabels',
    'dark_only_labels',
    'rastertiles/voyager',
    'rastertiles/voyager_nolabels',
    'rastertiles/voyager_only_labels',
    'rastertiles/voyager_labels_under',
  ];
  foreach ($cartodb_names as $cartodb_name) {
    $code = mb_strtolower($cartodb_name);
    $label = t('Cartodb @name', ['@name' => $cartodb_name]);
    $url_template = "https://{s}.basemaps.cartocdn.com/$code/{z}/{x}/{y}{r}.png";
    $map_info["cartodb-$code"] = [
      'label'       => $label,
      'description' => $label,
      'settings'    => $default_settings,
      'layers'      => [
        '' => [
          'urlTemplate' => $url_template,
          'options'     => ['attribution' => $attr_cartodb],
        ],
      ],
    ];
  }
}
