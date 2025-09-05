<?php

namespace Drupal\os2loop_cura_login\Drush\Commands;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Serialization\Yaml;
use Drupal\Core\Url;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Firebase\JWT\JWT;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A Drush command file.
 */
final class Os2loopCuraLoginCommands extends DrushCommands {
  use AutowireTrait;

  /**
   * Constructs an Os2loopCuraLoginCommands object.
   */
  public function __construct(
    private readonly TimeInterface $time,
    private readonly ClientInterface $httpClient,
  ) {
    parent::__construct();
  }

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'os2loop-cura-login:get-login-url')]
  #[CLI\Argument(name: 'username', description: 'The username.')]
  #[CLI\Option(name: 'post', description: 'Use POST to get the login URL')]
  #[CLI\Usage(name: 'os2loop-cura-login:get-login-url test@example.com', description: 'Get login URL')]
  public function getLoginUrl(
    $username,
    $options = [
      'linkUrl' => NULL,
      'get' => NULL,
      'secret' => NULL,
      'algorithm' => 'HS256',
    ],
  ) {
    // https://github.com/firebase/php-jwt?tab=readme-ov-file#example
    $payload = [
      // Issued at.
      'iat' => $this->time->getRequestTime(),
      // Expire af 60 seconds.
      'exp' => $this->time->getRequestTime() + 60,
      'username' => $username,
    ];
    $jwt = JWT::encode($payload, $options['secret'], $options['algorithm']);

    $routeName = 'os2loop_cura_login.start';
    $routeParameters = [];
    $requestOptions = [];
    if ($name = $options['get']) {
      $method = Request::METHOD_GET;
      $routeParameters[$name] = $jwt;
      if ('jwt' === $name) {
        $routeName = 'os2loop_cura_login.start_get_jwt';
      }
    }
    else {
      $method = Request::METHOD_POST;
      $requestOptions['form_params'] = ['payload' => $jwt];
    }
    $url = Url::fromRoute($routeName, $routeParameters)->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    $this->io()->writeln($method === Request::METHOD_POST
      ? sprintf('POST\'ing to %s', $url)
      : sprintf('GET\'ing %s', $url),
    );
    $response = $this->httpClient->request($method, $url, $requestOptions);

    $this->io()->writeln(Yaml::encode([
      'status' => $response->getStatusCode(),
      'headers' => Yaml::encode($response->getHeaders()),
      'body' => $response->getBody()->getContents(),
    ]));
  }

}
