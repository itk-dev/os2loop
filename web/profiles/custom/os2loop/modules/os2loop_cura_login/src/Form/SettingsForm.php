<?php

declare(strict_types=1);

namespace Drupal\os2loop_cura_login\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\os2loop_cura_login\Settings;
use Drupal\os2loop_cura_login\Settings\Cura;
use Drupal\os2loop_cura_login\Settings\IdP;

/**
 * Configure OS2Loop Cura login settings for this site.
 */
final class SettingsForm extends ConfigFormBase {
  use AutowireTrait;

  private const string GENERATE_NEW_SECRET = 'generate_new_secret';

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
    $form[Cura::NAME] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => t('Cura'),
    ] + $this->buildCuraForm($form_state);

    $form[IdP::NAME] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => t('IdP'),
    ] + $this->buildIdPForm($form_state);

    $form[Settings::NAME] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => t('General settings'),

      Settings::SETTING_LOG_LEVEL => [
        '#type' => 'select',
        '#options' => RfcLogLevel::getLevels(),
        '#title' => $this->t('Log level'),
        '#default_value' => $this->settings->getLogLevel(),
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Build Cura form.
   */
  public function buildCuraForm(FormStateInterface $form_state): array {
    $settings = $this->settings->getCuraSettings();
    $form[Cura::SETTING_SIGNING_ALGORITHM] = [
      '#required' => TRUE,
      '#type' => 'select',
      '#options' => Cura::SIGNING_ALGORITHMS,
      '#title' => $this->t('Signing algorithm'),
      '#default_value' => $settings->getSigningAlgorithm(),
    ];

    $hasSigningSecret = !empty($settings->getSigningSecret());

    $form[Cura::SETTING_SIGNING_SECRET] = [
      '#type' => 'textfield',
      '#size' => 128,
      '#required' => $hasSigningSecret,
      '#title' => $this->t('Signing secret'),
      '#default_value' => $settings->getSigningSecret(),
      '#description' => !$hasSigningSecret
        ? $this->t('Save the configuration to generate a random secret.')
        : '',
    ];

    $form[self::GENERATE_NEW_SECRET] = [
      '#title' => $this->t('Generate new secret'),
      '#type' => 'checkbox',
      '#default_value' => !$hasSigningSecret,
      '#description' => $this->t('Check this to generate a new random secret'),
    ];

    $form[Cura::SETTING_PAYLOAD_NAME] = [
      '#type' => 'textfield',
      '#title' => $this->t('Payload name'),
      '#default_value' => $settings->getPayloadName(),
      '#description' => $this->t('Name of parameter used for payload'),
    ];

    $form[Cura::SETTING_JWT_LEEWAY] = [
      '#type' => 'textfield',
      '#title' => $this->t('JWT leeway'),
      '#default_value' => $settings->getJwtLeeway(),
    ];

    $authenticationStartUrlPost = Url::fromRoute('os2loop_cura_login.start')->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    $authenticationStartUrlGet = rtrim($authenticationStartUrlPost, '/') . '/';

    $form['info'] = [
      '#type' => 'details',
      '#title' => $this->t('Cura link settings'),
      '#description' => $this->t('Use these settings to set up a link in Cura (cf. the documentation we cannot refer to)'),
      'info' => [
        '#markup' => $this->t('
<dl>
<dt>signingAlgorithm</dt>
<dd>:signing_algorithm</dd>

<dt>signingSecret (Base64 encoded)</dt>
<dd><code>:signing_secret</code></dd>

<dt>linkURL (postToGetLinkURL = false)</dt>
<dd><code>:link_url_get</code></dd>

<dt>linkURL (postToGetLinkURL = true)</dt>
<dd><code>:link_url_post</code></dd>
</dl>
',
          [
            ':signing_algorithm' => $settings->getSigningAlgorithm(),
            ':signing_secret' => base64_encode($settings->getSigningSecret()),
            ':link_url_get' => $authenticationStartUrlGet,
            ':link_url_post' => $authenticationStartUrlPost,
          ]
        ),
      ],
    ];

    return $form;
  }

  /**
   * Build IdP form.
   */
  public function buildIdpForm(FormStateInterface $form_state): array {
    $settings = $this->settings->getIdpSettings();

    $form['todo'] = [
      '#theme' => 'status_messages',
      '#message_list' => [
        MessengerInterface::TYPE_WARNING => [$this->t('This section is incomplete.')],
      ],
    ];

    $form[IdP::USERNAME_CLAIM] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Username claim'),
      '#default_value' => $settings->getUsernameClaim(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($form_state->getValue([Cura::NAME, self::GENERATE_NEW_SECRET])) {
      $form_state->setValue([Cura::NAME, Cura::SETTING_SIGNING_SECRET], (new Random())->name(64));
    }
    $this->settings->saveSettings($form_state);

    parent::submitForm($form, $form_state);
  }

}
