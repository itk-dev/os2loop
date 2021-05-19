<?php

namespace Drupal\os2loop_mail_notifications\Helper;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\message\Entity\Message;
use Drupal\node\NodeInterface;
use Drupal\user\Entity\User;

/**
 * OS2Loop Mail notifications helper.
 */
class Helper {
  public const MODULE = 'os2loop_mail_notifications';

  /**
   * How often to run our cron task in seconds.
   */
  private const CRON_INTERVAL = 24 * 60 * 60;

  /**
   * Message template names.
   *
   * @var string[]
   */
  private static $messageTemplateNames = [
    'os2loop_message_answer_added',
    'os2loop_message_collection_added',
    'os2loop_message_collection_edit',
    'os2loop_message_comment_changed',
    'os2loop_message_document_added',
    'os2loop_message_document_edited',
    'os2loop_message_question_added',
    'os2loop_message_question_edited',
  ];

  /**
   * Subscription flag names.
   *
   * @var string[]
   */
  private static $subscriptionFlagNames = [
    'os2loop_subscription_node',
    'os2loop_subscription_term',
  ];

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * The database (connection).
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $database;

  /**
   * The mail helper.
   *
   * @var MailHelper
   */
  private $mailHelper;

  /**
   * The logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * Helper constructor.
   */
  public function __construct(StateInterface $state, EntityTypeManagerInterface $entityTypeManager, Connection $database, MailHelper $mailHelper, LoggerChannelFactoryInterface $loggerFactory) {
    $this->state = $state;
    $this->entityTypeManager = $entityTypeManager;
    $this->database = $database;
    $this->mailHelper = $mailHelper;
    $this->logger = $loggerFactory->get(static::MODULE);
  }

  /**
   * Implements hook_cron().
   */
  public function cron() {
    $now = new \DateTimeImmutable();
    $lastRunAt = $this->getLastRunAt();

    if ($now->getTimestamp() - $lastRunAt->getTimestamp() < static::CRON_INTERVAL) {
      return;
    }

    $this->sendNotifications();

    $this->setLastRunAt($now);
  }

  /**
   * Send notifications.
   */
  public function sendNotifications() {
    $messages = $this->getMessages();
    $users = $this->getUsers();

    foreach ($users as $user) {
      if (0 === $this->getNotificationInterval($user)) {
        continue;
      }

      $userMessages = $this->getUserMessages($user, $messages);
      if (!empty($userMessages)) {
        $groupedMessages = $this->groupMessages($userMessages);
        $success = $this->mailHelper->sendNotification($user, $groupedMessages);
        if ($success) {
          $this->logger->info(sprintf('Notification mail sent to %s', $user->getEmail()));
        }
        else {
          $this->logger->error(sprintf('Error sending motification mail to %s', $user->getEmail()));
        }
      }
    }
  }

  /**
   * Group messages by type and content.
   *
   * @param \Drupal\message\Entity\Message[] $messages
   *   The messages.
   *
   * @return array
   *   The grouped messages.
   */
  private function groupMessages(array $messages): array {
    $nodeIds = [];
    $groupedMessages = [];
    foreach ($messages as $message) {
      $type = $message->getTemplate()->id();
      $node = $this->getMessageNode($message);
      if (NULL !== $node) {
        if (!isset($nodeIds[$type][$node->id()])) {
          $groupedMessages[$type][$node->id()] = $message;
        }
        $nodeIds[$type][$node->id()] = $node->id();
      }
    }

    // @todo Remove notification on content edited if a the same content has been
    // created since last run.
    return $groupedMessages;
  }

  /**
   * Get messages.
   *
   * @return \Drupal\message\MessageInterface[]
   *   The messages.
   */
  private function getMessages() {
    $storage = $this->entityTypeManager
      ->getStorage('message');
    $ids = $storage
      ->getQuery()
      ->condition('template', static::$messageTemplateNames, 'IN')
      ->sort('created', 'DESC')
      ->execute();

    // @phpstan-ignore-next-line
    return $storage->loadMultiple($ids);
  }

  /**
   * Get users that subscribe to content or taxonomy terms.
   *
   * @return \Drupal\user\Entity\User[]
   *   The users.
   */
  private function getUsers() {
    $ids = $this->database
      ->select('flagging', 'f')
      ->fields('f', ['uid'])
      ->condition('flag_id', static::$subscriptionFlagNames, 'IN')
      ->execute()
      ->fetchAllKeyed(0, 0);

    // @phpstan-ignore-next-line
    return $this->entityTypeManager->getStorage('user')->loadMultiple($ids);
  }

  /**
   * Get messages relevant for a user.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user.
   * @param \Drupal\message\Entity\Message[] $messages
   *   All candidate messages.
   *
   * @return \Drupal\message\Entity\Message[]
   *   The messages for the user.
   */
  private function getUserMessages(User $user, array $messages): array {
    return array_filter($messages, function (Message $message) use ($user) {
      // Exclude messages generated by own actions.
      if ($message->getOwner() === $user) {
        return FALSE;
      }

      $node = $this->getMessageNode($message);
      if (NULL !== $node) {
        $userNodeIds = $this->getSubscribedNodeIds($user);
        if (in_array($node->id(), $userNodeIds, TRUE)) {
          return TRUE;
        }

        $subjectId = $node->get('os2loop_shared_subject')->getValue()[0]['target_id'] ?? NULL;
        if (NULL !== $subjectId) {
          $userSubjectIds = $this->getSubscribedTaxonomyTermIds($user);
          if (in_array($subjectId, $userSubjectIds, TRUE)) {
            return TRUE;
          }
        }
      }

      return FALSE;
    });
  }

  /**
   * Map from user id to nodes ids.
   *
   * @var array
   */
  private $userNodeIds;

  /**
   * Get ids of nodes a user subscribes to.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user.
   *
   * @return int[]
   *   The node ids.
   */
  private function getSubscribedNodeIds(User $user): array {
    if (NULL === $this->userNodeIds) {
      $result = $this->database
        ->select('flagging', 'f')
        ->fields('f', ['uid', 'entity_id'])
        ->condition('flag_id', 'os2loop_subscription_node')
        ->execute();
      foreach ($result as $row) {
        $this->userNodeIds[$row->uid][] = $row->entity_id;
      }
    }

    return $this->userNodeIds[$user->id()] ?? [];
  }

  /**
   * Map from user id to nodes ids.
   *
   * @var array
   */
  private $userTaxonomyTermIds;

  /**
   * Get ids of taxonomy terms a user subscribes to.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user.
   *
   * @return int[]
   *   The taxonomy term ids.
   */
  private function getSubscribedTaxonomyTermIds(User $user): array {
    if (NULL === $this->userTaxonomyTermIds) {
      $result = $this->database
        ->select('flagging', 'f')
        ->fields('f', ['uid', 'entity_id'])
        ->condition('flag_id', 'os2loop_subscription_term')
        ->execute();
      foreach ($result as $row) {
        $this->userTaxonomyTermIds[$row->uid][] = $row->entity_id;
      }
    }

    return $this->userTaxonomyTermIds[$user->id()] ?? [];
  }

  /**
   * Get node from a message.
   *
   * @param \Drupal\message\Entity\Message $message
   *   The message.
   *
   * @return \Drupal\node\NodeInterface|null
   *   The node if any.
   */
  private function getMessageNode(Message $message): ?NodeInterface {
    $field = $message->get('os2loop_message_node_refer');
    if (!($field instanceof EntityReferenceFieldItemListInterface)) {
      return NULL;
    }

    $nodes = $field->referencedEntities();

    // @phpstan-ignore-next-line
    return reset($nodes) ?: NULL;
  }

  /**
   * Get user notification interval.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user.
   */
  private function getNotificationInterval(User $user): int {
    return (int) ($user->get('os2loop_mail_notifications_intvl')->getValue()[0]['value'] ?: 0);
  }

  /**
   * Get last run at from state.
   *
   * @return \DateTimeInterface
   *   The time.
   */
  private function getLastRunAt(): \DateTimeInterface {
    $value = $this->getStateValue('last_run_at');
    try {
      return new \DateTimeImmutable($value ?: '1970-01-01T00:00:00');
    }
    catch (\Exception $exception) {
      return new \DateTimeImmutable('1970-01-01T00:00:00');
    }
  }

  /**
   * Set last run at in state.
   *
   * @param \DateTimeInterface $time
   *   The time.
   */
  private function setLastRunAt(\DateTimeInterface $time) {
    $this->setStateValue('last_run_at', $time->format($time::ATOM));
  }

  /**
   * Get module state value.
   *
   * @param string $key
   *   The key.
   * @param mixed $defaultValue
   *   The default value.
   *
   * @return mixed
   *   The state value if any.
   */
  private function getStateValue(string $key, $defaultValue = NULL) {
    $value = $this->state->get(static::MODULE);
    if (!is_array($value)) {
      $value = [];
    }

    return $value[$key] ?? $defaultValue;
  }

  /**
   * Set module state value.
   *
   * @param string $key
   *   The key.
   * @param mixed $value
   *   The value.
   */
  private function setStateValue(string $key, $value) {
    $stateValue = $this->state->get(static::MODULE);
    $stateValue[$key] = $value;
    $this->state->set(static::MODULE, $stateValue);
  }

}
