<?php

declare(strict_types=1);

namespace Drupal\os2loop_cura_login\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\os2loop_cura_login\Settings;
use Drupal\user\UserInterface;
use Drupal\user\UserStorageInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
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
   * Constructor.
   */
  public function __construct(
    private readonly Settings $settings,
    private readonly TimeInterface $time,
    #[Autowire(service: 'logger.channel.os2loop_cura_login')]
    private readonly LoggerInterface $logger,
  ) {
    $this->userStorage = $this->entityTypeManager()->getStorage('user');
  }

  /**
   * Start user authentication.
   */
  public function start(Request $request, ?string $jwt): Response {
    try {
      $content = NULL;
      try {
        $content = (string) $request->getContent();
      }
      catch (\Exception) {
      }
      $this->debug('@debug', [
        '@debug' => json_encode([
          'method' => $request->getMethod(),
          'headers' => $request->headers->all(),
          'query' => $request->query->all(),
          'content' => $content,
        ]),
      ]);

      if (empty($jwt)) {
        $name = $this->settings->getPayloadName();
        $jwt = Request::METHOD_POST === $request->getMethod()
          ? $request->request->getString($name)
          : $request->query->getString($name);
      }

      $this->debug('@debug', [
        '@debug' => json_encode([
          'jwt' => $jwt,
        ]),
      ]);

      if (empty($jwt)) {
        throw new BadRequestHttpException('Missing or empty JWT');
      }

      $payload = $this->decodeJwt($jwt);

      $this->debug('@debug', [
        '@debug' => json_encode([
          'payload' => $payload,
        ]),
      ]);

      $username = $payload['username'] ?? $payload['brugerId'] ?? NULL;
      if (empty($username)) {
        throw new BadRequestHttpException('Missing username');
      }

      // Check that we can get userinfo.
      $userinfo = $this->fetchUserinfo($username);

      $this->debug('@debug', [
        '@debug' => json_encode([
          'userinfo' => $userinfo,
        ]),
      ]);

      if (empty($userinfo)) {
        // Don't disclose whether or not the user exists.
        throw new BadRequestHttpException();
      }

      $user = $this->ensureUser($username, $userinfo);

      $this->debug('@debug', [
        '@debug' => json_encode([
          'user' => $user,
        ]),
      ]);

      if (empty($user)) {
        // Don't disclose whether or not the user exists.
        throw new BadRequestHttpException();
      }

      return Request::METHOD_POST === $request->getMethod()
        ? $this->createAuthenticateResponse($user)
        : $this->authenticateUser($user);
    }
    catch (\Exception $exception) {
      $this->error('start: @message', ['@message' => $exception->getMessage(), $exception]);
      throw new BadRequestException($exception->getMessage());
    }
  }

  /**
   * Create authenticate response.
   */
  private function createAuthenticateResponse(UserInterface $user): Response {
    // https://github.com/firebase/php-jwt?tab=readme-ov-file#example
    $payload = [
      // Issued at.
      'iat' => $this->time->getRequestTime(),
      // Expire af 60 seconds.
      'exp' => $this->time->getRequestTime() + 60,
      'username' => $user->getAccountName(),
    ];
    $jwt = $this->encodeJwt($payload);

    $url = Url::fromRoute('os2loop_cura_login.authenticate', [
      'jwt' => $jwt,
    ])->setAbsolute()->toString(TRUE)->getGeneratedUrl();

    return new Response($url);
  }

  /**
   * Authenticate action.
   */
  public function authenticate(Request $request): Response {
    try {
      $jwt = $request->get('jwt');
      if (empty($jwt)) {
        throw new BadRequestHttpException();
      }

      $payload = $this->decodeJwt($jwt);
      $username = $payload['username'] ?? NULL;
      if (empty($username)) {
        throw new BadRequestHttpException();
      }

      $user = $this->loadUser($username);
      if (empty($user)) {
        // Don't disclose whether or not the user exists.
        throw new BadRequestHttpException();
      }

      return $this->authenticateUser($user);
    }
    catch (\Exception $exception) {
      $this->error('authenticate: @message', ['@message' => $exception->getMessage(), $exception]);
      throw new BadRequestException($exception->getMessage());
    }
  }

  /**
   * Authenticate user.
   */
  private function authenticateUser($user): Response {
    user_login_finalize($user);

    $this->messenger()->addStatus($this->t('Welcome Cura user @user.', ['@user' => $user->getDisplayName()]));

    return $this->redirect('<front>');
  }

  /**
   * Encode JWT.
   */
  private function encodeJwt(array $payload): string {
    $secret = $this->settings->getSigningSecret();
    // @todo Get rid of the double base64 encoding.
    $secret = base64_decode($secret);

    return JWT::encode($payload, $secret, $this->settings->getSigningAlgorithm());
  }

  /**
   * Decode JWT.
   */
  private function decodeJwt(string $jwt): array {
    $secret = $this->settings->getSigningSecret();
    // @todo Get rid of the double base64 encoding.
    $secret = base64_decode($secret);

    $originalLeeway = JWT::$leeway;
    $leeway = $this->settings->getJwtLeeway();
    if ($leeway > 0) {
      JWT::$leeway = $leeway;
    }
    $payload = (array) JWT::decode($jwt, new Key($secret, $this->settings->getSigningAlgorithm()));
    JWT::$leeway = $originalLeeway;

    return $payload;
  }

  /**
   * Get user info from userinfo endpoint.
   */
  private function fetchUserinfo(string $username): array {
    return [
      // Drupal user fields.
      'name' => $username,
      'mail' => $username . '@cura.example.com',

      // OS2Lloop fields
      // 'os2loop_user_address' => '',
      // 'os2loop_user_areas_of_expertise' => '',
      // 'os2loop_user_biography' => '',
      // 'os2loop_user_city' => '',
      // 'os2loop_user_external_list' => '',.
      'os2loop_user_family_name' => 'Cura',
      'os2loop_user_given_name' => 'User',
      // 'os2loop_user_image' => '',
      // 'os2loop_user_internal_list' => '',
      // 'os2loop_user_job_title' => '',
      // 'os2loop_user_phone_number' => '',
      // 'os2loop_user_place' => '',
      // 'os2loop_user_postal_code' => '',
      // 'os2loop_user_professions' => '',
    ];
  }

  /**
   * Ensure user exists.
   *
   * @param string $username
   *   The username.
   * @param array $userinfo
   *   The user info to set on the user.
   *
   * @return \Drupal\user\Entity\UserInterface
   *   The newly created or updated user.
   */
  private function ensureUser(string $username, array $userinfo): UserInterface {
    $user = $this->loadUser($username);

    if (NULL === $user) {
      $user = $this->userStorage->create();
    }

    foreach ($userinfo as $field => $value) {
      $currentValue = $user->get($field);
      if ($currentValue !== $value) {
        $user->set($field, $value);
      }
    }

    // Make sure that the user is active.
    $user
      ->activate()
      ->save();

    return $user;
  }

  /**
   * Load user by username.
   */
  private function loadUser(string $username) : ?UserInterface {
    $users = $this->userStorage->loadByProperties(['name' => $username]);

    return reset($users) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function log($level, \Stringable|string $message, array $context = []): void {
    // Lifted from LoggerChannel.
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
    if ((int) $this->settings->getLogLevel() >= $rfcLogLevel) {
      $this->logger->log($level, $message, $context);
    }
  }

}
