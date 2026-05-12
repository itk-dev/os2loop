<?php

namespace Drupal\os2loop_user_login\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\os2loop_settings\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure os2loop_user_login.
 */
final class SettingsForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * Config setting name.
   *
   * @var string
   */
  public const SETTINGS_NAME = 'os2loop_user_login.settings';

  /**
   * The settings.
   *
   * @var \Drupal\os2loop_settings\Settings
   */
  private $settings;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, TypedConfigManagerInterface $typedConfigManager, Settings $settings) {
    parent::__construct($config_factory, $typedConfigManager);
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('config.typed'),
      $container->get(Settings::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2loop_user_login_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS_NAME,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->settings->getConfig(static::SETTINGS_NAME);

    $form['show_drupal_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show Drupal login'),
      '#default_value' => FALSE,
      '#disabled' => TRUE,
      '#description' => $this->t(
        'This option has been removed. This is now controlled by the "@config_title" setting in the <a href=":config_url">OpenID Connect settings</a>.',
        [
          '@config_title' => $this->t('OpenID buttons display in user login form'),
          ':config_url' => Url::fromRoute('openid_connect.admin_settings')->toString(),
        ],
      ),
    ];

    $form['show_oidc_login'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show OpenID Connect login'),
      '#default_value' => FALSE,
      '#disabled' => TRUE,
      '#description' => $this->t(
        'This option has been removed. This is now controlled by the "@config_title" setting in the <a href=":config_url">OpenID Connect settings</a>.',
        [
          '@config_title' => $this->t('OpenID buttons display in user login form'),
          ':config_url' => Url::fromRoute('openid_connect.admin_settings')->toString(),
        ],
      ),
    ];

    $form['default_login_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Default login method'),
      '#default_value' => FALSE,
      '#disabled' => TRUE,
      '#description' => $this->t(
        'This option has been removed. This is now controlled by the "@config_title" setting in the <a href=":config_url">OpenID Connect settings</a>.',
        [
          '@config_title' => $this->t('Autostart login process'),
          ':config_url' => Url::fromRoute('openid_connect.admin_settings')->toString(),
        ],
      ),
    ];

    $form['hide_logout_menu_item'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide logout menu item'),
      '#default_value' => $config->get('hide_logout_menu_item'),
      '#description' => $this->t('If checked, the "Log out" item in the user menu will be hidden.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS_NAME)
      ->set('hide_logout_menu_item', $form_state->getValue('hide_logout_menu_item'))
      ->save();

    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

}
