<?php

namespace Drupal\os2loop_revision_date\Plugin\Action;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Action\ActionBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Sets revision date on content.
 *
 * @Action(
 *   id = "os2loop_revision_date_set_action",
 *   label = @Translation("Set revision date"),
 *   type = "node"
 * )
 */
class SetRevisionDate extends ActionBase implements ContainerFactoryPluginInterface {

  /**
   * Date time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $timeService;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, TimeInterface $time_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->timeService = $time_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('datetime.time')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access($object, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\node\NodeInterface $object */
    $result = $object->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

  /**
   * {@inheritdoc}
   */
  public function execute($node = NULL) {
    if ($node) {
      /** @var \Drupal\node\NodeInterface $node */
      if (!$node->hasField('os2loop_shared_rev_date')) {
        throw new \RuntimeException("Revisioning date field not found on node.");
      }
      $node->set('os2loop_shared_rev_date', date('Y-m-d', $this->timeService->getCurrentTime() + $this->getTimeSpan()));
      $node->save();
    }
  }

  /**
   * Get timespan from action configuration yml.
   *
   * @return int
   *   A timespan in seconds.
   */
  private function getTimeSpan() : int {
    return isset($this->configuration['time_span']) ? $this->configuration['time_span'] : 0;
  }

}
