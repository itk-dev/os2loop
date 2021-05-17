<?php

namespace Drupal\os2loop_share_with_a_friend\Helper;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Utility\Token;
use Drupal\os2loop_share_with_a_friend\Form\SettingsForm;
use Drupal\os2loop_settings\Settings;

/**
 * MailHelper for creating mail templates.
 */
class MailHelper {
  use StringTranslationTrait;
  /**
   * The token.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * The config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * Constructor.
   */
  public function __construct(Token $token, Settings $settings) {
    $this->token = $token;
    $this->config = $settings->getConfig(SettingsForm::SETTINGS_NAME);
  }

  /**
   * Implements hook_mail().
   */
  public function mail($key, &$message, $params) {
    switch ($key) {
      case 'share_with_a_friend':
        $node = $params['node'];
        // @todo move this to settings
        $body_template = $this->config->get('template_body');
        $subject_template = $this->config->get('template_subject');
        $data['node'] = $node;
        $data['message'] = $params['message'];
        $body = $this->renderTemplate($body_template, $data);
        $subject = $this->renderTemplate($subject_template);
        $message['subject'] = $subject;
        $message['body'][] = $body;
        break;
    }
  }

  /**
   * Renders content of a mail.
   */
  public function renderTemplate($template, array $data = NULL) {
    if (isset($data)) {
      return $this->token->replace($template, [
        'node' => $data['node'],
        'message' => $data['message'],
      ], []);
    }
    else {
      return $this->token->replace($template, [], []);
    }
  }

  /**
   * Implements hook_tokens().
   */
  public function tokens($type, $tokens, array $data) {
    $replacements = [];
    if ($type == 'os2loop_share_with_a_friend' && !empty($data['message'])) {
      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'message':
            $replacements[$original] = $data['message'];
            break;
        }
      }
    }
    return $replacements;
  }

  /**
   * Implements hook_token_info().
   */
  public function tokenInfo() {
    $types['os2loop_share_with_a_friend'] = [
      'name' => $this->t('Message type'),
    ];
    $tokens['message'] = [
      'name' => $this->t('Message'),
    ];

    return [
      'types' => $types,
      'tokens' => [
        'os2loop_share_with_a_friend' => $tokens,
      ],
    ];

  }

}