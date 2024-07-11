<?php

namespace Drupal\os2loop_subscriptions\Form;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\Entity\NodeType;
use Drupal\os2loop_settings\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings for os2loop_subscriptions.
 */
final class SettingsForm extends ConfigFormBase {
  use StringTranslationTrait;

  /**
   * Config setting name.
   *
   * @var string
   */
  public const SETTINGS_NAME = 'os2loop_subscriptions.settings';

  /**
   * The settings.
   *
   * @var \Drupal\os2loop_settings\Settings
   */
  private $settings;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory, Settings $settings, private EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($config_factory);
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get(Settings::class),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2loop_share_with_a_friend_settings';
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

    $nodeTypes = $this->settings->getContentTypes();
    $options = array_map(static function (NodeType $nodeType) {
      return $nodeType->label();
    }, $nodeTypes);

    $subscriptionTaxonomyRequiredOptions = $this->getSubscriptionTaxonomyRequiredOptions();

    $form['subscribe_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable subscribe on content types'),
      '#description' => $this->t('Enable subscribe on these content types'),
      '#options' => $options,
      '#default_value' => $config->get('subscribe_node_types') ?: [],
    ];

    $form['favourite_node_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable favourite on content types'),
      '#description' => $this->t('Enable favourite on these content types'),
      '#options' => $options,
      '#default_value' => $config->get('favourite_node_types') ?: [],
    ];

    $form['subscription_required_taxonomy'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enable required subscription for taxonomies'),
      '#description' => $this->t('Ensures that the user is always subscribed to at least one term of this taxonomy.'),
      '#options' => $subscriptionTaxonomyRequiredOptions,
      '#default_value' => $config->get('subscription_required_taxonomy') ?: [],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS_NAME)
      ->set('subscribe_node_types', $form_state->getValue('subscribe_node_types'))
      ->set('favourite_node_types', $form_state->getValue('favourite_node_types'))
      ->set('subscription_required_taxonomy', $form_state->getValue('subscription_required_taxonomy'))
      ->save();

    drupal_flush_all_caches();

    parent::submitForm($form, $form_state);
  }

  /**
   * Get taxonomy options from flagged taxonomies.
   *
   * @return array
   *   Options for the taxonomy required list.
   */
  private function getSubscriptionTaxonomyRequiredOptions(): array {
    $options = [];
    $enabledTaxonomies = $this->settings->getConfig('flag.flag.os2loop_subscription_term')->get('bundles');
    foreach ($enabledTaxonomies as $taxonomy) {
      try {
        $vocabulary = $this->entityTypeManager->getStorage('taxonomy_vocabulary')
          ->load($taxonomy);
        $options[$taxonomy] = $vocabulary->label();
      }
      catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      }
    }

    return $options;
  }

}
