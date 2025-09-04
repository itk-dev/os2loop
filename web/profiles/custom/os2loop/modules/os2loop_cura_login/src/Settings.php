<?php

namespace Drupal\os2loop_cura_login;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Settings for OS2Loop Cura login.
 */
final class Settings {
  const string CONFIG_NAME = 'os2loop_cura_login.settings';

  private const SETTING_SIGNING_SECRET = 'signing_secret';
  private const SETTING_SIGNING_ALGORITHM = 'signing_algorithm';
  private const SETTING_PAYLOAD_NAME = 'payload_name';
  private const SETTING_JWT_LEEWAY = 'jwt_leeway';
  private const SETTING_LOG_LEVEL = 'log_level';

  const array SIGNING_ALGORITHMS = [
    'HS256' => 'HS256',
    'HS384' => 'HS384',
    'HS512' => 'HS512',
  ];

  /**
   * The config.
   */
  private readonly ImmutableConfig $config;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    $this->config = $configFactory->get(self::CONFIG_NAME);
  }

  /**
   * Get payload name.
   */
  public function getPayloadName(): string {
    return $this->config->get('payload_name') ?? 'payload';
  }

  /**
   * Get signing algorithm.
   */
  public function getSigningAlgorithm(): string {
    return $this->config->get('signing_algorithm') ?? self::SIGNING_ALGORITHMS[array_key_first(self::SIGNING_ALGORITHMS)];
  }

  /**
   * Get signing secret.
   */
  public function getSigningSecret(): string {
    return $this->config->get('signing_secret') ?? '';
  }

  /**
   * Get JWT leeway.
   */
  public function getJwtLeeway(): int {
    return (int) $this->config->get('jwt_leeway');
  }

  /**
   * Get log level.
   */
  public function getLogLevel() {
    return (int) $this->config->get('log_level') ?? RfcLogLevel::ERROR;
  }

  /**
   * Save settings.
   */
  public function saveSettings(array|FormStateInterface $values): array {
    if ($values instanceof FormStateInterface) {
      $values = [
        self::SETTING_SIGNING_ALGORITHM => $values->getValue(self::SETTING_SIGNING_ALGORITHM),
        self::SETTING_SIGNING_SECRET => $values->getValue(self::SETTING_SIGNING_SECRET),
        self::SETTING_PAYLOAD_NAME => $values->getValue(self::SETTING_PAYLOAD_NAME),
        self::SETTING_JWT_LEEWAY => $values->getValue(self::SETTING_JWT_LEEWAY),
        self::SETTING_LOG_LEVEL => $values->getValue(self::SETTING_LOG_LEVEL),
      ];
    }

    // @todo validate values
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    return $config->get();
  }

}
