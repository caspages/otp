<?php

namespace Drupal\geofield_map\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\geofield\Element\GeofieldLatLon;
use Drupal\Core\Url;
use Drupal\Component\Utility\NestedArray;

/**
 * Provides a Geofield Map form element.
 *
 * @FormElement("geofield_map")
 */
class GeofieldMap extends GeofieldLatLon {

  /**
   * Generates the Geofield Map form element.
   *
   * @param array $element
   *   An associative array containing the properties and children of the
   *   element. Note that $element must be taken by reference here, so processed
   *   child elements are taken over into $form_state.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param array $complete_form
   *   The complete form structure.
   *
   * @return array
   *   The processed element.
   */
  public static function latLonProcess(array &$element, FormStateInterface $form_state, array &$complete_form) {

    /* @var \Drupal\Core\Config\ConfigFactoryInterface $config */
    $config = \Drupal::configFactory();
    $geofield_map_settings = $config->get('geofield_map.settings');
    /** @var \Drupal\geofield_map\Services\GoogleMapsService $google_maps_service */
    $google_maps_service = \Drupal::service('geofield_map.google_maps');

    /** @var \Drupal\Core\Session\AccountProxyInterface $currentUser */
    $currentUser = \Drupal::currentUser();

    // Initialize the basic geofield_map_element.
    static::elementProcess($element, $form_state, $complete_form);

    // Define the $mapid variable.
    $mapid = $element['#mapid'] = 'map-' . $element['#id'];

    // Map Element.
    $element['map'] = [
      '#type' => 'fieldset',
      '#weight' => 0,
    ];

    // Geocode address textfield and functionality.
    $gmap_geocoder_enabled = \Drupal::moduleHandler()->moduleExists('geocoder') && $element['#gmap_geocoder'];
    $message_recipient = t("(Note: This message is only shown to the Geofield Map module administrator ('Configure Geofield Map' permission).");
    if (strlen($element['#gmap_api_key']) > 0 || $gmap_geocoder_enabled) {
      $element['map']['geocode'] = [
        '#title' => t("Geocode address"),
        '#type' => 'textfield',
        '#description' => t("Use this to geocode your search location."),
        '#size' => 60,
        '#maxlength' => 128,
        '#attributes' => [
          'id' => 'search-' . $element['#id'],
          'class' => ['form-text', 'form-autocomplete', 'geofield-map-search'],
        ],
      ];

      if ($currentUser->hasPermission('configure geofield_map')) {
        $element['map']['geocode']['#description'] .= '<div class="geofield-map-message">' . t('Search Address Functionalities based on:') . ' ';
        if ($element['#gmap_geocoder']) {
          $element['map']['geocode']['#description'] .= t('Geocoder Module Providers.');
        }
        else {
          $element['map']['geocode']['#description'] .= t('Geofield Map GMaps API Key Geocoder.');
          $element['map']['geocode']['#description'] .= '<br>' . t('@google_places_autocomplete_message', [
            '@google_places_autocomplete_message' => !$element['#gmap_places'] ? 'Google Places Autocomplete Service disabled. Might be enabled in the Geofield Widget configuration.' : 'Google Places Autocomplete Service enabled.',
          ]);
        }
        $element['map']['geocode']['#description'] .= '<br>' . $message_recipient . '</div>';
      }
    }
    elseif ($currentUser->hasPermission('configure geofield_map')) {
      $geocoder_module_link = !\Drupal::moduleHandler()->moduleExists('geocoder') ? \Drupal::service('link_generator')->generate('Geocoder Module', Url::fromUri('https://www.drupal.org/project/geocoder', ['attributes' => ['target' => 'blank']])) : 'Geocoder Module';
      $element['map']['geocode_missing'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => t("Gmap Api Key missing (@settings_page_link) and @geocoder_module_link integration not enabled in this Geofield Widget configuration.<br>The Geocode & ReverseGeocode functionalities are not available.", [
          '@settings_page_link' => \Drupal::linkGenerator()->generate(t('in the Geofield Map Configuration Page'), Url::fromRoute('geofield_map.settings', [], [
            'query' => [
              'destination' => Url::fromRoute('<current>')
                ->toString(),
            ],
          ])),
          '@geocoder_module_link' => $geocoder_module_link,
        ]),
        '#attributes' => [
          'class' => ['geofield-map-message'],
        ],
      ];
      $element['map']['geocode_missing']['#value'] .= '<br>' . $message_recipient;
    }

    $element['map']['geofield_map'] = [
      '#theme' => 'geofield_map_widget',
      '#mapid' => $mapid,
      '#width' => isset($element['#map_dimensions']['width']) ? $element['#map_dimensions']['width'] : '100%',
      '#height' => isset($element['#map_dimensions']['height']) ? $element['#map_dimensions']['height'] : '450px',
    ];

    $element['map']['actions'] = [
      '#type' => 'actions',
    ];

    if (!empty($element['#click_to_find_marker']) && $element['#click_to_find_marker'] == TRUE) {
      $element['map']['actions']['click_to_find_marker'] = [
        '#type' => 'button',
        '#value' => t('Find marker'),
        '#name' => 'geofield-map-center',
        '#attributes' => [
          'id' => $element['#id'] . '-click-to-find-marker',
        ],
      ];
      $element['#attributes']['class'] = ['geofield-map-center'];
    }

    if (!empty($element['#click_to_place_marker']) && $element['#click_to_place_marker'] == TRUE) {
      $element['map']['actions']['click_to_place_marker'] = [
        '#type' => 'button',
        '#value' => t('Place marker here'),
        '#name' => 'geofield-map-marker',
        '#attributes' => [
          'id' => $element['#id'] . '-click-to-place-marker',
        ],
      ];
      $element['#attributes']['class'] = ['geofield-map-marker'];
    }

    // Add the HTML5 User Geolocation button functionality.
    if (!empty($element['#geolocation']) && $element['#geolocation'] == TRUE) {
      $element['#attached']['library'][] = 'geofield_map/geolocation';
      $element['map']['actions']['geolocation'] = [
        '#type' => 'button',
        '#value' => t('Find my location'),
        '#name' => 'geofield-html5-geocode-button',
        '#attributes' => ['mapid' => $mapid],
      ];
      $element['#attributes']['class'] = ['geofieldmap-widget-auto-geocode'];
    }

    // Define Lat and Lon sub-elements.
    $element['lat']['#weight'] = 10;
    $element['lon']['#weight'] = 20;
    $element['lat']['#attributes']['id'] = 'lat-' . $element['#id'];
    $element['lon']['#attributes']['id'] = 'lon-' . $element['#id'];
    if ($element['#hide_coordinates']) {
      $element['lat']['#attributes']['class'][] = 'visually-hidden';
      $element['lat']['#title_display'] = 'invisible';
      $element['lon']['#attributes']['class'][] = 'visually-hidden';
      $element['lon']['#title_display'] = 'invisible';
    }

    // Geoaddress Field Functionality and Settings.
    $address_field_exists = FALSE;
    if (!empty($element['#geoaddress_field']['field'])) {
      $address_field_name = $element['#geoaddress_field']['field'];
      $parents = array_slice($element['#array_parents'], 0, -4);
      $parents[] = $address_field_name;
      $address_field = NestedArray::getValue($complete_form, $parents, $address_field_exists);
      if ($address_field_exists && ($address_field['widget']['#cardinality'] == '-1' || $address_field['widget']['#cardinality'] > $element['#delta'])) {

        if ($element['#delta'] > 0 && !isset($address_field['widget'][$element['#delta']])) {
          $address_field['widget'][$element['#delta']] = $address_field['widget'][$element['#delta'] - 1];
          $address_field['widget'][$element['#delta']]['#delta'] = $element['#delta'];
          $address_field['widget'][$element['#delta']]['_weight']['#default_value'] = $element['#delta'];
          $address_field['widget'][$element['#delta']]['value']['#default_value'] = NULL;
        }

        $address_field['widget'][$element['#delta']]['value']['#description'] = (string) t('This value will be synchronized with the Geofield Map Reverse-Geocoded value.');
        if ($element['#geoaddress_field']['hidden']) {
          $address_field['#attributes']['class'][] = 'geofield_map_geoaddress_field_hidden';
        }
        if ($element['#geoaddress_field']['disabled']) {
          $address_field['widget'][$element['#delta']]['value']['#attributes']['readonly'] = 'readonly';
          $address_field['widget'][$element['#delta']]['value']['#description'] = (string) t('This field is readonly. It will be synchronized with the Geofield Map Reverse-Geocoded value.');
        }

        // Re-Generate the geoaddress_field #id.
        $address_field['widget'][$element['#delta']]['value']['#id'] = $element['#geoaddress_field']['field'] . '-' . $element['#delta'];
        NestedArray::setValue($complete_form, $parents, $address_field);
      }
    }

    // Attach Geofield Map Libraries.
    $element['#attached']['library'][] = 'geofield_map/geofield_map_general';
    $element['#attached']['library'][] = 'geofield_map/geofield_map_widget';
    // Conditionally use the Leaflet library from the D8 Module, if enabled.
    if ($element['#map_library'] == 'leaflet') {
      $element['#attached']['library'][] = \Drupal::moduleHandler()->moduleExists('leaflet') ? 'leaflet/leaflet' : 'geofield_map/leaflet';
    }

    // The Entity Form.
    /* @var \Drupal\Core\Entity\ContentEntityFormInterface $entity_form */
    $entity_form = $form_state->getBuildInfo()['callback_object'];
    $entity_operation = method_exists($entity_form, 'getOperation') ? $entity_form->getOperation() : 'any';

    $map_settings = [
      'entity_operation' => $entity_operation,
      'widget' => TRUE,
      'id' => $element['#id'],
      'mapid' => $mapid,
      'name' => $element['#name'],
      'lat' => floatval($element['lat']['#default_value']),
      'lng' => floatval($element['lon']['#default_value']),
      'zoom_start' => intval($element['#zoom']['start']),
      'zoom_focus' => intval($element['#zoom']['focus']),
      'zoom_min' => intval($element['#zoom']['min']),
      'zoom_max' => intval($element['#zoom']['max']),
      'latid' => $element['lat']['#attributes']['id'],
      'lngid' => $element['lon']['#attributes']['id'],
      'searchid' => isset($element['map']['geocode']) ? $element['map']['geocode']['#attributes']['id'] : NULL,
      'geoaddress_field_id' => $address_field_exists && isset($address_field['widget'][$element['#delta']]['value']['#id']) ? $address_field['widget'][$element['#delta']]['value']['#id'] : NULL,
      'gmap_places' => $element['#gmap_places'],
      'gmap_places_options' => $element['#gmap_places_options'],
      'gmap_geocoder' => 0,
      'gmap_geocoder_settings' => [],
      'map_library' => $element['#map_library'],
      'map_type' => $element['#map_type'],
      'map_type_selector' => $element['#map_type_selector'] ? TRUE : FALSE,
      'map_types_google' => $element['#map_types_google'],
      'map_types_leaflet' => $element['#map_types_leaflet'],
      'click_to_find_marker_id' => $element['#click_to_find_marker'] ? $element['map']['actions']['click_to_find_marker']['#attributes']['id'] : NULL,
      'click_to_find_marker' => $element['#click_to_find_marker'] ? TRUE : FALSE,
      'click_to_place_marker_id' => $element['#click_to_place_marker'] ? $element['map']['actions']['click_to_place_marker']['#attributes']['id'] : NULL,
      'click_to_place_marker' => $element['#click_to_place_marker'] ? TRUE : FALSE,
      'geolocation' => $element['#geolocation'],
      // Geofield Map Google Maps and Geocoder Settings.
      'gmap_api_localization' => $google_maps_service->getGmapApiLocalization($geofield_map_settings->get('gmap_api_localization')),
      'gmap_api_key' => $element['#gmap_api_key'] && strlen($element['#gmap_api_key']) > 0 ? $element['#gmap_api_key'] : NULL,
      'geocoder' => !empty($geofield_map_settings->get('geocoder')) ? $geofield_map_settings->get('geocoder') : [],
    ];

    // Add the Geofield Map Geocoder settings and library if Geocoder Search is
    // Enabled and Accessible.
    if (\Drupal::service('module_handler')->moduleExists('geocoder')
      && class_exists('\Drupal\geocoder\Controller\GeocoderApiEnpoints')
      && $element['#gmap_geocoder']
      && \Drupal::service('current_user')->hasPermission('access geocoder api endpoints')) {
      $map_settings['gmap_geocoder'] = $element['#gmap_geocoder'];
      $map_settings['gmap_geocoder_settings'] = $element['#gmap_geocoder_settings'];
      $element['#attached']['library'][] = 'geofield_map/geocoder';
    }

    // Allow other modules to add/alter the geofield map element settings.
    \Drupal::moduleHandler()->alter('geofield_map_latlon_element', $map_settings, $complete_form, $form_state->getValues());

    // Geofield Map Element specific mapid settings.
    $element['#attached']['drupalSettings']['geofield_map'][$mapid] = $map_settings;

    return $element;
  }

}
