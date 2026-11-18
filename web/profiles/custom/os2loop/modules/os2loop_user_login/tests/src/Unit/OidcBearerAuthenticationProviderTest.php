<?php

namespace Drupal\Tests\os2loop_user_login\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\externalauth\AuthmapInterface;
use Drupal\os2loop_user_login\Authentication\OidcBearerAuthenticationProvider;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the identity provider bearer token authentication.
 */
#[Group('os2loop_tests')]
#[Group('os2loop_user_login')]
class OidcBearerAuthenticationProviderTest extends UnitTestCase {

  private const ISSUER = 'https://idp.example/v2.0/';

  private const AUDIENCE = 'site-api-id';

  /**
   * The signing key of the test identity provider.
   */
  private \OpenSSLAsymmetricKey $key;

  /**
   * The provider under test.
   */
  private OidcBearerAuthenticationProvider $provider;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->key = self::createKey();
    $rsa = openssl_pkey_get_details($this->key)['rsa'];
    $jwks = [
      'keys' => [
        [
          'kty' => 'RSA',
          'use' => 'sig',
          'kid' => 'k1',
          'n' => JWT::urlsafeB64Encode($rsa['n']),
          'e' => JWT::urlsafeB64Encode($rsa['e']),
        ],
      ],
    ];
    new Settings([
      'os2loop_oidc_bearer' => [
        'issuer' => self::ISSUER,
        'jwks_uri' => 'https://idp.example/keys',
        'audience' => self::AUDIENCE,
      ],
    ]);

    $user = $this->createMock(UserInterface::class);
    $user->method('isActive')->willReturn(TRUE);
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('load')->willReturnMap([[7, $user]]);
    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')->willReturn($storage);
    $authmap = $this->createMock(AuthmapInterface::class);
    $authmap->method('getUid')->willReturnMap([['subject-1', 'openid_connect.generic', 7]]);

    $this->provider = new OidcBearerAuthenticationProvider(
      new Client(['handler' => HandlerStack::create(new MockHandler([new Response(200, [], json_encode($jwks))]))]),
      $this->createMock(CacheBackendInterface::class),
      $authmap,
      $entityTypeManager,
      new NullLogger(),
    );
  }

  /**
   * Tests that only a valid access token of a known user gives an account.
   */
  #[DataProvider('claimsProvider')]
  public function testAuthenticate(array $claims, bool $expected): void {
    $request = $this->createRequest($claims, $this->key);

    $this->assertSame($expected, $this->provider->authenticate($request) !== NULL);
    $this->assertSame($expected, $request->attributes->has(OidcBearerAuthenticationProvider::REQUEST_ATTRIBUTE));
  }

  /**
   * Provides claim changes and the expected result.
   */
  public static function claimsProvider(): array {
    return [
      'valid token' => [[], TRUE],
      'audience as list' => [['aud' => ['other', self::AUDIENCE]], TRUE],
      'wrong audience' => [['aud' => 'other'], FALSE],
      'wrong issuer' => [['iss' => 'https://evil.example/'], FALSE],
      'expired' => [['exp' => time() - 300], FALSE],
      'ID token without scp' => [['scp' => NULL], FALSE],
      'unknown user' => [['sub' => 'subject-2'], FALSE],
    ];
  }

  /**
   * Tests that a token with a signature of a different key is refused.
   */
  public function testWrongSignature(): void {
    $this->assertNull($this->provider->authenticate($this->createRequest([], self::createKey())));
  }

  /**
   * Tests that the provider applies only to tokens of the configured issuer.
   */
  public function testApplies(): void {
    $this->assertTrue($this->provider->applies($this->createRequest([], $this->key)));
    $this->assertFalse($this->provider->applies($this->createRequest(['iss' => 'https://other.example/'], $this->key)));
    $this->assertFalse($this->provider->applies(Request::create('/mcp', 'POST')));
  }

  /**
   * Creates a request with a signed token.
   */
  private function createRequest(array $claims, \OpenSSLAsymmetricKey $key): Request {
    $claims = array_filter($claims + [
      'iss' => self::ISSUER,
      'aud' => self::AUDIENCE,
      'sub' => 'subject-1',
      'scp' => 'mcp',
      'exp' => time() + 300,
    ], static fn ($value) => $value !== NULL);
    $request = Request::create('/mcp', 'POST');
    $request->headers->set('Authorization', 'Bearer ' . JWT::encode($claims, $key, 'RS256', 'k1'));

    return $request;
  }

  /**
   * Creates an RSA key.
   */
  private static function createKey(): \OpenSSLAsymmetricKey {
    return openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
  }

}
