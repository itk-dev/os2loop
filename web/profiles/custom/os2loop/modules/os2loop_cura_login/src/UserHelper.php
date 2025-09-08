<?php

namespace Drupal\os2loop_cura_login;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\openid_connect\OpenIDConnect;
use Drupal\os2loop_cura_login\Trait\ControllerAwareTrait;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * User helper.
 */
final class UserHelper {
  use ControllerAwareTrait;

  /**
   * The user storage.
   */
  private readonly UserStorageInterface $userStorage;

  public function __construct(
    #[Autowire(service: 'openid_connect.openid_connect')]
    private readonly OpenIDConnect $openidConnect,
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

    // Make sure that the user is active.
    $user->activate();
    // saveUserinfo below needs a user id (uid).
    if ($user->isNew()) {
      $user->setUsername($username);
      $user->save();
    }

    // We piggyback on the OpenId Connect module to set user fields and roles.
    if ($this->openidConnect->saveUserinfo($user, ['userinfo' => $userinfo])) {
      $this->info('Userinfo saved on user @user (@username)', ['@user' => $user->label(), '@username' => $username]);
    }
    else {
      $this->error('Error saving info on user @user (@username)', ['@user' => $user->label(), '@username' => $username]);
    }

    return $user;
  }

  /**
   * Load user by username.
   */
  public function loadUser(string $username) : ?UserInterface {
    $users = $this->userStorage->loadByProperties(['name' => $username]);

    return reset($users) ?: NULL;
  }

  /**
   * Authenticate user.
   */
  public function authenticateUser($user) {
    user_login_finalize($user);
  }

}
