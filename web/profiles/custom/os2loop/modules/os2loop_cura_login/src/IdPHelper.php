<?php

namespace Drupal\os2loop_cura_login;

use Drupal\os2loop_cura_login\Settings\IdP;
use Drupal\os2loop_cura_login\Trait\ControllerAwareTrait;

/**
 * IdP helper.
 */
final class IdPHelper {
  use ControllerAwareTrait;

  /**
   * The settings.
   */
  private IdP $settings;

  public function __construct(
    Settings $settings,
  ) {
    $this->settings = $settings->getIdpSettings();
  }

  /**
   * Get user info from userinfo endpoint.
   */
  public function fetchUserinfo(string $username): array {
    // @todo Call some API here …
    // $query = [
    // $this->settings->getUsernameClaim() => $username,
    // ];
    // $result = fetch($query)
    // Mock up some user data matching claims from OIDC login.
    $result = [
      // Drupal user fields.
      'upn' => $username,
      'name' => $username,
      'email' => filter_var($username, FILTER_VALIDATE_EMAIL) ? $username : $username . '@cura.example.com',
      'samaccountname' => $username,
      'given_name' => 'Cura',
      'family_name' => 'User',
      'groups' => [
        'GG-Rolle-B2C-Loop-AuthenticatedUser-Prod',
        // 'GG-Rolle-B2C-Loop-Administrator-Prod',
        // 'GG-Rolle-B2C-Loop-Administrator-Test',
        // 'GG-Rolle-B2C-Loop-DocumentAuthor-Prod',
        // 'GG-Rolle-B2C-Loop-DocumentAuthor-Test',
        // 'GG-Rolle-B2C-Loop-DocumentCollectionEditor-Prod',
        // 'GG-Rolle-B2C-Loop-DocumentCollectionEditor-Test',
        // 'GG-Rolle-B2C-Loop-DocumentationCoordinator-Prod',
        // 'GG-Rolle-B2C-Loop-DocumentationCoordinator-Test',
        // 'GG-Rolle-B2C-Loop-ExternalSourcesEditor-Prod',
        // 'GG-Rolle-B2C-Loop-ExternalSourcesEditor-Test',
        // 'GG-Rolle-B2C-Loop-Manager-Prod',
        // 'GG-Rolle-B2C-Loop-Manager-Test',
        // 'GG-Rolle-B2C-Loop-PostAuthor-Prod',
        // 'GG-Rolle-B2C-Loop-PostAuthor-Test',
        // 'GG-Rolle-B2C-Loop-ReadOnly-Prod',
        // 'GG-Rolle-B2C-Loop-ReadOnly-Test',
        // 'GG-Rolle-B2C-Loop-UserAdministrator-Prod',
        // 'GG-Rolle-B2C-Loop-UserAdministrator-Test',
      ],
    ];

    return $result;
  }

}
