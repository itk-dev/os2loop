<?php

namespace Drupal\os2loop_documents;

use Drupal\views\EntityViewsData;

/**
 * Provides the views data for the os2loop_document_collection_item entity type.
 */
class ItemViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Add relationships to the collection and document nodes.
    // https://api.drupal.org/api/drupal/core%21modules%21views%21views.api.php/function/hook_views_data/11.x
    $data['os2loop_documents_collection_item']['collection_id']['relationship'] = [
      'base' => 'node_field_data',
      'base field' => 'nid',
      'id' => 'standard',
      'title' => $this->t('Collection'),
      'help' => $this->t('The document collection'),
    ];

    $data['os2loop_documents_collection_item']['document_id']['relationship'] = [
      'base' => 'node_field_data',
      'base field' => 'nid',
      'id' => 'standard',
      'title' => $this->t('Document'),
      'help' => $this->t('The document'),
    ];

    return $data;
  }

}
