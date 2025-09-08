<?php

namespace Drupal\os2loop_cura_login;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\os2loop_cura_login\Settings\Cura;
use Drupal\os2loop_cura_login\Settings\IdP;

/**
 * Settings for OS2Loop Cura login.
 */
final class Settings {
  const string CONFIG_NAME = 'os2loop_cura_login.settings';

  const string NAME = 'general';
  const string SETTING_LOG_LEVEL = 'log_level';

  /**
   * The config.
   */
  private readonly ImmutableConfig $config;

  /**
   * The Cura settings.
   */
  private readonly Cura $curaSettings;

  /**
   * The IdP settings.
   */
  private readonly IdP $idpSettings;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly ConfigFactoryInterface $configFactory,
  ) {
    $this->config = $configFactory->get(self::CONFIG_NAME);
  }

  /**
   * Get Cura settings.
   */
  public function getCuraSettings() {
    if (!isset($this->curaSettings)) {
      $this->curaSettings = new Cura($this->config->get(Cura::NAME) ?? []);
    }

    return $this->curaSettings;
  }

  /**
   * Get IdP settings.
   */
  public function getIdpSettings() {
    if (!isset($this->idpSettings)) {
      $this->idpSettings = new IdP($this->config->get(IdP::NAME) ?? []);
    }

    return $this->idpSettings;
  }

  /**
   * Get log level.
   */
  public function getLogLevel() {
    return (int) ($this->config->get(self::NAME)[self::SETTING_LOG_LEVEL] ?? RfcLogLevel::ERROR);
  }

  /**
   * Save settings.
   */
  public function saveSettings(FormStateInterface $formState): array {
    $values = array_filter(
      $formState->getValues(),
      static fn(string $key) => in_array($key, [self::NAME, Cura::NAME, IdP::NAME], TRUE),
      ARRAY_FILTER_USE_KEY
    );

    // @todo validate values
    $config = $this->configFactory->getEditable(self::CONFIG_NAME);
    foreach ($values as $key => $value) {
      $config->set($key, $value);
    }
    $config->save();

    return $config->get();
  }

}
