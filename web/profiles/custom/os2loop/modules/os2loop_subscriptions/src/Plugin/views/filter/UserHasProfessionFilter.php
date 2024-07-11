<?php

namespace Drupal\os2loop_subscriptions\Plugin\views\filter;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\views\Plugin\views\filter\StringFilter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Filter by user has profession.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("taxonomy_term_user_has_profession")
 */
final class UserHasProfessionFilter extends StringFilter {

  /**
   * Class constructor.
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public function __construct(
    $configuration,
    $plugin_id,
    $plugin_definition,
    Connection $connection,
    protected AccountInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $connection);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public static function create(ContainerInterface $container, $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    $this->ensureMyTable();

    /** @var \Drupal\views\Plugin\views\query\Sql $query */
    $query = $this->query;
    $table = array_key_first($query->tables);

    $user = $this->currentUser;

    if ($user) {
      // Terms value can't be empty.
      $defaultTerms = [0];
      $activeTermSubscriptions = $this->getActiveTermSubscriptions();
      $userProfessionsTermSubscriptions = $this->getTermSubscriptionsFromUserProfessions();

      // Merge all sources for terms.
      $termsValues = array_merge($defaultTerms, $activeTermSubscriptions, $userProfessionsTermSubscriptions);

      $query->addWhere($this->options['group'], $table . '.tid', $termsValues, 'IN');
    }
  }

  /**
   * Get terms that the user has subscribed to.
   *
   * @return array
   *   A list of term ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getActiveTermSubscriptions(): array {
    $termIds = [];

    $flags = $this->entityTypeManager->getStorage('flagging')->loadByProperties([
      'uid' => $this->currentUser->id(),
      'flag_id' => 'os2loop_subscription_term',
      'entity_type' => 'taxonomy_term',
    ]);

    foreach ($flags as $flag) {
      $termIds[] = $flag->entity_id->value;
    }

    return $termIds;
  }

  /**
   * Get terms from the users professions field.
   *
   * @return array
   *   A list of term ids.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  private function getTermSubscriptionsFromUserProfessions(): array {
    $termIds = [];

    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $userProfessions = $user->os2loop_user_professions->getValue();

    foreach ($userProfessions as $profession) {
      $termIds[] = $profession['target_id'];
    }

    return $termIds;
  }

}
