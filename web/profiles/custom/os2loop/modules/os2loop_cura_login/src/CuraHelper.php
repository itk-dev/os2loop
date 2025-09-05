<?php

namespace Drupal\os2loop_cura_login;

use Drupal\os2loop_cura_login\Settings\Cura;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * Cura helper.
 */
final class CuraHelper {
  /**
   * The settings.
   */
  private Cura $settings;

  public function __construct(
    Settings $settings,
  ) {
    $this->settings = $settings->getCuraSettings();
  }

  /**
   * Decode JWT.
   */
  public function decodeJwt(string $jwt): array {
    $secret = $this->settings->getSigningSecret();

    $originalLeeway = JWT::$leeway;
    $leeway = $this->settings->getJwtLeeway();
    if ($leeway > 0) {
      JWT::$leeway = $leeway;
    }
    $payload = (array) JWT::decode($jwt, new Key($secret, $this->settings->getSigningAlgorithm()));
    JWT::$leeway = $originalLeeway;

    return [$payload, $payload['username'] ?? $payload['brugerId'] ?? NULL];
  }

}
