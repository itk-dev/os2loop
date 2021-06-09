<?php

namespace Drupal\os2loop_analytics\Helper;

use Drupal\os2loop_analytics\Form\SettingsForm;
use Drupal\os2loop_settings\Settings;
use Drupal\Core\Render\Markup;

/**
 * Helper for os2loop_analytics.
 */
class AnalyticsHelper {
  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Constructor.
   *
   * @param \Drupal\os2loop_settings\Settings $settings
   *   Settings.
   */
  public function __construct(Settings $settings) {
    $this->config = $settings->getConfig(SettingsForm::SETTINGS_NAME);
  }

  /**
   * Set analytics tools script in head.
   *
   * @param array $attachments
   *   The attachments to add.
   */
  public function setAnalytics(array &$attachments) {
    $scripts = $this->getAnalyticsConfig();
    foreach ($scripts as $name => $script) {
      $cleanScript = $this->cleanup($script);
      $attachments['#attached']['html_head'][] = [
        [
          '#tag' => 'script',
          '#value' => Markup::create($cleanScript),
          '#weight' => 1,
        ],
        $name . '_script',
      ];
    }
  }

  /**
   * Get config related to os2loop analytics.
   *
   * @return array
   *   A list of analytics scripts.
   */
  private function getAnalyticsConfig(): array {
    $scripts = [];
    $scripts += $this->config->get('matomo_tracking_code') ? ['matomo' => $this->config->get('matomo_tracking_code')] : [];
    $scripts += $this->config->get('google_analytics_tracking_code') ? ['google_analytics' => $this->config->get('google_analytics_tracking_code')] : [];
    $scripts += $this->config->get('siteimprove_tracking_code') ? ['siteimprove' => $this->config->get('siteimprove_tracking_code')] : [];
    return $scripts;
  }

  /**
   * Prepares a script for attachment.
   *
   * @param string $script
   *   A script to cleanup.
   *
   * @return string
   *   The cleansed script.
   */
  private function cleanup(string $script): string {
    $script = $this->removeTags($script);
    return $script;
  }

  /**
   * Removes tags from script (i.e <script>)
   *
   * @param string $script
   *   The script to remove tags from.
   *
   * @return string
   *   The resulting output.
   */
  private function removeTags(string $script): string {
    return strip_tags($script);
  }

}
