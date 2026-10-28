<?php

declare(strict_types=1);

namespace Drupal\farm_oauth_client;

/**
 * Best-effort call to the provider's OAuth revoke endpoint.
 */
final class TokenRevoke {

  public function __construct(
    private Connection $connection,
    private PinnedRequest $http,
  ) {}

  /**
   * Revokes the refresh token (preferred) or access token on the remote host.
   */
  public function revoke(string $provider): void {
    $base = $this->connection->baseUrl($provider);
    if ($base === '') {
      return;
    }
    $hint = '';
    $token = '';
    try {
      if ($this->connection->hasRefresh($provider)) {
        $hint = 'refresh_token';
        $token = $this->connection->openRefresh($provider);
      }
      else {
        try {
          $hint = 'access_token';
          $token = $this->connection->openAccess($provider);
        }
        catch (\Throwable) {
          return;
        }
      }
      $this->http->send($base, 'POST', '/api/oauth/revoke', [
        'headers' => ['Accept' => 'application/json'],
        'form_params' => [
          'token' => $token,
          'token_type_hint' => $hint,
        ],
      ]);
    }
    catch (\Throwable) {
      // Local wipe still proceeds; remote revoke is best-effort.
    }
    finally {
      if ($token !== '') {
        sodium_memzero($token);
      }
    }
  }

}
