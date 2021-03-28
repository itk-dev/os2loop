<?php

namespace Drupal\os2loop\Helper;

use Drupal\block\Entity\Block;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\os2loop\Form\SettingsForm;

/**
 * The helper.
 */
class Helper {
  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->config = $configFactory->get(SettingsForm::SETTINGS);
  }

  /**
   * Implements hook_entity_create_access().
   */
  public function entityCreateAccess(AccountInterface $account, array $context, $entity_bundle): AccessResult {
    return $this->contentTypeAccess($entity_bundle);
  }

  /**
   * Implements hook_node_access().
   */
  public function nodeAccess(NodeInterface $node, $op, AccountInterface $account): AccessResult {
    return $this->contentTypeAccess($node->bundle());
  }

  /**
   * Implements hook_form_alter().
   */
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) {
    $this->hideTaxonomyVocabularies($form);
  }

  /**
   * Implements hook_preprocess_node().
   */
  public function preprocessNode(array &$variables) {
    if (isset($variables['content'])) {
      $this->hideTaxonomyVocabularies($variables['content']);
    }
    if (isset($variables['elements'])) {
      $this->hideTaxonomyVocabularies($variables['elements']);
    }
  }

  /**
   * Implements hook_block_access().
   */
  public function blockAccess(Block $block, $operation, AccountInterface $account) {
    if ('view' === $operation) {
      $enabledTaxonomyVocabularies = array_filter($this->config->get('taxonomy_vocabulary') ?: [],
        static function ($value) {
          return 0 !== $value;
        });
      // Vocabulary name => Block id.
      $stuff = [
        'os2loop_subject' => 'os2loop_search_db_subject',
        'os2loop_tag' => 'os2loop_search_db_tags',
        'os2loop_profession' => 'os2loop_search_db_profession',
      ];
      foreach ($stuff as $vocabularyName => $blockId) {
        if ($blockId === $block->id() && !isset($enabledTaxonomyVocabularies[$vocabularyName])) {
          return AccessResult::forbidden();
        }
      }
    }

    return AccessResult::neutral();
  }

  /**
   * Check access to a node type.
   */
  private function contentTypeAccess(string $type) {
    $enabledNodeTypes = array_filter($this->config->get('node_type') ?: [], static function ($value) {
      return 0 !== $value;
    });

    if (!isset($enabledNodeTypes[$type])) {
      return AccessResult::forbidden(sprintf('Content type %s is not enabled', $type));
    }

    return AccessResult::neutral();
  }

  /**
   * Hide taxonomy vocabularies.
   *
   * @param array $element
   *   The element.
   */
  private function hideTaxonomyVocabularies(array &$element) {
    $enabledTaxonomyVocabularies = array_filter($this->config->get('taxonomy_vocabulary') ?: [], static function ($value) {
      return 0 !== $value;
    });

    // Vocabulary name => Field name.
    $stuff = [
      'os2loop_subject' => 'os2loop_shared_subject',
      'os2loop_tag' => 'os2loop_shared_tags',
      'os2loop_profession' => 'os2loop_shared_profession',
    ];

    foreach ($stuff as $vocabularyName => $fieldName) {
      if (isset($element[$fieldName]) && !isset($enabledTaxonomyVocabularies[$vocabularyName])) {
        unset($element[$fieldName]);
      }
    }
  }

}
