<?php

/**
 * @file
 * Hooks specific to the EU Cookie Compliance module.
 */

/**
 * @addtogroup hooks
 *
 * Hooks that extend the EU Cookie Compliance module.
 */

/**
 * Alter the geo_ip_match variable.
 *
 * @param bool &$geoip_match
 *   Whether to show the cookie compliance banner.
 */
function hook_eu_cookie_compliance_geoip_match_alter(&$geoip_match) {
  $geoip_match['in_eu'] = FALSE;
}

/**
 * Take control of EU Cookie Compliance path exclusion.
 *
 * @param bool $excluded
 *   Whether this path is excluded from cookie compliance behavior.
 * @param string $path
 *   Current string path.
 * @param string $exclude_paths
 *   Admin variable of excluded paths.
 */
function hook_eu_cookie_compliance_path_match_alter(&$excluded, $path, $exclude_paths) {
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node && $node->type === 'my_type') {
    $excluded = TRUE;
  }
}

/**
 * Alter hook to provide advanced logic for hiding the banner.
 *
 * @param bool $show_popup
 *   Whether to show the banner.
 */
function hook_eu_cookie_compliance_show_popup_alter(&$show_popup) {
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node && $node->type === 'my_type') {
    $show_popup = FALSE;
  }
}

/**
 * Alter hook to modify the cache id of the banner data.
 *
 * @param bool $cid
 *   The cache id to store the banner data.
 */
function hook_eu_cookie_compliance_cid_alter(&$cid) {
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node && $node->type === 'my_type') {
    $cid .= ':' . $node->type;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
