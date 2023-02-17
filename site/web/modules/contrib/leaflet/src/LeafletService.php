<?php

namespace Drupal\leaflet;

use Drupal\Core\File\Exception\InvalidStreamWrapperException;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\geofield\GeoPHP\GeoPHPInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\Utility\LinkGeneratorInterface;
use Drupal\Component\Serialization\Json;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a  LeafletService class.
 */
class LeafletService {

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The geoPhpWrapper service.
   *
   * @var \Drupal\geofield\GeoPHP\GeoPHPInterface
   */
  protected $geoPhpWrapper;

  /**
   * The module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The Link generator Service.
   *
   * @var \Drupal\Core\Utility\LinkGeneratorInterface
   */
  protected $link;

  /**
   * The stream wrapper manager.
   *
   * @var \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface
   */
  protected $streamWrapperManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Static cache for icon sizes.
   *
   * @var array
   */
  protected $iconSizes = [];

  /**
   * Creates an absolute web-accessible URL string.
   *
   * @todo switch to this same method of the @file_url_generator Drupal Core
   *   (since 9.3+) service once we fork on a branch not supporting 8.x anymore.
   *
   * @param string $uri
   *   The URI to a file for which we need an external URL, or the path to a
   *   shipped file.
   * @param bool $relative
   *   Whether to return a relative or absolute URL.
   *
   * @return string
   *   An absolute string containing a URL that may be used to access the
   *   file.
   *
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   If a stream wrapper could not be found to generate an external URL.
   */
  protected function doGenerateString(string $uri, bool $relative): string {
    // Allow the URI to be altered, e.g. to serve a file from a CDN or static
    // file server.
    $this->moduleHandler->alter('file_url', $uri);

    $scheme = StreamWrapperManager::getScheme($uri);

    if (!$scheme) {
      $baseUrl = $relative ? base_path() : $this->requestStack->getCurrentRequest()->getSchemeAndHttpHost() . base_path();
      return $this->generatePath($baseUrl, $uri);
    }
    elseif ($scheme == 'http' || $scheme == 'https' || $scheme == 'data') {
      // Check for HTTP and data URI-encoded URLs so that we don't have to
      // implement getExternalUrl() for the HTTP and data schemes.
      return $relative ? $this->transformRelative($uri) : $uri;
    }
    elseif ($wrapper = $this->streamWrapperManager->getViaUri($uri)) {
      // Attempt to return an external URL using the appropriate wrapper.
      $externalUrl = $wrapper->getExternalUrl();
      return $relative ? $this->transformRelative($externalUrl) : $externalUrl;
    }
    throw new InvalidStreamWrapperException();
  }

  /**
   * Generate a URL path.
   *
   * @todo switch to this same method of the @file_url_generator Drupal Core
   *   (since 9.3+) service once we fork on a branch not supporting 8.x anymore.
   *
   * @param string $base_url
   *   The base URL.
   * @param string $uri
   *   The URI.
   *
   * @return string
   *   The URL path.
   */
  protected function generatePath(string $base_url, string $uri): string {
    // Allow for:
    // - root-relative URIs (e.g. /foo.jpg in http://example.com/foo.jpg)
    // - protocol-relative URIs (e.g. //bar.jpg, which is expanded to
    //   http://example.com/bar.jpg by the browser when viewing a page over
    //   HTTP and to https://example.com/bar.jpg when viewing an HTTPS page)
    // Both types of relative URIs are characterized by a leading slash, hence
    // we can use a single check.
    if (mb_substr($uri, 0, 1) == '/') {
      return $uri;
    }
    else {
      // If this is not a properly formatted stream, then it is a shipped
      // file. Therefore, return the urlencoded URI with the base URL
      // prepended.
      $options = UrlHelper::parse($uri);
      $path = $base_url . UrlHelper::encodePath($options['path']);
      // Append the query.
      if ($options['query']) {
        $path .= '?' . UrlHelper::buildQuery($options['query']);
      }

      // Append fragment.
      if ($options['fragment']) {
        $path .= '#' . $options['fragment'];
      }

      return $path;
    }
  }

  /**
   * LeafletService constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user service.
   * @param \Drupal\geofield\GeoPHP\GeoPHPInterface $geophp_wrapper
   *   The geoPhpWrapper.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   * @param \Drupal\Core\Utility\LinkGeneratorInterface $link_generator
   *   The Link Generator service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $stream_wrapper_manager
   *   The stream wrapper manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The stream wrapper manager.
   */
  public function __construct(
    AccountInterface $current_user,
    GeoPHPInterface $geophp_wrapper,
    ModuleHandlerInterface $module_handler,
    LinkGeneratorInterface $link_generator,
    StreamWrapperManagerInterface $stream_wrapper_manager,
    RequestStack $request_stack
  ) {
    $this->currentUser = $current_user;
    $this->geoPhpWrapper = $geophp_wrapper;
    $this->moduleHandler = $module_handler;
    $this->link = $link_generator;
    $this->streamWrapperManager = $stream_wrapper_manager;
    $this->requestStack = $request_stack;
  }

  /**
   * Load all Leaflet required client files and return markup for a map.
   *
   * @param array $map
   *   The map settings array.
   * @param array $features
   *   The features array.
   * @param string $height
   *   The height value string.
   *
   * @return array
   *   The leaflet_map render array.
   */
  public function leafletRenderMap(array $map, array $features = [], $height = '400px') {
    $map_id = isset($map['id']) ? $map['id'] : Html::getUniqueId('leaflet_map');

    $attached_libraries = ['leaflet/general', 'leaflet/leaflet-drupal'];

    // Add the Leaflet Fullscreen library, if requested.
    if (isset($map['settings']['fullscreen']) && $map['settings']['fullscreen']['control']) {
      $attached_libraries[] = 'leaflet/leaflet.fullscreen';
    }

    // Add the Leaflet Gesture Handling library, if requested.
    if (!empty($map['settings']['gestureHandling'])) {
      $attached_libraries[] = 'leaflet/leaflet.gesture_handling';
    }

    // Add the Leaflet Markercluster library and functionalities, if requested.
    if ($this->moduleHandler->moduleExists('leaflet_markercluster') && isset($map['settings']['leaflet_markercluster']) && $map['settings']['leaflet_markercluster']['control']) {
      $attached_libraries[] = 'leaflet_markercluster/leaflet-markercluster';
      $attached_libraries[] = 'leaflet_markercluster/leaflet-markercluster-drupal';
    }

    // Add the Leaflet Geocoder library and functionalities, if requested,
    // and the user has access to Geocoder Api Enpoints.
    if (!empty($map['settings']['geocoder']['control'])) {
      $this->setGeocoderControlSettings($map['settings']['geocoder'], $attached_libraries);
    }

    $settings[$map_id] = [
      'mapid' => $map_id,
      'map' => $map,
      // JS only works with arrays, make sure we have one with numeric keys.
      'features' => array_values($features),
    ];
    return [
      '#theme' => 'leaflet_map',
      '#map_id' => $map_id,
      '#height' => $height,
      '#map' => $map,
      '#attached' => [
        'library' => $attached_libraries,
        'drupalSettings' => [
          'leaflet' => $settings,
        ],
      ],
    ];
  }

  /**
   * Get all available Leaflet map definitions.
   *
   * @param string $map
   *   The specific map definition string.
   *
   * @return array
   *   The leaflet maps definition array.
   */
  public function leafletMapGetInfo($map = NULL) {
    static $drupal_static_fast;
    if (!isset($drupal_static_fast)) {
      $drupal_static_fast['leaflet_map_info'] = &drupal_static(__FUNCTION__);
    }
    $map_info = &$drupal_static_fast['leaflet_map_info'];

    if (empty($map_info)) {
      if ($cached = \Drupal::cache()->get('leaflet_map_info')) {
        $map_info = $cached->data;
      }
      else {
        $map_info = $this->moduleHandler->invokeAll('leaflet_map_info');

        // Let other modules alter the map info.
        $this->moduleHandler->alter('leaflet_map_info', $map_info);

        \Drupal::cache()->set('leaflet_map_info', $map_info);
      }
    }

    if (empty($map)) {
      return $map_info;
    }
    else {
      return isset($map_info[$map]) ? $map_info[$map] : [];
    }

  }

  /**
   * Convert a geofield into an array of map points.
   *
   * The map points can then be fed into $this->leafletRenderMap().
   *
   * @param mixed $items
   *   A single value or array of geo values, each as a string in any of the
   *   supported formats or as an array of $item elements, each with a
   *   $item['wkt'] field.
   *
   * @return array
   *   The return array.
   */
  public function leafletProcessGeofield($items = []) {

    if (!is_array($items)) {
      $items = [$items];
    }
    $data = [];
    foreach ($items as $item) {
      // Auto-detect and parse the format (e.g. WKT, JSON etc).
      /* @var \GeometryCollection $geom */
      if (!($geom = $this->geoPhpWrapper->load(isset($item['wkt']) ? $item['wkt'] : $item))) {
        continue;
      }
      $data[] = $this->leafletProcessGeometry($geom);

    }
    return $data;
  }

  /**
   * Process the Geometry Collection.
   *
   * @param \Geometry $geom
   *   The Geometry Collection.
   *
   * @return array
   *   The return array.
   */
  private function leafletProcessGeometry(\Geometry $geom) {
    $datum = ['type' => strtolower($geom->geometryType())];

    switch ($datum['type']) {
      case 'point':
        $datum = [
          'type' => 'point',
          'lat' => $geom->getY(),
          'lon' => $geom->getX(),
        ];
        break;

      case 'linestring':
        /* @var \GeometryCollection $geom */
        $components = $geom->getComponents();
        /* @var \Geometry $component */
        foreach ($components as $component) {
          $datum['points'][] = [
            'lat' => $component->getY(),
            'lon' => $component->getX(),
          ];
        }
        break;

      case 'polygon':
        /* @var \GeometryCollection $geom */
        $tmp = $geom->getComponents();
        /* @var \GeometryCollection $geom */
        $geom = $tmp[0];
        $components = $geom->getComponents();
        /* @var \Geometry $component */
        foreach ($components as $component) {
          $datum['points'][] = [
            'lat' => $component->getY(),
            'lon' => $component->getX(),
          ];
        }
        break;

      case 'multipolyline':
      case 'multilinestring':
        if ($datum['type'] == 'multilinestring') {
          $datum['type'] = 'multipolyline';
          $datum['multipolyline'] = TRUE;
        }
        /* @var \GeometryCollection $geom */
        $components = $geom->getComponents();
        /* @var \GeometryCollection $component */
        foreach ($components as $key => $component) {
          $subcomponents = $component->getComponents();
          /* @var \Geometry $subcomponent */
          foreach ($subcomponents as $subcomponent) {
            $datum['component'][$key]['points'][] = [
              'lat' => $subcomponent->getY(),
              'lon' => $subcomponent->getX(),
            ];
          }
          unset($subcomponent);
        }
        break;

      case 'multipolygon':
        $components = [];
        /* @var \GeometryCollection $geom */
        $tmp = $geom->getComponents();
        /* @var \GeometryCollection $polygon */
        foreach ($tmp as $delta => $polygon) {
          $polygon_component = $polygon->getComponents();
          foreach ($polygon_component as $k => $linestring) {
            $components[] = $linestring;
          }
        }
        foreach ($components as $key => $component) {
          $subcomponents = $component->getComponents();
          /* @var \Geometry $subcomponent */
          foreach ($subcomponents as $subcomponent) {
            $datum['component'][$key]['points'][] = [
              'lat' => $subcomponent->getY(),
              'lon' => $subcomponent->getX(),
            ];
          }
        }
        break;

      case 'geometrycollection':
      case 'multipoint':
        /* @var \GeometryCollection $geom */
        $components = $geom->getComponents();
        foreach ($components as $key => $component) {
          $datum['component'][$key] = $this->leafletProcessGeometry($component);
        }
        break;

    }
    return $datum;
  }

  /**
   * Leaflet Icon Documentation Link.
   *
   * @return \Drupal\Core\GeneratedLink
   *   The Leaflet Icon Documentation Link.
   */
  public function leafletIconDocumentationLink() {
    return $this->link->generate(t('Leaflet Icon Documentation'), Url::fromUri('https://leafletjs.com/reference-1.3.0.html#icon', [
      'absolute' => TRUE,
      'attributes' => ['target' => 'blank'],
    ]));
  }

  /**
   * Set Feature Icon Size & Shadow Size If Empty or Invalid.
   *
   * @param array $feature
   *   The feature.
   */
  public function setFeatureIconSizesIfEmptyOrInvalid(array &$feature) {
    $icon_url = $feature["icon"]["iconUrl"] ?? NULL;
    if (isset($icon_url) && isset($feature["icon"]["iconSize"])
      && (empty(intval($feature["icon"]["iconSize"]["x"])) && empty(intval($feature["icon"]["iconSize"]["y"])))
      && (!empty($feature["icon"]["iconUrl"]))) {

      // Use the cached IconSize is present for this Icon Url.
      if (isset($this->iconSizes[$feature["icon"]["iconUrl"]])) {
        $feature["icon"]["iconSize"]["x"] = $this->iconSizes[$feature["icon"]["iconUrl"]]["x"];
        $feature["icon"]["iconSize"]["y"] = $this->iconSizes[$feature["icon"]["iconUrl"]]["y"];
      }
      elseif ($this->fileExists($feature["icon"]["iconUrl"])) {
        $file_parts = pathinfo($icon_url);
        switch ($file_parts['extension']) {
          case "svg":
            if ($xml = simplexml_load_file($icon_url)) {
              $attr = $xml->attributes();
              $feature["icon"]["iconSize"]["x"] = $attr->width->__toString();
              $feature["icon"]["iconSize"]["y"] = $attr->height->__toString();
            }
            break;

          default:
            if ($iconSize = getimagesize($icon_url)) {
              $feature["icon"]["iconSize"]["x"] = $iconSize[0];
              $feature["icon"]["iconSize"]["y"] = $iconSize[1];
            }
        }
        // Cache the IconSize, so we don't fetch the same icon multiple times.
        $this->iconSizes[$feature["icon"]["iconUrl"]] = $feature["icon"]["iconSize"];
      }
    }

    $shadow_url = $feature["icon"]["shadowUrl"] ?? NULL;
    if (isset($shadow_url) && isset($feature["icon"]["shadowSize"])
      && (empty(intval($feature["icon"]["shadowSize"]["x"])) && empty(intval($feature["icon"]["shadowSize"]["y"])))
      && (!empty($feature["icon"]["shadowUrl"]))) {

      // Use the cached Shadow IconSize is present for this Icon Url.
      if (isset($this->iconSizes[$feature["icon"]["shadowUrl"]])) {
        $feature["icon"]["shadowSize"]["x"] = $this->iconSizes[$feature["icon"]["shadowUrl"]]["x"];
        $feature["icon"]["shadowSize"]["y"] = $this->iconSizes[$feature["icon"]["shadowUrl"]]["y"];
      }
      elseif ($this->fileExists($feature["icon"]["shadowUrl"])) {
        $file_parts = pathinfo($shadow_url);
        switch ($file_parts['extension']) {
          case "svg":
            if ($xml = simplexml_load_file($shadow_url)) {
              $attr = $xml->attributes();
              $feature["icon"]["shadowSize"]["x"] = $attr->width->__toString();
              $feature["icon"]["shadowSize"]["y"] = $attr->height->__toString();
            }
            break;

          default:
            if ($shadowSize = getimagesize($shadow_url)) {
              $feature["icon"]["shadowSize"]["x"] = $shadowSize[0];
              $feature["icon"]["shadowSize"]["y"] = $shadowSize[1];
            }
        }
        // Cache the Shadow IconSize, so we don't fetch the same icon multiple
        // times.
        $this->iconSizes[$feature["icon"]["shadowUrl"]] = $feature["icon"]["shadowSize"];
      }
    }
  }

  /**
   * Check if a file exists.
   *
   * @param string $fileUrl
   *   The file url.
   *
   * @see https://stackoverflow.com/questions/10444059/file-exists-returns-false-even-if-file-exist-remote-url
   *
   * @return bool
   *   The bool result.
   */
  public function fileExists($fileUrl) {
    $file_headers = @get_headers($fileUrl);
    if (isset($file_headers) && !empty($file_headers[0])
      && (stripos($file_headers[0], "404 Not Found") == 0)
      && (stripos($file_headers[0], "403 Forbidden") == 0)
      && (stripos($file_headers[0], "302 Found") == 0 && !empty($file_headers[7]) && stripos($file_headers[7], "404 Not Found") == 0)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if an array has all values empty.
   *
   * @param array $array
   *   The array to check.
   *
   * @return bool
   *   The bool result.
   */
  public function multipleEmpty(array $array) {
    foreach ($array as $value) {
      if (empty($value)) {
        continue;
      }
      else {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Set Geocoder Controls Settings.
   *
   * @param array $geocoder_settings
   *   The geocoder settings.
   * @param array $attached_libraries
   *   The attached libraries.
   */
  public function setGeocoderControlSettings(array &$geocoder_settings, array &$attached_libraries): void {
    if ($this->moduleHandler->moduleExists('geocoder')
      && class_exists('\Drupal\geocoder\Controller\GeocoderApiEnpoints')
      && $geocoder_settings['control']
      && $this->currentUser->hasPermission('access geocoder api endpoints')) {
      $attached_libraries[] = 'leaflet/leaflet.geocoder';

      // Set the geocoder settings ['providers'] as the enabled ones.
      $enabled_providers = [];
      foreach ($geocoder_settings['settings']['providers'] as $plugin_id => $plugin) {
        if (!empty($plugin['checked'])) {
          $enabled_providers[] = $plugin_id;
        }
      }
      $geocoder_settings['settings']['providers'] = $enabled_providers;
      $geocoder_settings['settings']['options'] = [
        'options' => Json::decode($geocoder_settings['settings']['options']) ?? '',
      ];
    }
  }

  /**
   * Creates an absolute web-accessible URL string.
   *
   * @param string $uri
   *   The URI to a file for which we need an external URL, or the path to a
   *   shipped file.
   *
   * @return string
   *   An absolute string containing a URL that may be used to access the
   *   file.
   *
   * @throws \Drupal\Core\File\Exception\InvalidStreamWrapperException
   *   If a stream wrapper could not be found to generate an external URL.
   */
  public function generateAbsoluteString(string $uri): string {
    return $this->doGenerateString($uri, FALSE);
  }

  /**
   * Transforms an absolute URL of a local file to a relative URL.
   *
   * @todo switch to this same method of the @file_url_generator Drupal Core
   *   (since 9.3+) service once we fork on a branch not supporting 8.x anymore.
   *
   * May be useful to prevent problems on multisite set-ups and prevent mixed
   * content errors when using HTTPS + HTTP.
   *
   * @param string $file_url
   *   A file URL of a local file as generated by
   *   \Drupal\Core\File\FileUrlGenerator::generate().
   * @param bool $root_relative
   *   (optional) TRUE if the URL should be relative to the root path or FALSE
   *   if relative to the Drupal base path.
   *
   * @return string
   *   If the file URL indeed pointed to a local file and was indeed absolute,
   *   then the transformed, relative URL to the local file. Otherwise: the
   *   original value of $file_url.
   */
  public function transformRelative(string $file_url, bool $root_relative = TRUE): string {
    // Unfortunately, we pretty much have to duplicate Symfony's
    // Request::getHttpHost() method because Request::getPort() may return NULL
    // instead of a port number.
    $request = $this->requestStack->getCurrentRequest();
    $host = $request->getHost();
    $scheme = $request->getScheme();
    $port = $request->getPort() ?: 80;

    // Files may be accessible on a different port than the web request.
    $file_url_port = parse_url($file_url, PHP_URL_PORT) ?? $port;
    if ($file_url_port != $port) {
      return $file_url;
    }

    if (('http' == $scheme && $port == 80) || ('https' == $scheme && $port == 443)) {
      $http_host = $host;
    }
    else {
      $http_host = $host . ':' . $port;
    }

    // If this should not be a root-relative path but relative to the drupal
    // base path, add it to the host to be removed from the URL as well.
    if (!$root_relative) {
      $http_host .= $request->getBasePath();
    }

    return preg_replace('|^https?://' . preg_quote($http_host, '|') . '|', '', $file_url);
  }

}
