<?php

namespace Drupal\eu_cookie_compliance\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller for JS call that checks if the visitor is in the EU.
 */
class CheckIfEuCountryJsController extends ControllerBase {

  /**
   * The Module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Creates a new VendorFileDownloadController instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module handler.
   */
  public function __construct(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler')
    );
  }

  /**
   * Check if visitor is in the EU.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Whether the user is in EU.
   */
  public function content() {
    $data = eu_cookie_compliance_user_in_eu();

    // Allow other modules to alter the geo IP matching logic.
    $this->moduleHandler->alter('eu_cookie_compliance_geoip_match', $data);

    return new JsonResponse($data, 200, ['Cache-Control' => 'private']);
  }

}
