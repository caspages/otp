<?php

namespace Drupal\eu_cookie_compliance\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Controller for EUCC Access.
 */
class AdminController extends ControllerBase {

  /**
   * Access.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Whether the user has access.
   */
  public function access(AccountInterface $account) {
    return AccessResult::allowedIf(
      $account->hasPermission('administer eu cookie compliance popup') ||
      $account->hasPermission('administer eu cookie compliance categories')
    );
  }

}
