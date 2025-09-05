<?php

namespace Drupal\os2loop_cura_login;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;

/**
 * User helper.
 */
final class UserHelper {
  /**
   * The user storage.
   */
  private readonly UserStorageInterface $userStorage;

  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
  ) {
    $this->userStorage = $entityTypeManager->getStorage('user');
  }

  /**
   * Ensure user exists.
   *
   * @param string $username
   *   The username.
   * @param array $userinfo
   *   The user info to set on the user.
   *
   * @return \Drupal\user\Entity\UserInterface
   *   The newly created or updated user.
   */
  public function ensureUser(string $username, array $userinfo): UserInterface {
    $user = $this->loadUser($username);

    if (NULL === $user) {
      $user = $this->userStorage->create();
    }

    foreach ($userinfo as $field => $value) {
      $currentValue = $user->get($field);
      if ($currentValue !== $value) {
        $user->set($field, $value);
      }
    }

    // Make sure that the user is active.
    $user
      ->activate()
      ->save();

    return $user;
  }

  /**
   * Load user by username.
   */
  public function loadUser(string $username) : ?UserInterface {
    $users = $this->userStorage->loadByProperties(['name' => $username]);

    return reset($users) ?: NULL;
  }

}
