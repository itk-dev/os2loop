<?php

namespace Drupal\os2loop_cura_login\Settings;

/**
 * Settings for IdP.
 */
final class IdP {
  const string NAME = 'idp';

  const string USERNAME_CLAIM = 'username_claim';

  /**
   * Contructor.
   */
  public function __construct(
    private readonly array $config,
  ) {
  }

  /**
   * Get username claim.
   */
  public function getUsernameClaim(): string {
    return $this->config[self::USERNAME_CLAIM] ?? 'upn';
  }

}
