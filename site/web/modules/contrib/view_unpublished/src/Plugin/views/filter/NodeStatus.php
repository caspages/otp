<?php

namespace Drupal\view_unpublished\Plugin\views\filter;

use Drupal\node\Entity\NodeType;
use Drupal\node\Plugin\views\filter\Status;

/**
 * Filter by view all unpublished permissions granted by view_unpublished.
 *
 * Takes over the Published or Admin filter query.
 *
 * @ingroup views_filter_handlers
 */
class NodeStatus extends Status {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = $this->ensureMyTable();
    $snippet = "$table.status = 1 OR ($table.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_NODES*** = 1) OR ***VIEWUNPUBLISHED_ANY*** = 1 OR ***BYPASS_NODE_ACCESS*** = 1";
    if ($this->moduleHandler->moduleExists('content_moderation')) {
      $snippet .= ' OR ***VIEW_ANY_UNPUBLISHED_NODES*** = 1';
    }

    $where_per_type = [];
    foreach (NodeType::loadMultiple() as $type) {
      $type_id = $type->id();
      $where_per_type[] = "($table.type = '$type_id' AND ***VIEWUNPUBLISHED_TYPE_$type_id*** = 1)";
    }
    if (!empty($where_per_type)) {
      $where_per_type = implode(' OR ', $where_per_type);
      $snippet .= " OR $where_per_type";
    }

    $this->query->addWhereExpression($this->options['group'], $snippet);
  }

}
