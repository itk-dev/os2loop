<?php

namespace Drupal\os2loop_user_login\Authentication;

use Drupal\Core\Authentication\AuthenticationProviderInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\externalauth\AuthmapInterface;
use Drupal\user\UserInterface;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\ClientInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Authenticates a request with an access token of the identity provider.
 *
 * The site and the client (for example Open WebUI) use the same OpenID Connect
 * identity provider. The token must be a JWT that the identity provider issued
 * for this site. The user must have signed in to this site one time.
 *
 * Set $settings['os2loop_oidc_bearer'] in settings.local.php to enable it:
 * - issuer: the "issuer" value of the OpenID Connect discovery document.
 * - jwks_uri: the "jwks_uri" value of the same document.
 * - audience: the application ID of the API that the token is for.
 * - claim: (optional) the claim that holds the user ID. The default is "sub".
 */
final class OidcBearerAuthenticationProvider implements AuthenticationProviderInterface {

  /**
   * The request attribute that marks a request that this provider accepted.
   */
  public const REQUEST_ATTRIBUTE = '_os2loop_oidc_bearer';

  private const AUTHMAP_PROVIDER = 'openid_connect.generic';

  private const JWKS_CACHE_ID = 'os2loop_user_login:oidc_bearer_jwks';

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly CacheBackendInterface $cache,
    private readonly AuthmapInterface $authmap,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LoggerInterface $logger,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function applies(Request $request): bool {
    $issuer = Settings::get('os2loop_oidc_bearer')['issuer'] ?? NULL;
    $parts = explode('.', (string) $this->getToken($request));
    if (!$issuer || count($parts) !== 3) {
      return FALSE;
    }
    // Read the issuer without a signature check. All other tokens go to the
    // subsequent providers. authenticate() does the full validation.
    $claims = json_decode((string) base64_decode(strtr($parts[1], '-_', '+/')), TRUE);

    return is_array($claims) && ($claims['iss'] ?? NULL) === $issuer;
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate(Request $request): ?AccountInterface {
    $settings = Settings::get('os2loop_oidc_bearer', []);
    try {
      // Permit 60 seconds of clock difference.
      JWT::$leeway = 60;
      $claims = (array) JWT::decode(
        (string) $this->getToken($request),
        JWK::parseKeySet($this->getJwks($settings['jwks_uri'] ?? ''), 'RS256')
      );
    }
    catch (\Exception $exception) {
      return $this->refuse($exception->getMessage());
    }

    if (($claims['iss'] ?? NULL) !== ($settings['issuer'] ?? NULL)) {
      return $this->refuse('wrong issuer');
    }
    if (empty($settings['audience']) || !in_array($settings['audience'], (array) ($claims['aud'] ?? []), TRUE)) {
      return $this->refuse('wrong audience');
    }
    // An ID token has no scope claim. Accept access tokens only.
    if (empty($claims['scp'])) {
      return $this->refuse('no scp claim');
    }
    $subject = $claims[$settings['claim'] ?? 'sub'] ?? NULL;
    $uid = is_string($subject) && $subject !== '' ? $this->authmap->getUid($subject, self::AUTHMAP_PROVIDER) : FALSE;
    $account = $uid ? $this->entityTypeManager->getStorage('user')->load($uid) : NULL;
    if (!$account instanceof UserInterface || !$account->isActive()) {
      return $this->refuse(sprintf('no active user for the subject "%s"', is_string($subject) ? $subject : ''));
    }

    $request->attributes->set(self::REQUEST_ATTRIBUTE, TRUE);

    return $account;
  }

  /**
   * Gets the bearer token of the request.
   */
  private function getToken(Request $request): ?string {
    $header = (string) $request->headers->get('Authorization');

    return str_starts_with($header, 'Bearer ') ? substr($header, 7) : NULL;
  }

  /**
   * Gets the signing keys of the identity provider.
   *
   * @return array<string, mixed>
   *   The JSON Web Key Set.
   */
  private function getJwks(string $uri): array {
    // ponytail: no new download for an unknown key ID. A new signing key works
    // after one hour at most. Add a rate-limited download if this is too long.
    if ($cached = $this->cache->get(self::JWKS_CACHE_ID)) {
      return $cached->data;
    }
    $jwks = json_decode((string) $this->httpClient->request('GET', $uri, ['timeout' => 5])->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);
    $this->cache->set(self::JWKS_CACHE_ID, $jwks, time() + 3600);

    return $jwks;
  }

  /**
   * Writes the reason to the log. The log does not get the token.
   */
  private function refuse(string $reason): ?AccountInterface {
    $this->logger->warning('OIDC bearer token refused: @reason', ['@reason' => $reason]);

    return NULL;
  }

}
