<?php

declare(strict_types=1);

namespace Drupal\os2loop_cura_login\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\os2loop_cura_login\Settings;

/**
 * Configure OS2Loop Cura login settings for this site.
 */
final class SettingsForm extends ConfigFormBase {
  use AutowireTrait;

  public function __construct(
    ConfigFactoryInterface $config_factory,
    private readonly Settings $settings,
  ) {
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'os2loop_cura_login_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [Settings::CONFIG_NAME];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $settings = \Drupal::service(Settings::class);
    $form['signing_algorithm'] = [
      '#required' => TRUE,
      '#type' => 'select',
      '#options' => Settings::SIGNING_ALGORITHMS,
      '#title' => $this->t('Signing algorithm'),
      '#default_value' => $this->settings->getSigningAlgorithm(),
    ];

    $hasSigningSecret = !empty($this->settings->getSigningSecret());

    $form['signing_secret'] = [
      '#type' => 'textfield',
      '#size' => 128,
      '#required' => $hasSigningSecret,
      '#title' => $this->t('Signing secret'),
      '#default_value' => $this->settings->getSigningSecret(),
      '#description' => !$hasSigningSecret
        ? $this->t('Save the configuration to generate a random secret.')
        : '',
    ];

    $form['generate_new_secret'] = [
      '#title' => $this->t('Generate new secret'),
      '#type' => 'checkbox',
      '#default_value' => !$hasSigningSecret,
      '#description' => $this->t('Check this to generate a new random secret'),
    ];

    $form['payload_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payload name'),
      '#default_value' => $this->settings->getPayloadName(),
      '#description' => $this->t('Name of parameter used for payload'),
    ];

    $form['jwt_leeway'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JWT leeway'),
      '#default_value' => $this->settings->getJwtLeeway(),
    ];

    $form['log_level'] = [
      '#type' => 'select',
      '#options' => RfcLogLevel::getLevels(),
      '#title' => $this->t('Log level'),
      '#default_value' => $this->settings->getLogLevel(),
    ];

    $authenticationStartUrl = Url::fromRoute('os2loop_cura_login.start')->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    $form['info'] = [
      '#theme' => 'item_list',
      '#items' => [
        '#markup' => $this->t('Use <a href=":url">:url</a> as <code>linkURL</code> for <code>postToGetLinkURL ≡ true</code>.', [':url' => $authenticationStartUrl]),
      ],
    ];

    $authenticationStartUrl = Url::fromRoute('os2loop_cura_login.start', [])->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    $authenticationStartUrl = rtrim($authenticationStartUrl, '/') . '/';
    $form['info']['#items'][] = [
      '#markup' => $this->t('Use <a href=":url">:url</a> as <code>linkURL</code> for <core><code>postToGetLinkURL ≡ false</code>.', [':url' => $authenticationStartUrl]),
    ];

    if ($name = $this->settings->getPayloadName()) {
      $authenticationStartUrl = Url::fromRoute('os2loop_cura_login.start', [$name => '…'])->setAbsolute()->toString(TRUE)->getGeneratedUrl();
      $authenticationStartUrl = str_replace(urlencode('…'), '', $authenticationStartUrl);
      $form['info']['#items'][] = [
        '#markup' => $this->t('Use <a href=":url">:url</a> as <code>linkURL</code> for <core><code>postToGetLinkURL ≡ false</code>.', [':url' => $authenticationStartUrl]),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $secret = $form_state->getValue('signing_secret');
    if ($form_state->getValue('generate_new_secret')) {
      $form_state->setValue('signing_secret', base64_encode((new Random())->string(64)));
    }
    /** @var \Drupal\os2loop_cura_login\Settings $settings */
    $settings = \Drupal::service(Settings::class);
    $settings->saveSettings($form_state);

    parent::submitForm($form, $form_state);
  }

}
