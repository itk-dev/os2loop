<?php

namespace Drupal\os2loop_post_fixtures\Fixture;

use Drupal\content_fixtures\Fixture\AbstractFixture;
use Drupal\content_fixtures\Fixture\FixtureGroupInterface;
use Drupal\user\Entity\User;

/**
 * User fixture.
 *
 * @package Drupal\os2loop_post_fixtures\Fixture
 */
class UserFixture extends AbstractFixture implements FixtureGroupInterface {

  /**
   * {@inheritdoc}
   */
  public function load() {
    $user = User::create([
      'name' => 'os2loop_post_user',
      'mail' => 'os2loop_post_user@example.com',
      'pass' => 'os2loop_post_user-password',
      // Active.
      'status' => 1,
      'roles' => [
        'os2loop_user_administrator',
      ],
    ]);
    $user->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getGroups() {
    return ['os2loop_user'];
  }

}
