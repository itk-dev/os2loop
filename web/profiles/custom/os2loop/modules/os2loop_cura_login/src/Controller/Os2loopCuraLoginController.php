<?php

declare(strict_types=1);

namespace Drupal\os2loop_cura_login\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\user\UserStorageInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Returns responses for os2loop_cura_login routes.
 */
final class Os2loopCuraLoginController extends ControllerBase {
  use LoggerTrait;

  private const JWT_KEY = 'os2loop_cura_login';

  /**
   * The user storage.
   */
  private readonly UserStorageInterface $userStorage;

  /**
   * The module config.
   */
  private readonly ImmutableConfig $config;

  /**
   * Constructor.
   */
  public function __construct(
    private readonly TimeInterface $time,
    #[Autowire(service: 'logger.channel.os2loop_cura_login')]
    private readonly LoggerInterface $logger,
  ) {
    $this->userStorage = $this->entityTypeManager()->getStorage('user');
    $this->config = $this->config('os2loop_cura_login.settings');
  }

  /**
   * Start user authentication.
   */
  public function start(Request $request): Response {
    try {
            $content = NULL;
      try {
        $content = (string) $request->getContent();
      } catch (\Exception) {}
      $this->debug('@debug', [
        '@debug' => json_encode([
          'method' => $request->getMethod(),
          'query' => $request->query->all(),
          'content' => $content,
        ]),
      ]);

      $jwt = Request::METHOD_POST === $request->getMethod()
        ? $request->getContent()
        : $request->query->getString($this->config->get('token_param_name') ?? 'token');

      $this->debug('@debug', [
        '@debug' => json_encode([
          'jwt' => $jwt,
        ]),
      ]);

      if (empty($jwt)) {
        throw new BadRequestHttpException('Missing or empty JWT');
      }

      $payload = (array) JWT::decode($jwt, new Key($this->config->get('signing_secret'), $this->config->get('signing_algorithm')));

      $this->debug('@debug', [
        '@debug' => json_encode([
          'payload' => $payload,
        ]),
      ]);

      $username = $payload['username'] ?? NULL;
      if (empty($username)) {
        throw new BadRequestHttpException('Missing username');
      }

      $user = $this->loadUser($username);

      $this->debug('@debug', [
        '@debug' => json_encode([
          'user' => $user,
        ]),
      ]);

      if (empty($user)) {
        // Don't disclose whether or not the user exists.
        throw new BadRequestHttpException();
      }

      // Check that we can get userinfo.
      $userinfo = $this->getUserinfo($user);

      $this->debug('@debug', [
        '@debug' => json_encode([
          'userinfo' => $userinfo,
        ]),
      ]);

      if (empty($userinfo)) {
        throw new BadRequestHttpException();
      }

      // https://github.com/firebase/php-jwt?tab=readme-ov-file#example
      $payload = [
        // Issued at.
        'iat' => $this->time->getRequestTime(),
        // Expire af 60 seconds.
        'exp' => $this->time->getRequestTime() + 60,
        'username' => $username,
      ];
      $jwt = JWT::encode($payload, self::JWT_KEY, 'HS256');

      $url = Url::fromRoute('os2loop_cura_login.authenticate', [
        'username' => $username,
        'jwt' => $jwt,
      ])->setAbsolute()->toString(TRUE)->getGeneratedUrl();

      return new JsonResponse([
        'authenticate_url' => $url,
        'jwt' => $jwt,
      ]);
    }
    catch (\Exception $exception) {
      $this->error('start: @message', ['@message' => $exception->getMessage(), $exception]);
      throw new BadRequestException($exception->getMessage());
    }
  }

  /**
   * Authenticate user.
   */
  public function authenticate(Request $request): Response {
    try {
      $username = $request->get('username');
      $jwt = $request->get('jwt');
      if (empty($username) || empty($jwt)) {
        throw new BadRequestHttpException();
      }

      $payload = (array) JWT::decode($jwt, new Key(self::JWT_KEY, 'HS256'));
      $username = $payload['username'] ?? NULL;
      if (empty($username)) {
        throw new BadRequestHttpException();
      }

      $user = $this->loadUser($username);
      if (empty($user)) {
        // Don't disclose whether or not the user exists.
        throw new BadRequestHttpException();
      }

      $this->updateUser($user);

      user_login_finalize($user);

      $url = Url::fromRoute('<front>')->setAbsolute()->toString(TRUE)->getGeneratedUrl();
      $this->messenger()->addStatus($this->t('Welcome @user.', ['@user' => $user->getDisplayName()]));

      return new TrustedRedirectResponse($url);
    }
    catch (\Exception $exception) {
      $this->error('start: @message', ['@message' => $exception->getMessage(), $exception]);
      throw new BadRequestException($exception->getMessage());
    }
  }

  /**
   * Load user by username.
   *
   * @param string $username
   *   The username.
   *
   * @return \Drupal\user\Entity\User|null
   *   The user if any.
   */
  private function loadUser(string $username): ?User {
    $users = $this->userStorage->loadByProperties(['name' => $username]);

    return reset($users) ?: NULL;
  }

  /**
   * Update user with info from IdP.
   */
  private function updateUser(User $user): User {
    // $userinfo = $this->getUserinfo($user);
    // @todo Update user.
    return $user;
  }

  /**
   * Get user info from userinfo endpoint.
   */
  private function getUserinfo(User $user): array {
    return [
      'name' => $user->getDisplayName(),
    ];
  }

  public function log($level, \Stringable|string $message, array $context = []): void
  {
    // Lifted from LoggerChannel
    $levels = [
      LogLevel::EMERGENCY => RfcLogLevel::EMERGENCY,
      LogLevel::ALERT => RfcLogLevel::ALERT,
      LogLevel::CRITICAL => RfcLogLevel::CRITICAL,
      LogLevel::ERROR => RfcLogLevel::ERROR,
      LogLevel::WARNING => RfcLogLevel::WARNING,
      LogLevel::NOTICE => RfcLogLevel::NOTICE,
      LogLevel::INFO => RfcLogLevel::INFO,
      LogLevel::DEBUG => RfcLogLevel::DEBUG,
    ];
    $rfcLogLevel = $levels[$level] ?? RfcLogLevel::ERROR;
    if ((int)$this->config->get('log_level') >= $rfcLogLevel) {
      $this->logger->log($level, $message, $context);
    }
  }

}
