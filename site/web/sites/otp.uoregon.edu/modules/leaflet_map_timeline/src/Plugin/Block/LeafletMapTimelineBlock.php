<?php

namespace Drupal\leaflet_map_timeline\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a leaflet map timeline block.
 *
 * @Block(
 *   id = "leaflet_map_timeline_block",
 *   admin_label = @Translation("Leaflet Map with Timeline"),
 *   category = @Translation("Custom"),
 * )
 */
class LeafletMapTimelineBlock extends BlockBase implements BlockPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#markup' => '<h1 class="visually-hidden">Home</h1><div id="leaflet_map_timeline"></div>',
      '#attached' => [
        'library' => [
          'leaflet_map_timeline/map-timeline',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    return AccessResult::allowedIfHasPermission($account, 'access content');
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['leaflet_map_timeline_block_settings'] = $form_state->getValue('leaflet_map_timeline_block_settings');
  }

}
