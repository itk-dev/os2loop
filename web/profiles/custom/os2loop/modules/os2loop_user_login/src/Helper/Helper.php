<?php

namespace Drupal\os2loop_user_login\Helper;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\os2loop_user_login\Form\SettingsForm;
use Drupal\os2loop_settings\Settings;

/**
 * Helper for os2loop_user_login.
 */
class Helper {
  use StringTranslationTrait;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Constructor.
   */
  public function __construct(Settings $settings) {
    $this->config = $settings->getConfig(SettingsForm::SETTINGS_NAME);
  }

  /**
   * Implements hook_form_alter().
   */
  public function alterForm(&$form, FormStateInterface $form_state, $form_id) {
    if ('openid_connect_login_form' === $form_id) {
      if (!$this->config->get('show_oidc_login')) {
        $form['#access'] = FALSE;
      }
    }
    elseif ('user_login_form' === $form_id) {
      if (!$this->config->get('show_drupal_login')) {
        $form['#attached']['library'][] = 'os2loop_user_login/user-login-form';

        // Wrap default Drupal login form in an element with a known id
        // (drupal-login) so we can visually hide it.
        foreach ($form as $key => $value) {
          if (0 !== strpos($key, '#')) {
            $form['drupal_login'][$key] = array_merge($value);
            unset($form[$key]);
          }
        }
        $form['drupal_login'] += [
          '#type' => 'fieldset',
          '#title' => $this->t('Drupal login'),
          '#weight' => 100,
          '#attributes' => ['id' => 'drupal-login'],
        ];
      }

      if ($this->config->get('show_saml_login')) {
        $form['saml_login'] = [
          '#weight' => -100,
          '#type' => 'link',
          '#title' => $this->t('Log in with SAML'),
          '#url' => Url::fromRoute('samlauth.saml_controller_login'),
          '#attributes' => [
            'class' => ['os2loop-user-login-button'],
          ],
        ];
      }
    }
  }

}
