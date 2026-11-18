<?php

namespace Drupal\os2loop_user_login\Mcp;

use Drupal\os2loop_user_login\Authentication\OidcBearerAuthenticationProvider;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Skips the OAuth scope check for a user with an identity provider token.
 *
 * The scope check of mcp_server_oauth reads only tokens that Drupal issued.
 * An identity provider token has no such scopes. The Drupal permissions of the
 * user control the access.
 */
final class OidcBearerToolScopeMiddleware implements MiddlewareInterface {

  public function __construct(
    private readonly MiddlewareInterface $inner,
    private readonly RequestStack $requestStack,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
    if ($this->requestStack->getCurrentRequest()?->attributes->get(OidcBearerAuthenticationProvider::REQUEST_ATTRIBUTE)) {
      return $handler->handle($request);
    }

    return $this->inner->process($request, $handler);
  }

}
