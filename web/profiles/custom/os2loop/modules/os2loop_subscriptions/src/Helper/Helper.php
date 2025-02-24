<?php

namespace Drupal\os2loop_subscriptions\Helper;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\flag\FlagInterface;
use Drupal\node\NodeInterface;
use Drupal\os2loop_settings\Settings;
use Drupal\os2loop_subscriptions\Form\SettingsForm;
use Drupal\taxonomy\Entity\Term;

/**
 * Helper for os2loop_subscriptions.
 */
class Helper {
  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The database (connection).
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * Constructor.
   */
  public function __construct(Settings $settings, Connection $database, private EntityTypeManagerInterface $entityTypeManager) {
    $this->config = $settings->getConfig(SettingsForm::SETTINGS_NAME);
    $this->database = $database;
  }

  /**
   * Implements hook_os2loop_settings_is_granted().
   *
   * Handle access for favourite and subscribe flags on node types.
   */
  public function isGranted(string $attribute, $object = NULL): bool {
    if ($object instanceof NodeInterface) {
      if ('favourite' === $attribute) {
        $nodeTypes = $this->config->get('favourite_node_types');
        return $nodeTypes[$object->bundle()] ?? FALSE;
      }
      if ('subscribe' === $attribute) {
        $nodeTypes = $this->config->get('subscribe_node_types');
        return $nodeTypes[$object->bundle()] ?? FALSE;
      }
    }

    return FALSE;
  }

  /**
   * Get ids of users with a subscription on a term.
   *
   * @param \Drupal\taxonomy\Entity\Term $term
   *   The term.
   *
   * @return array
   *   The user ids.
   */
  public function getSubscribedUserIds(Term $term): array {
    return $this->database
      ->select('flagging', 'f')
      ->fields('f', ['uid'])
      ->condition('flag_id', 'os2loop_subscription_term')
      ->condition('entity_id', $term->id())
      ->execute()
      ->fetchCol();
  }

  /**
   * Change access result for flag action.
   *
   * @param string $action
   *   The flag action flag/unflag.
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag being checked.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   * @param \Drupal\Core\Entity\EntityInterface|null $flaggable
   *   The actual flagging.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   The access result.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function flagActionAccess(string $action, FlagInterface $flag, AccountInterface $account, EntityInterface $flaggable = NULL): AccessResult {
    // Only check unflagging on os2loop_subscription_terms.
    if ('os2loop_subscription_term' !== $flag->id() || 'flag' === $action) {
      return AccessResult::neutral();
    }

    $subscriptionRequiredTaxonomies = $this->config->get('subscription_required_taxonomy');

    // Only act if subscription_required_taxonomy is set for this term.
    if (!in_array($flaggable->bundle(), $subscriptionRequiredTaxonomies)) {
      return AccessResult::neutral();
    }

    // Don't allow last unflagging within a taxonomy term bundle.
    if (1 == count($this->getTermSubscriptionsOnType($flaggable->bundle(), $account))) {
      return AccessResult::forbidden();
    }

    // Fallback.
    return AccessResult::neutral();
  }

  /**
   * Get term subscriptions for a taxonomy bundle and a user.
   *
   * @param string $bundle
   *   The bundle.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   A user account.
   *
   * @return array
   *   List of subscription flaggings.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getTermSubscriptionsOnType(string $bundle, AccountInterface $account): array {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->getQuery()
      ->condition('vid', $bundle, '=')
      ->accessCheck()
      ->execute();

    $flaggings = $this->entityTypeManager->getStorage('flagging')
      ->getQuery()
      ->condition('entity_id', $terms, 'IN')
      ->condition('uid', $account->id(), '=')
      ->condition('flag_id', 'os2loop_subscription_term', '=')
      ->accessCheck()
      ->execute();

    return $flaggings;
  }

}
