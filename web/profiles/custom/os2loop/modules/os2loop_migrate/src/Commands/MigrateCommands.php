<?php

namespace Drupal\os2loop_migrate\Commands;

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\media\Entity\Media;
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
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

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
    $this->entityTypeManager = $entityTypeManager;
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

  /**
   * Migrate collection documents.
   *
   * @command os2loop:migrate:files-to-media
   * @usage os2loop:migrate:files-to-media
   */
  public function migrateFilesToMedia() {
    $this->output()->writeln('Deleting all media');
    $storage = $this->entityTypeManager->getStorage('media');
    foreach ($storage->loadMultiple() as $media) {
      $media->delete();
    }
    $this->output()->writeln('');

    $migrations = [

      'User images' => [
        'sql' => "SELECT * FROM field_data_field_user_image WHERE entity_type = 'user'",
        // Map from result row bundle.
        'user' => [
          'media' => [
            'bundle' => 'os2loop_media_image',
            'field_name' => 'field_media_image',
            'fields' => [
              'target_id' => 'field_user_image_fid',
              'alt' => 'field_user_image_alt',
            ],
          ],
          'entity' => [
            'bundle' => 'user',
            'media_field_name' => 'os2loop_user_image',
          ],
        ],
      ],

      'Node files' => [
        'sql' => "SELECT * FROM field_data_field_file_upload WHERE entity_type = 'node'",
        'post' => [
          'media' => [
            'bundle' => 'os2loop_media_file',
            'field_name' => 'field_media_file',
            'fields' => [
              'target_id' => 'field_file_upload_fid',
            ],
          ],
          'entity' => [
            'bundle' => 'node',
            'media_field_name' => 'os2loop_question_file',
          ],
        ],
      ],

      'Comment files' => [
        'sql' => "SELECT * FROM field_data_field_file_upload_comment WHERE entity_type = 'comment'",
        'comment_node_post' => [
          'media' => [
            'bundle' => 'os2loop_media_file',
            'field_name' => 'field_media_file',
            'fields' => [
              'target_id' => 'field_file_upload_fid',
            ],
          ],
          'entity' => [
            'bundle' => 'comment',
            'media_field_name' => 'os2loop_question_file',
          ],
        ],
      ],

    ];

    foreach ($migrations as $name => $migration) {
      $this->output()->writeln($name);
      $items = Database::getConnection('default', 'migrate')->query(
        $migration['sql'],
        [],
        ['return' => Database::RETURN_STATEMENT]
      )->fetchAll();
      foreach ($items as $item) {
        $data = [
          'bundle' => $migration[$item->bundle]['media']['bundle'],
        ];
        foreach ($migration[$item->bundle]['media']['fields'] as $field => $property) {
          $data[$migration[$item->bundle]['media']['field_name']][$field] = $item->{$property};
        }
        $media = Media::create($data);
        $media->save();

        /** @var \Drupal\Core\Entity\ContentEntityBase $entity */
        $entity = $this->entityTypeManager->getStorage($migration[$item->bundle]['entity']['bundle'])->load($item->entity_id);
        $mediaFieldName = $migration[$item->bundle]['entity']['media_field_name'];
        $entity->set($mediaFieldName, ['target_id' => $media->id()]);
        $entity->save();

        $this->output()->writeln(sprintf('%s:%s.%s -> %s:%s', $entity->bundle(), $entity->id(), $mediaFieldName, $media->bundle(), $media->id()));
        $this->output()->writeln(sprintf('%s -> %s', $entity->toUrl('canonical', ['absolute' => TRUE])->toString(), $media->toUrl('canonical', ['absolute' => TRUE])->toString()));
      }
      $this->output()->writeln('');
    }
  }

}
