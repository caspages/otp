<?php

namespace Drupal\leaflet_more_maps\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form to enter global settings and to assemble custom maps from overlays.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Class constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   */
  public function __construct(ModuleHandlerInterface $moduleHandler, ConfigFactoryInterface $config_factory) {
    $this->moduleHandler = $moduleHandler;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'leaflet_more_maps_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    global $base_url;
    $config = $this->configFactory->get('leaflet_more_maps.settings');

    $form['global_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Map provider API keys/access tokens'),
      '#description' => $this->t("After you've entered and saved keys for the map provider(s) relevant to you, visit the <a href='@showcase_page' target='_showcase'>map showcase page</a> to check they work.", [
        '@showcase_page' => $base_url . '/admin/config/system/leaflet-more-maps/demo',
      ]),
      '#open' => TRUE,
    ];
    if (!$this->moduleHandler->moduleExists('leaflet_demo')) {
      $form['global_settings']['#description'] .= '<br/>' . $this->t('The Leaflet Demo module is currently not enabled. To see the map showcase page, please <a href="@extend" target="_extend">enable Leaflet Demo</a>.', [
        '@extend' => $base_url . '/admin/modules',
      ]);
    }

    $form['global_settings']['thunderforest_api_key'] = [
      '#type' => 'textfield',
      '#size' => 31,
      '#title' => $this->t('OSM Thunderforest API key'),
      '#default_value' => $config->get('thunderforest_api_key') ?? '',
      '#description' => $this->t('If you use a <a href="@thunderforest" target="_thunderforest_maps">Thunderforest</a> map, please <a href="@api_key" target="_api_key">obtain an API key</a> and paste it above.',
        [
          '@thunderforest' => 'https://www.thunderforest.com/maps',
          '@api_key' => 'http://www.thunderforest.com/docs/apikeys/',
        ]),
    ];

    $form['global_settings']['here_api_key'] = [
      '#type' => 'textfield',
      '#size' => 43,
      '#title' => $this->t('HERE API key'),
      '#default_value' => $config->get('here_api_key') ?? '',
      '#description' => $this->t('If you use a <a href="@here_maps" target="_here_maps">HERE</a> map, please sign up for an account, <a href="@api_key" target="_api_key">create an API key</a> and paste it above.',
        [
          '@here_maps' => 'https://here.com',
          '@api_key' => 'https://developer.here.com',
        ]),
    ];

    $form['global_settings']['mapbox_access_token'] = [
      '#type' => 'textfield',
      '#size' => 83,
      '#title' => $this->t('mapbox access token'),
      '#default_value' => $config->get('mapbox_access_token') ?? '',
      '#description' => $this->t('If you use a <a href="@mapbox" target="_mapbox_maps">mapbox</a> map, please create an account, <a href="@access_token target="_access_token">generate an access token</a> and paste it above.',
        [
          '@mapbox' => 'https://www.mapbox.com',
          '@access_token' => 'https://docs.mapbox.com/help/glossary/access-token',
        ]),
    ];

    $form['global_settings']['navionics'] = [
      '#type'  => 'fieldset',
      '#title' => $this
        ->t('Navionics'),
    ];
    $form['global_settings']['navionics']['help'] = [
      '#type'   => 'markup',
      '#markup' => $this->t('If you use a <a href="@navionics" target="_navionics_maps">Navionics</a> map, please sign up for an account, and <a href="@navionics_api" target="_navionics_token">create an api key</a>',
        [
          '@navionics'     => 'https://www.navionics.com',
          '@navionics_api' => 'https://www.navionics.com/aus/web-api/download',
        ]),
    ];
    $form['global_settings']['navionics']['navionics_api_key'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Navionics API Key'),
      '#description'   => $this->t('E.g. navionics_key'),
      '#default_value' => $config->get('navionics_api_key') ?? '',
    ];
    $form['global_settings']['navionics']['navionics_authorized_domain'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Domain name authorized by Navionics'),
      '#description'   => $this->t('Do not include http or https, e.g. <em>www.example.com</em>'),
      '#default_value' => $config->get('navionics_authorized_domain') ?? '',
    ];

    $map_info = [];

    _leaflet_more_maps_assemble_default_map_info($map_info);

    $all_layer_keys = [];
    foreach ($map_info as $map_key => $map) {
      foreach ($map['layers'] as $layer_key => $layer) {
        // Unique.
        $all_layer_keys["$map_key $layer_key"] = "$map_key $layer_key";
      }
    }
    $custom_map_layers = $config->get('leaflet_more_maps_custom_maps') ?? [];

    if (empty($custom_map_layers)) {
      for ($i = 1; $i <= LEAFLET_MORE_MAPS_NO_CUSTOM_MAPS; $i++) {
        $custom_map_layers[$i] = [
          'map-key' => '',
          'layer-keys' => [],
          'reverse-order' => FALSE,
        ];
      }
    }
    for ($i = 1; $i <= LEAFLET_MORE_MAPS_NO_CUSTOM_MAPS; $i++) {
      $form['map'][$i] = [
        '#type' => 'details',
        '#open' => $i <= 1,
        '#title' => $this->t('Custom map #@number layer selection', ['@number' => $i]),
      ];
      $form['map'][$i]['map-key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name of custom map #@number in the administrative UI', ['@number' => $i]),
        '#default_value' => $custom_map_layers[$i]['map-key'] ?? '',
        '#description' => $this->t('Use a blank field to remove this layer configuration from the set of selectable maps.'),
      ];
      $form['map'][$i]['layer-keys'] = [
        '#type' => 'checkboxes',
        '#title' => $this->t('Select one or more layers to be included in this map.'),
        '#options' => $all_layer_keys,
        '#default_value' => $custom_map_layers[$i]['layer-keys'] ?? [],
        '#description' => $this->t('If you select two or more layers, these will be selectable via radio buttons in the layer switcher on your map.'),
      ];
      $form['map'][$i]['reverse-order'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Reverse order in layer switcher'),
        '#default_value' => $custom_map_layers[$i]['reverse-order'] ?? FALSE,
        '#description' => $this->t('The last layer in the switcher will be the default.'),
      ];
      $form['map'][$i]['map-key']['#parents'] = ['map', $i, 'map-key'];
      $form['map'][$i]['layer-keys']['#parents'] = ['map', $i, 'layer-keys'];
      $form['map'][$i]['reverse-order']['#parents'] =
        ['map', $i, 'reverse-order'];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $custom_maps = $form_state->getValue('map');

    // Clear out the unticked boxes before saving the form.
    foreach ($custom_maps as $i => $custom_map) {
      $custom_map['layer-keys'] = array_filter($custom_map['layer-keys']);
      if (empty($custom_map['map-key']) || empty($custom_map['layer-keys'])) {
        unset($custom_maps[$i]);
      }
    }

    $this->config('leaflet_more_maps.settings')
      ->set('thunderforest_api_key', $form_state->getValue('thunderforest_api_key'))
      ->set('mapbox_access_token', $form_state->getValue('mapbox_access_token'))
      ->set('here_api_key', $form_state->getValue('here_api_key'))
      ->set('navionics_api_key', $form_state->getValue('navionics_api_key'))
      ->set('navionics_authorized_domain', $form_state->getValue('navionics_authorized_domain'))
      ->set('leaflet_more_maps_custom_maps', $custom_maps)
      ->save();

    parent::submitForm($form, $form_state);
    // @todo Need to refresh config cache.
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['leaflet_more_maps.settings'];
  }

}
