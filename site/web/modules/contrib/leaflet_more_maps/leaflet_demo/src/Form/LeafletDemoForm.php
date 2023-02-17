<?php

namespace Drupal\leaflet_demo\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Renderer;
use Drupal\leaflet\LeafletService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class LeafletDemoForm.
 */
class LeafletDemoForm extends FormBase {

  // Old Royal Observatory, Greenwich, near London, England.
  // It's right on the zero-meridian!
  const LEAFLET_DEMO_DEFAULT_LAT = 51.47774;
  const LEAFLET_DEMO_DEFAULT_LNG = -0.001164;
  const LEAFLET_DEMO_DEFAULT_ZOOM = 11;

  /**
   * The Leaflet Service.
   *
   * @var \Drupal\leaflet\LeafletService
   */
  protected $leafletService;

  /**
   * The Drupal Render Service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'leaflet_demo_page';
  }

  /**
   * LeafletDemoPage constructor.
   *
   * @param \Drupal\leaflet\LeafletService $leaflet_service
   *   The Leaflet Map service.
   * @param \Drupal\Core\Render\Renderer $renderer
   *   The drupal render service.
   */
  public function __construct(LeafletService $leaflet_service, Renderer $renderer) {
    $this->leafletService = $leaflet_service;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('leaflet.service'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;

    $values = $form_state->getUserInput();
    if (empty($values['latitude'])) {
      $latitude = LeafletDemoForm::LEAFLET_DEMO_DEFAULT_LAT;
      $longitude = LeafletDemoForm::LEAFLET_DEMO_DEFAULT_LNG;
    }
    else {
      $latitude = $values['latitude'];
      $longitude = $values['longitude'];
    }
    $zoom = isset($values['zoom']) ? $values['zoom'] : LeafletDemoForm::LEAFLET_DEMO_DEFAULT_ZOOM;

    $form['demo_map_parameters'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('Map parameters'),
      '#description' => $this->t('All maps below are centered on the same latitude, longitude and have the same initial zoom level.<br/>You may pan/drag and zoom each map individually.'),
    ];
    $form['demo_map_parameters']['latitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Latitude'),
      '#description' => $this->t('-90 .. 90 degrees'),
      '#size' => 12,
      '#default_value' => $latitude,
    ];
    $form['demo_map_parameters']['longitude'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Longitude'),
      '#description' => $this->t('-180 .. 180 degrees'),
      '#size' => 12,
      '#default_value' => $longitude,
    ];
    $form['demo_map_parameters']['zoom'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zoom'),
      '#field_suffix' => $this->t('(0..18)'),
      '#description' => $this->t('Some zoom levels may not be available in some maps.'),
      '#size' => 2,
      '#default_value' => $zoom,
    ];
    $form['demo_map_parameters']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit map parameters'),
    ];

    $form['#attached']['library'][] = 'leaflet_demo/leaflet_demo_form';

    $form['demo_maps'] = [
      '#type' => 'details',
      '#open' => TRUE,
      '#title' => $this->t('All available maps'),
      '#description' => '<em>' . $this->t('If some maps do not display, this may be due to a missing or invalid map provider API key.') . '<br/>' .
      $this->t('You can enter API keys <a href="@config_page">here</a>.', ['@config_page' => $base_url . '/admin/config/system/leaflet-more-maps/']) . '</em>',
    ];

    $form['demo_maps'] = array_merge($form['demo_maps'], $this->outputDemoMaps($latitude, $longitude, $zoom));

    return $form;
  }

  /**
   * Outputs the HTML for available Leaflet maps, centered on supplied coords.
   *
   * @param float $latitude
   *   The latitude, -90..90 degrees.
   * @param float $longitude
   *   The longitude, -180..180 degrees.
   * @param int $zoom
   *   The zoom level, typically 0..18.
   *
   * @return array
   *   An array of maps as renderable arrays.
   */
  protected function outputDemoMaps($latitude = LeafletDemoForm::LEAFLET_DEMO_DEFAULT_LAT, $longitude = LeafletDemoForm::LEAFLET_DEMO_DEFAULT_LNG, $zoom = LeafletDemoForm::LEAFLET_DEMO_DEFAULT_ZOOM) {

    if (!is_numeric($latitude) || !is_numeric($longitude) || !is_numeric($zoom)) {
      return [];
    }
    $center = ['lat' => $latitude, 'lon' => $longitude];
    $feature = [
      'type' => 'point',
      'lat' => $latitude,
      'lon' => $longitude,
      'popup' => $this->t('Location as entered above'),
    ];

    $demo_maps = [];
    $map_info = leaflet_map_get_info();

    foreach ($map_info as $map_id => $map) {
      $map['id'] = $feature['leaflet_id'] = str_replace(' ', '-', $map_id);
      $map['settings']['map_position'] = $center;
      $map['settings']['zoom'] = $zoom;

      $render_object = $this->leafletService->leafletRenderMap($map, [$feature], '350px');

      $demo_maps[$map_id] = [
        '#type' => 'item',
        '#title' => $map_info[$map_id]['label'],
        '#markup' => $this->renderer->render($render_object),
        '#attributes' => ['class' => ['leaflet-gallery-map']],
      ];
    }
    return $demo_maps;
  }

}
