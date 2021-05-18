<?php

namespace Drupal\os2loop_mail_notifications\Helper;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\user\Entity\User;

/**
 * OS2Loop Mail notifications mail helper.
 */
class MailHelper {
  private const NOTIFICATION_MAIL = 'os2loop_mail_notifications_notification';

  /**
   * The site config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $siteConfig;

  /**
   * The module config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  private $config;

  /**
   * The renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  private $renderer;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  private $mailer;

  /**
   * Helper constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory, RendererInterface $renderer, MailManagerInterface $mailer) {
    $this->siteConfig = $configFactory->get('system.site');
    $this->config = $configFactory->get(Helper::MODULE . '.settings');
    $this->renderer = $renderer;
    $this->mailer = $mailer;
  }

  /**
   * Implements hook_mail().
   */
  public function mail($key, &$message, $params) {
    $siteName = $this->siteConfig->get('name');
    $siteMail = $this->siteConfig->get('mail');

    switch ($key) {
      case static::NOTIFICATION_MAIL:
        $message['headers']['Reply-To'] = $siteMail;
        $message['headers']['Content-Type'] = 'text/html';
        $message['headers']['From'] = sprintf('%s <%s>', $siteName, $siteMail);
        $message['subject'] = $params['subject'];
        $message['body'][] = $params['body'];
        break;
    }
  }

  /**
   * Send notification.
   */
  public function sendNotification(User $user, array $groupedMessages) {
    $lang_code = $user->getPreferredLangcode();

    $subjectTemplate = 'Notifications from {{ site.name }}';
    $bodyTemplate = <<<'TWIG'
<p>Hi {{ user.display_name }} ({{ user.first_name }} {{ user.last_name }}),</p>

<p>
Here are the notifications from {{ site.name }}:
</p>
{% for type, messages in grouped_messages %}
    <h1>{{ type }}</h1>

    {% for message in messages %}
    <p>{{ message.text|first|raw }}</p>
    {% endfor %}
{% endfor %}

<p>Best regards,<br/>
{{ site.name}}</p>
TWIG;

    $elements = [
      [
        '#type' => 'inline_template',
        '#template' => $subjectTemplate,
        '#context' => [
          'user' => $this->getUserValues($user),
          'site' => $this->siteConfig->get(),
        ],
      ],
    ];
    $params['subject'] = $this->renderer->renderPlain($elements);
    $elements = [
      [
        '#type' => 'inline_template',
        '#template' => $bodyTemplate,
        '#context' => [
          'user' => $this->getUserValues($user),
          'site' => $this->siteConfig->get(),
          'grouped_messages' => $groupedMessages,
        ],
      ],
    ];
    $params['body'] = $this->renderer->renderPlain($elements);

    $result = $this->mailer->mail(Helper::MODULE, static::NOTIFICATION_MAIL, $user->getEmail(), $lang_code, $params, NULL, TRUE);

    return TRUE === $result['result'];
  }

  /**
   * Get user values.
   */
  private function getUserValues(User $user) {
    return [
      'first_name' => $user->get('os2loop_user_given_name')->getValue()[0]['value'] ?? NULL,
      'last_name' => $user->get('os2loop_user_family_name')->getValue()[0]['value'] ?? NULL,
      'display_name' => $user->getDisplayName(),
    ];
  }

}
