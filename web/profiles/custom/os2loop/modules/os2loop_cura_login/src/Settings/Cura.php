<?php

namespace Drupal\os2loop_cura_login\Settings;

/**
 * Settings for Cura.
 */
final class Cura {
  const string NAME = 'cura';

  const string SETTING_SIGNING_SECRET = 'signing_secret';
  const string SETTING_SIGNING_ALGORITHM = 'signing_algorithm';
  const string SETTING_PAYLOAD_NAME = 'payload_name';
  const string SETTING_JWT_LEEWAY = 'jwt_leeway';

  const array SIGNING_ALGORITHMS = [
    'HS256' => 'HS256',
    'HS384' => 'HS384',
    'HS512' => 'HS512',
  ];

  public function __construct(
    private readonly array $config,
  ) {
  }

  /**
   * Get payload name.
   */
  public function getPayloadName(): string {
    return $this->config[self::SETTING_PAYLOAD_NAME] ?? 'payload';
  }

  /**
   * Get signing algorithm.
   */
  public function getSigningAlgorithm(): string {
    return $this->config['signing_algorithm'] ?? self::SIGNING_ALGORITHMS[array_key_first(self::SIGNING_ALGORITHMS)];
  }

  /**
   * Get signing secret.
   */
  public function getSigningSecret(): string {
    return $this->config['signing_secret'] ?? '';
  }

  /**
   * Get JWT leeway.
   */
  public function getJwtLeeway(): int {
    return (int) ($this->config['jwt_leeway'] ?? 0);
  }

}
