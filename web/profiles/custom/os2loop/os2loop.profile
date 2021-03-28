<?php

/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

use Drupal\block\Entity\Block;
use Drupal\Core\Session\AccountInterface;
use Drupal\node\NodeInterface;
use Drupal\os2loop\Helper\Helper;
use Drupal\Core\Form\FormStateInterface;

/**
 * Implements hook_entity_create_access().
 */
function os2loop_entity_create_access(AccountInterface $account, array $context, $entity_bundle) {
  return Drupal::service(Helper::class)->entityCreateAccess($account, $context, $entity_bundle);
}

/**
 * Implements hook_node_access().
 */
function os2loop_node_access(NodeInterface $node, $op, AccountInterface $account) {
  return Drupal::service(Helper::class)->nodeAccess($node, $op, $account);
}

/**
 * Implements hook_form_alter().
 */
function os2loop_form_alter(&$form, FormStateInterface $form_state, $form_id) {
  return Drupal::service(Helper::class)->formAlter($form, $form_state, $form_id);
}

/**
 * Implements hook_preprocess_node().
 */
function os2loop_preprocess_node(array &$variables) {
  return Drupal::service(Helper::class)->preprocessNode($variables);
}

/**
 * Implements hook_block_access().
 */
function os2loop_block_access(Block $block, $operation, AccountInterface $account) {
  return Drupal::service(Helper::class)->blockAccess($block, $operation, $account);
}
