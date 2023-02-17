<?php

namespace Drupal\block_content_revision_ui\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Block Content Revision UI event subscriber.
 */
class BlockContentRevisionUiSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    $revisionDeleteFormRoute = $collection->get('entity.block_content.revision_delete_form');
    $revisionDeleteFormRoute->setOption('_admin_route', TRUE);
  }

}
