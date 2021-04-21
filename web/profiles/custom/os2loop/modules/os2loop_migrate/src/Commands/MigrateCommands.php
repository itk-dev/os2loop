<?php

namespace Drupal\os2loop_migrate\Commands;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\os2loop_documents\Entity\DocumentCollectionItem;
use Drupal\os2loop_documents\Helper\CollectionHelper;
use Drush\Commands\DrushCommands;

/**
 * A drush command file.
 *
 * @package Drupal\os2loop_migrate\Commands
 */
class MigrateCommands extends DrushCommands {
  /**
   * The DocumentCollectionItem storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  private $documentCollectionItemStorage;

  /**
   * The collection helper.
   *
   * @var \Drupal\os2loop_documents\Helper\CollectionHelper
   */
  private $collectionHelper;

  /**
   * The constructor.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, CollectionHelper $collectionHelper) {
    parent::__construct();
    $this->documentCollectionItemStorage = $entityTypeManager->getStorage('os2loop_document_collection_item');
    $this->collectionHelper = $collectionHelper;
  }

  /**
   * Migrate collection documents.
   *
   * @command os2loop:migrate:collection-documents
   * @usage os2loop:migrate:collections-documents
   */
  public function migrateCollectionDocuments() {
    if (!$this->confirm('Migrate documents in collections?')) {
      return;
    }
    // Remove all existing items.
    $this->output()->writeln('Removing all document collection items');
    $items = $this->documentCollectionItemStorage->loadMultiple();
    $this->documentCollectionItemStorage->delete($items);

    // Load menus from source database (migrate).
    $this->output()->writeln('Loading menu items from source database');
    $sql = 'SELECT * FROM menu_links WHERE menu_name LIKE \'loop-documents-collection-%\' ORDER BY menu_name, mlid, plid, p1, p2, p3, p4, p5, p6, p7, p8, p9';
    $menuLinks = Database::getConnection('default', 'migrate')->query(
      $sql,
      [],
      ['return' => Database::RETURN_STATEMENT]
    )->fetchAll();
    // Index by mlid.
    $menuLinks = array_column($menuLinks, NULL, 'mlid');
    // (object) array(
    // 'menu_name' => 'loop-documents-collection-864',
    // 'mlid' => '1344',
    // 'plid' => '0',
    // 'link_path' => 'node/865',
    // 'router_path' => 'node/%',
    // 'link_title' => 'Instruks for …',
    // 'options' => 'a:1:{s:10:"attributes";a:1:{s:5:"class";a:1:{i:0;s:21:"menu-node-unpublished";}}}',
    // 'module' => 'menu',
    // 'hidden' => '0',
    // 'external' => '0',
    // 'has_children' => '0',
    // 'expanded' => '1',
    // 'weight' => '1',
    // 'depth' => '1',
    // 'customized' => '0',
    // 'p1' => '1344',
    // 'p2' => '0',
    // 'p3' => '0',
    // 'p4' => '0',
    // 'p5' => '0',
    // 'p6' => '0',
    // 'p7' => '0',
    // 'p8' => '0',
    // 'p9' => '0',
    // 'updated' => '0',
    // 'language' => 'und',
    // 'i18n_tsid' => '0',
    // )
    $collections = [];
    // Map from mlid to document id.
    $documents = [];
    foreach ($menuLinks as $menuLink) {
      if (preg_match('/(?<collectionId>\d+)$/', $menuLink->menu_name, $matches)) {
        $id = (int) $matches['collectionId'];
        $collection = $this->collectionHelper->loadCollection($id);
        if (NULL === $collection) {
          // The collection menu may exist even if a collection has been
          // deleted.
          $this->output()->writeln(sprintf('Cannot load collection with id %d', $id));
          continue;
        }
        $collections[$collection->id()] = $collection;
        if (preg_match('/(?<documentId>\d+)$/', $menuLink->link_path, $matches)) {
          $id = (int) $matches['documentId'];
          $document = $this->collectionHelper->loadDocument($id);
          if (NULL === $document) {
            throw new \RuntimeException(sprintf('Cannot load document with id %d', $id));
          }
          $parentDocument = $documents[$menuLink->plid] ?? NULL;

          $values = [
            'collection_id' => $collection->id(),
            'document_id' => $document->id(),
            'parent_document_id' => NULL !== $parentDocument ? $parentDocument->id() : 0,
            'weight' => $menuLink->weight,
          ];
          DocumentCollectionItem::create($values)->save();

          $documents[$menuLink->mlid] = $document;

          $this->output()->writeln(json_encode($values));
        }
      }
    }

    $this->output()->writeln(sprintf('Migrated %d documents in %d collections', count($documents), count($collections)));
  }

}
