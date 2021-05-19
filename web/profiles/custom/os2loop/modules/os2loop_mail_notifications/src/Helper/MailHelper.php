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
        // $message['headers']['Content-Type'] = 'text/html';
        $message['headers']['From'] = sprintf('%s <%s>', $siteName, $siteMail);
        $message['subject'] = $params['subject'];
        $message['body'][] = $params['body'];
        break;
    }
  }

  /**
   * Implements hook_theme().
   */
  public function theme($existing, $type, $theme, $path) {
    return [
      'os2loop_mail_notifications_notification' => [
        'variables' => [
          'site' => NULL,
          'user' => NULL,
          'grouped_messages' => NULL,
        ],
      ],
    ];
  }

  /**
   * Send notification.
   *
   * @return bool
   *   True if mail is sent.
   */
  public function sendNotification(User $user, array $groupedMessages) {
    $lang_code = $user->getPreferredLangcode();

    $elements = [
      [
        '#theme' => 'os2loop_mail_notifications_notification',
        '#user' => $user,
        '#site' => $this->siteConfig->get(),
        '#grouped_messages' => $groupedMessages,
      ],
    ];
    $content = (string) $this->renderer->renderPlain($elements);

    $parts = $this->getParts($content);
    $params['subject'] = $parts['subject'];
    $params['body'] = $parts['text/plain'];

    $result = $this->mailer->mail(Helper::MODULE, static::NOTIFICATION_MAIL, $user->getEmail(), $lang_code, $params, NULL, TRUE);

    return TRUE === $result['result'];
  }

  /**
   * Get parts from rendered content.
   *
   * Blocks are separated by lines starting with 4 dashes followed by
   * the mime type and some more dashes.
   *
   * The first block is the subject.
   *
   * @param string $content
   *   The content.
   *
   * @return array
   *   The parts.
   */
  private function getParts(string $content): array {
    $blocks = array_map('trim',
      preg_split('/-{4}([^-]+)-+/', $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE)
    );
    array_unshift($blocks, 'subject');

    $parts = [];
    for ($i = 0, $iMax = count($blocks); $i < $iMax; $i += 2) {
      $parts[$blocks[$i]] = $blocks[$i + 1];
    }

    $parts['subject'] = trim(strip_tags($parts['subject']));

    return $parts;
  }

}
