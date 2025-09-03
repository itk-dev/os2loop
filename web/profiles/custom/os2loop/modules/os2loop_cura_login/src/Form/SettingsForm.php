<?php

declare(strict_types=1);

namespace Drupal\os2loop_cura_login\Form;

use Drupal\Component\Utility\Random;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;

/**
 * Configure OS2Loop Cura login settings for this site.
 */
final class SettingsForm extends ConfigFormBase {

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
    return ['os2loop_cura_login.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $config = $this->config('os2loop_cura_login.settings');
    $form['signing_algorithm'] = [
      '#required' => TRUE,
      '#type' => 'select',
      '#options' => [
        'HS256' => 'HS256',
        'HS384' => 'HS384',
        'HS512' => 'HS512',
      ],
      '#title' => $this->t('Signing algorithm'),
      '#default_value' => $config->get('signing_algorithm'),
    ];

    $hasSigningSecret = !empty($config->get('signing_secret'));

    $form['signing_secret'] = [
      '#type' => 'textfield',
      '#size' => 128,
      '#required' => $hasSigningSecret,
      '#title' => $this->t('Signing secret'),
      '#default_value' => $config->get('signing_secret'),
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

    $form['token_param_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token param name'),
      '#default_value' => $config->get('token_param_name'),
      '#description' => $this->t('Query string param name used for JWT token on GET request'),
    ];

    $form['log_level'] = [
      '#type' => 'select',
      '#options' => RfcLogLevel::getLevels(),
      '#title' => $this->t('Log level'),
      '#default_value' => $config->get('log_level') ?? RfcLogLevel::ERROR,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // @todo Validate the form here.
    // Example:
    // @code
    //   if ($form_state->getValue('example') === 'wrong') {
    //     $form_state->setErrorByName(
    //       'message',
    //       $this->t('The value is not correct.'),
    //     );
    //   }
    // @endcode
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $secret = $form_state->getValue('signing_secret');
    if ($form_state->getValue('generate_new_secret')) {
      $secret = base64_encode((new Random())->string(64));
    }
    $this->config('os2loop_cura_login.settings')
      ->set('signing_algorithm', $form_state->getValue('signing_algorithm'))
      ->set('signing_secret', $secret)
      ->set('token_param_name', $form_state->getValue('token_param_name'))
      ->set('log_level', $form_state->getValue('log_level'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
