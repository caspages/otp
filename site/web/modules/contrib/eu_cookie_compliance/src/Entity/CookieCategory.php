<?php

namespace Drupal\eu_cookie_compliance\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Cookie category entity.
 *
 * @ConfigEntityType(
 *   id = "cookie_category",
 *   label = @Translation("Cookie category"),
 *   label_collection = @Translation("Cookie categories"),
 *   handlers = {
 *     "storage" = "Drupal\eu_cookie_compliance\CategoryStorageManager",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\eu_cookie_compliance\CookieCategoryListBuilder",
 *     "form" = {
 *       "add" = "Drupal\eu_cookie_compliance\Form\CookieCategoryForm",
 *       "edit" = "Drupal\eu_cookie_compliance\Form\CookieCategoryForm",
 *       "delete" = "Drupal\eu_cookie_compliance\Form\CookieCategoryDeleteForm"
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   config_prefix = "cookie_category",
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "checkbox_default_state",
 *     "weight",
 *   },
 *   admin_permission = "administer eu cookie compliance categories",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "description" = "description",
 *     "checkbox_default_state" = "checkbox_default_state",
 *     "weight" = "weight",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/system/eu-cookie-compliance/categories/add",
 *     "edit-form" = "/admin/config/system/eu-cookie-compliance/categories/{cookie_category}/edit",
 *     "delete-form" = "/admin/config/system/eu-cookie-compliance/categories/{cookie_category}/delete",
 *     "collection" = "/admin/config/system/eu-cookie-compliance/categories"
 *   }
 * )
 */
class CookieCategory extends ConfigEntityBase implements CookieCategoryInterface {

  /**
   * The Cookie category ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The Cookie category label.
   *
   * @var string
   */
  protected $label;

  /**
   * The Cookie category description.
   *
   * @var string
   */
  public $description;

  /**
   * The Cookie category cookie popup checkbox default state.
   *
   * @var bool
   */
  public $checkbox_default_state;

  /**
   * {@inheritDoc}
   */
  public function getWeight() {
    return $this->get('weight');
  }

  /**
   * {@inheritDoc}
   */
  public function setWeight($weight) {
    $this->set('weight', $weight);
    return $this;
  }

}
