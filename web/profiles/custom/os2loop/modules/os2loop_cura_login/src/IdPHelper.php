<?php

namespace Drupal\os2loop_cura_login;

use Drupal\os2loop_cura_login\Settings\IdP;

/**
 * IdP helper.
 */
final class IdPHelper {
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
    $query = [
      $this->settings->getUsernameClaim() => $username,
    ];
    // $result = fetch($query)
    return [
      // Drupal user fields.
      'name' => $username,
      'mail' => $username . '@cura.example.com',

      // OS2Lloop fields
      // 'os2loop_user_address' => '',
      // 'os2loop_user_areas_of_expertise' => '',
      // 'os2loop_user_biography' => '',
      // 'os2loop_user_city' => '',
      // 'os2loop_user_external_list' => '',.
      'os2loop_user_family_name' => 'Cura',
      'os2loop_user_given_name' => 'User',
      // 'os2loop_user_image' => '',
      // 'os2loop_user_internal_list' => '',
      // 'os2loop_user_job_title' => '',
      // 'os2loop_user_phone_number' => '',
      // 'os2loop_user_place' => '',
      // 'os2loop_user_postal_code' => '',
      // 'os2loop_user_professions' => '',
    ];
  }

}
