<?php

namespace Drupal\url_redirect\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\url_redirect\UrlRedirectInterface;

/**
 * Defines the Url Redirect entity.
 *
 * @ConfigEntityType(
 *   id = "url_redirect",
 *   label = @Translation("Url Redirect"),
 *   handlers = {
 *     "list_builder" =
 *   "Drupal\url_redirect\Controller\UrlRedirectListBuilder",
 *     "form" = {
 *       "add" = "Drupal\url_redirect\Form\UrlRedirectForm",
 *       "edit" = "Drupal\url_redirect\Form\UrlRedirectForm",
 *       "delete" = "Drupal\url_redirect\Form\UrlRedirectDeleteForm",
 *     }
 *   },
 *   config_prefix = "url_redirect",
 *   admin_permission = "access url redirect settings page",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "path" = "path",
 *     "redirect_path" = "redirect_path",
 *     "redirect_for" = "redirect_for",
 *     "negate" = "negate",
 *     "roles" = "roles",
 *     "users" = "users",
 *     "message" = "message",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/system/url_redirect/{url_redirect}",
 *     "delete-form" = "/admin/config/system/url_redirect/{url_redirect}/delete",
 *   },
 *   config_export = {
 *     "uuid",
 *     "id",
 *     "label",
 *     "path",
 *     "redirect_path",
 *     "redirect_for",
 *     "negate",
 *     "roles",
 *     "users",
 *     "message",
 *     "status"
 *   }
 * )
 */
class UrlRedirect extends ConfigEntityBase implements UrlRedirectInterface {

  /**
   * The UrlRedirect ID.
   *
   * @var string
   */
  public $id;

  /**
   * The UrlRedirect label.
   *
   * @var string
   */
  public $label;

  /**
   * The UrlRedirect path url.
   *
   * @var string
   */
  protected $path;

  /**
   * The Redirect path.
   *
   * @var string
   */
  protected $redirect_path;

  /**
   * Checked for Role/User.
   *
   * @var string
   */
  protected $redirect_for;

  /**
   * Roles used.
   *
   * @var string
   */
  protected $roles;

  /**
   * Users.
   *
   * @var string
   */
  protected $user;

  /**
   * Integer to indicate if negation of the condition is necessary.
   *
   * @var bool
   */
  protected $negate;

  /**
   * The CAS Site(s) details password.
   *
   * @var string
   */
  protected $message;

  /**
   * The CAS Site(s) details password.
   *
   * @var string
   */
  protected $status;

  /**
   * {@inheritdoc}
   */
  public function getPath() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectPath() {
    return $this->redirect_path;
  }

  /**
   * {@inheritdoc}
   */
  public function getCheckedFor() {
    return $this->redirect_for;
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles() {
    return $this->roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsers() {
    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->message;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function getNegate() {
    return $this->negate;
  }

}
