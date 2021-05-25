<?php

namespace Drupal\os2loop_share_with_a_friend\Helper;

use Drupal\node\NodeInterface;
use Drupal\os2loop_share_with_a_friend\Form\SettingsForm;
use Drupal\os2loop_settings\Settings;

/**
 * Helper for os2loop_share_with_a_friend.
 */
class Helper {
  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Constructor.
   */
  public function __construct(Settings $settings) {
    $this->config = $settings->getConfig(SettingsForm::SETTINGS_NAME);
  }

  /**
   * Implements hook_os2loop_settings_is_granted().
   */
  public function isGranted(string $attribute, $object = NULL): bool {
    if ('share with a friend' === $attribute && $object instanceof NodeInterface) {
      $nodeTypes = $this->config->get('node_types');
      return $nodeTypes[$object->bundle()] ?? FALSE;
    }

    return FALSE;
  }

}
