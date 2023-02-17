<?php

namespace Drupal\eu_cookie_compliance\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 */
class CheckIfEuCountryJs {

  /**
   * Routes.
   *
   * @return array
   *   List of routes.
   */
  public function routes() {
    $routes = [];
    if (\Drupal::config('eu_cookie_compliance.settings')->get('eu_only_js')) {
      $routes['eu_cookie_compliance.check_if_eu_country_js'] = new Route(
        '/eu-cookie-compliance-check',
        [
          '_controller' => '\Drupal\eu_cookie_compliance\Controller\CheckIfEuCountryJsController::content',
        ],
        [
          '_permission' => 'access content',
        ]
      );
    }
    return $routes;
  }

}
