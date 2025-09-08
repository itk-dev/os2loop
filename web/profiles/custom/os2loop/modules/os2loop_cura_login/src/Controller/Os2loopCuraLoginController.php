<?php

declare(strict_types=1);

namespace Drupal\os2loop_cura_login\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Url;
use Drupal\os2loop_cura_login\CuraHelper;
use Drupal\os2loop_cura_login\IdPHelper;
use Drupal\os2loop_cura_login\Settings;
use Drupal\os2loop_cura_login\UserHelper;
use Drupal\user\UserInterface;
use Firebase\JWT\JWT;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
   * Constructor.
   */
  public function __construct(
    private readonly CuraHelper $curaHelper,
    private readonly IdPHelper $idPHelper,
    private readonly UserHelper $userHelper,
    private readonly Settings $settings,
    private readonly TimeInterface $time,
    #[Autowire(service: 'logger.channel.os2loop_cura_login')]
    private readonly LoggerInterface $logger,
  ) {
    $this->curaHelper->setController($this);
    $this->idPHelper->setController($this);
    $this->userHelper->setController($this);
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
        $name = $this->settings->getCuraSettings()->getPayloadName();
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

      [$payload, $username] = $this->curaHelper->decodeJwt($jwt);

      if (empty($username)) {
        throw new BadRequestHttpException('Missing username');
      }

      // Check that we can get userinfo.
      $userinfo = $this->idPHelper->fetchUserInfo($username);

      $this->debug('@debug', [
        '@debug' => json_encode([
          'userinfo' => $userinfo,
        ]),
      ]);

      if (empty($userinfo)) {
        // Don't disclose whether or not the user exists.
        throw new BadRequestHttpException();
      }

      $user = $this->userHelper->ensureUser($username, $userinfo);

      $this->debug('@debug', [
        '@debug' => json_encode([
          'user' => $user->toArray(),
        ]),
      ]);

      if (empty($user)) {
        // Don't disclose whether or not the user exists.
        throw new BadRequestHttpException();
      }

      return Request::METHOD_POST === $request->getMethod()
        ? $this->createAuthenticateResponse($user, $request)
        : $this->authenticateUser($user, $request);
    }
    catch (\Exception $exception) {
      $this->error('start: @message', ['@message' => $exception->getMessage(), $exception]);
      throw new BadRequestException($exception->getMessage());
    }
  }

  /**
   * Authenticate action.
   */
  public function authenticate(Request $request, string $jwt): Response {
    try {
      if (empty($jwt)) {
        throw new BadRequestHttpException();
      }

      [$payload, $_] = $this->curaHelper->decodeJwt($jwt);
      $username = $payload['username'] ?? NULL;
      if (empty($username)) {
        throw new BadRequestHttpException();
      }

      $user = $this->userHelper->loadUser($username);
      if (empty($user)) {
        // Don't disclose whether or not the user exists.
        throw new BadRequestHttpException();
      }

      return $this->authenticateUser($user, $request);
    }
    catch (\Exception $exception) {
      $this->error('authenticate: @message', ['@message' => $exception->getMessage(), $exception]);
      throw new BadRequestException($exception->getMessage());
    }
  }

  /**
   * Create authenticate response.
   */
  private function createAuthenticateResponse(UserInterface $user, Request $request): Response {
    // https://github.com/firebase/php-jwt?tab=readme-ov-file#example
    $payload = [
      // Issued at.
      'iat' => $this->time->getRequestTime(),
      // Expire af 60 seconds.
      'exp' => $this->time->getRequestTime() + 60,
      'username' => $user->getAccountName(),
    ];
    $jwt = $this->encodeJwt($payload);

    $routeParameters = [
      'jwt' => $jwt,
    ];
    if ($destination = $request->query->get('destination')) {
      $routeParameters['destination'] = $destination;
    }

    $url = Url::fromRoute('os2loop_cura_login.authenticate', $routeParameters)
      ->setAbsolute()->toString(TRUE)->getGeneratedUrl();

    return new Response($url);
  }

  /**
   * Authenticate user.
   */
  private function authenticateUser($user, Request $request): Response {
    $this->userHelper->authenticateUser($user);

    $this->messenger()->addStatus($this->t('Welcome Cura user @user.', ['@user' => $user->getDisplayName()]));
    $url = Url::fromRoute('<front>');
    if ($destination = $request->query->get('destination')) {
      try {
        $url = Url::fromUserInput($destination);
      }
      catch (\Exception) {
        // Ignore any exceptions.
      }
    }

    return new RedirectResponse($url->setAbsolute()->toString());
  }

  /**
   * Encode JWT.
   */
  private function encodeJwt(array $payload): string {
    $settings = $this->settings->getCuraSettings();

    return JWT::encode($payload, $settings->getSigningSecret(), $settings->getSigningAlgorithm());
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
    if ($this->settings->getLogLevel() >= $rfcLogLevel) {
      $this->logger->log($level, $message, $context);
    }
  }

}
