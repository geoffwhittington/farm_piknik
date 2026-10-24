<?php

declare(strict_types=1);

namespace Drupal\farm_piknik;

use Drupal\farm_oauth_client\Connection;
use Drupal\farm_oauth_client\PinnedRequest;

/**
 * Checks that the saved client id and secret are still accepted.
 *
 * Sends a refresh grant with a token that cannot match a real one.
 * invalid_grant means the secret was accepted and no token was issued.
 */
final class ConnectionProbe {

  private const PROVIDER = 'piknik';

  public function __construct(
    private Connection $connection,
    private PinnedRequest $http,
  ) {}

  public function credentialsWork(): bool {
    if ($this->connection->clientId(self::PROVIDER) === '' || !$this->connection->hasSecret(self::PROVIDER)) {
      return FALSE;
    }
    $secret = '';
    try {
      $secret = $this->connection->openSecret(self::PROVIDER);
      $response = $this->http->send($this->connection->baseUrl(self::PROVIDER), 'POST', '/api/oauth/token', [
        'headers' => ['Accept' => 'application/json'],
        'form_params' => [
          'grant_type' => 'refresh_token',
          'refresh_token' => str_repeat('0', 64),
          'client_id' => $this->connection->clientId(self::PROVIDER),
          'client_secret' => $secret,
        ],
      ]);
    }
    catch (\Throwable) {
      return FALSE;
    }
    finally {
      if ($secret !== '') {
        sodium_memzero($secret);
      }
    }
    $decoded = json_decode((string) $response->getBody(), TRUE);
    $error = is_array($decoded) && is_string($decoded['error'] ?? NULL) ? $decoded['error'] : '';
    return $error === 'invalid_grant';
  }

}
