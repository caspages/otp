<?php

namespace Drupal\eu_cookie_compliance\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Cookie category entities.
 */
interface CookieCategoryInterface extends ConfigEntityInterface {

  /**
   * Get this category's weight.
   *
   * @return int
   *   The weight of this category.
   */
  public function getWeight();

  /**
   * Sets the weight of this category.
   *
   * @param int $weight
   *   The weight to set.
   *
   * @return \Drupal\eu_cookie_compliance\Entity\CookieCategoryInterface
   *   The called class instance.
   */
  public function setWeight($weight);

}
