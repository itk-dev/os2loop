<?php

namespace Drupal\os2loop_cura_login\Drush\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Url;
use Drupal\Core\Utility\Token;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Firebase\JWT\JWT;
use Http\Client\HttpClient;
use Psr\Http\Client\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * A Drush commandfile.
 */
final class Os2loopCuraLoginCommands extends DrushCommands
{
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
  public function commandName(
    $username,
    $options = [
      'get' => null,
      'secret' => null,
      'algorithm' => 'HS256',
    ]
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

    $routeParameters = [];
    $requestOptions = [];
    if ($name = $options['get']) {
      $method = Request::METHOD_GET;
      $routeParameters[$name] = $jwt;
    } else {
      $method = Request::METHOD_POST;
      $requestOptions['body'] = $jwt;
    }
    $url = Url::fromRoute('os2loop_cura_login.start', $routeParameters)->setAbsolute()->toString(true)->getGeneratedUrl();
    $this->io()->writeln($method === Request::METHOD_POST
      ? sprintf('POST\'ing to %s', $url)
      : sprintf('GET\'ing %s', $url),
    );
    $request = $this->httpClient->request($method, $url, $requestOptions);

    header('content-type: text/plain');
    echo var_export([
      $url,
      $request->getStatusCode(),
      $request->getBody()->getContents(),
    ], true);
    die(__FILE__ . ':' . __LINE__ . ':' . __METHOD__);
  }

}
