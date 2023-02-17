<?php

namespace Drupal\eu_cookie_compliance;

use Drupal\Core\Config\Entity\ConfigEntityStorage;

/**
 * The cookie category storage manager class.
 */
class CategoryStorageManager extends ConfigEntityStorage {

  /**
   * Load and return all active cookie categories.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   The loaded cookie categories.
   */
  public function getCookieCategories() {
    $categories = [];
    /** @var \Drupal\eu_cookie_compliance\Entity\CookieCategoryInterface[] $category_entities */
    $category_entities = $this->loadMultiple();
    foreach ($category_entities as $category_entity) {
      // Added this check to allow people to use domain access to change
      // the categories per domain. Unfortunately you can't create new
      // config entities only for a specific domain, but you can create
      // them for the normal site, and then set the status to false
      // and override it for the domain you want it to show up in, setting
      // the status to true for that domain.
      // Not ideal, but after the initial work setting it up it seems to
      // work fine.
      if ($category_entity->get('status')) {
        $categories[$category_entity->id()] = $category_entity->toArray();
      }
    }

    // Order the categories by their weight.
    uasort($categories, function ($a, $b) {
      return $a['weight'] - $b['weight'];
    });

    return $categories;
  }

  /**
   * Determine the next highest weight.
   *
   * @return int
   *   The next highest weight.
   */
  public function getCookieCategoryNextWeight() {
    /** @var \Drupal\eu_cookie_compliance\Entity\CookieCategoryInterface[] $cookies */
    $cookies = $this->loadMultiple();
    $weight = -10;

    foreach ($cookies as $cookie) {
      if ($cookie->getWeight() > $weight) {
        $weight = $cookie->getWeight();
      }
    }

    return $weight + 1;
  }

  /**
   * Returns an associative array of keys and labels for use in #options.
   *
   * @return array
   *   The options list for cookie default checkbox states.
   */
  public function getCheckboxDefaultStateOptionsList() {
    return [
      'unchecked' => $this->t('Unchecked by default'),
      'checked' => $this->t('Checked by default'),
      'required' => $this->t('Checked and disabled (user cannot clear the checkbox)'),
    ];
  }

}
