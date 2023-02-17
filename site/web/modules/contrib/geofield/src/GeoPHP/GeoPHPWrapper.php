<?php

namespace Drupal\geofield\GeoPHP;

/**
 * Provides a geoPHP Wrapper class.
 */
class GeoPHPWrapper implements GeoPHPInterface {

  /**
   * {@inheritdoc}
   */
  public function version() {
    return \geoPHP::version();
  }

  /**
   * {@inheritdoc}
   */
  public function load($data = NULL, $type = NULL) {
    try {
      return call_user_func_array(['\geoPHP', 'load'], func_get_args());
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAdapterMap() {
    return call_user_func_array(['\geoPHP', 'getAdapterMap'], func_get_args());
  }

}
