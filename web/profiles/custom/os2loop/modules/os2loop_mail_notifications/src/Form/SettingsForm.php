<?php

namespace Drupal\os2loop_mail_notifications\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2loop_settings\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure share with a friend admin settings for this site.
 */
class SettingsForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * Config setting name.
   *
   * @var string
   */
  public const SETTINGS_NAME = 'os2loop_mail_notifications.settings';

  /**
   * The settings.
   *
   * @var \Drupal\os2loop_settings\Settings
   */
  private $settings;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Settings $settings) {
    parent::__construct($config_factory);
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get(Settings::class)
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2loop_mail_notifications_settings';
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

    $form['template_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject template for mail notifications'),
      '#required' => TRUE,
      '#default_value' => $config->get('template_subject'),
    ];

    $form['template_subject_tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'os2loop_mail_notifications'],
    ];

    $form['template_body'] = [
      '#type' => 'textarea',
      '#rows' => 20,
      '#title' => $this->t('Body template for mail notifications'),
      '#description' => $this->t('Use [os2loop_mail_notifications:notifications] to insert the actual list of notifications.'),
      '#required' => TRUE,
      '#default_value' => $config->get('template_body'),
      '#token_insert' => TRUE,
    ];

    $form['template_body_tokens'] = [
      '#theme' => 'token_tree_link',
      '#token_types' => ['user', 'node', 'os2loop_mail_notifications'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $template = $form_state->getValue('template_body');
    if (FALSE === strpos($template, '[os2loop_mail_notifications:notifications]')) {
      $form_state->setErrorByName('template_body', $this->t('Please insert [os2loop_mail_notifications:notifications] in body template.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS_NAME)
      ->set('template_subject', $form_state->getValue('template_subject'))
      ->set('template_body', $form_state->getValue('template_body'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
