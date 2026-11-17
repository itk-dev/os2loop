<?php

namespace Drupal\os2loop_user_login\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Lets the MCP route accept access tokens of the identity provider.
 */
final class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    $route = $collection->get('mcp_server.handle');
    if ($route === NULL) {
      return;
    }
    $auth = $route->getOption('_auth') ?? [];
    if (!in_array('os2loop_oidc_bearer', $auth, TRUE)) {
      $auth[] = 'os2loop_oidc_bearer';
      $route->setOption('_auth', $auth);
    }
  }

}
