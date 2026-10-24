<?php

declare(strict_types=1);

namespace Drupal\farm_piknik;

use Drupal\farm_oauth_client\Connection;
use Drupal\farm_oauth_client\OAuthFlow;
use Drupal\farm_oauth_client\PinnedRequest;

/**
 * Lists the Piknik farms the signed-in user can post for.
 */
final class PlaceDirectory {

  public function __construct(
    private OAuthFlow $flow,
    private PinnedRequest $http,
    private Connection $connection,
  ) {}

  /**
   * @return array<string, string>
   */
  public function choices(): array {
    $choices = [];
    foreach ($this->mine() as $place) {
      if (($place['primaryRole'] ?? '') !== 'FARM') {
        continue;
      }
      $id = $this->idOf($place);
      if ($id === '') {
        continue;
      }
      $name = $this->nameOf($place);
      $choices[$id] = $name !== '' ? $name : $id;
    }
    return $choices;
  }

  /** Name for a saved place. Empty when Piknik has no name for it. */
  public function displayName(string $id): string {
    return $this->profile($id)['name'];
  }

  /**
   * Name, emoji, description, and public-page fields. Address is never included.
   *
   * @return array{name: string, emoji: string, description: string, type: string, role: string}
   */
  public function profile(string $id): array {
    $blank = ['name' => '', 'emoji' => '', 'description' => '', 'type' => '', 'role' => ''];
    if (!$this->validId($id)) {
      return $blank;
    }
    $listed = $this->listed($id);
    $place = $listed !== [] ? $listed : $this->fetchPlace($id);
    $name = $this->nameOf($place);
    return [
      'name' => ($name !== '' && $name !== $id) ? $name : '',
      'emoji' => $this->listEmoji($listed),
      'description' => $this->textOf($place, 'description'),
      'type' => is_string($place['participantType'] ?? NULL) ? $place['participantType'] : '',
      'role' => is_string($place['primaryRole'] ?? NULL) ? $place['primaryRole'] : '',
    ];
  }

  public function publicUrl(string $id, string $type, string $role): string {
    return PlaceWebUrl::forPlace($this->connection->baseUrl('piknik'), $id, $type, $role);
  }

  /**
   * @return list<array<string, mixed>>
   */
  private function mine(): array {
    $decoded = $this->get('/api/v2/participants?mine=true&role=FARM&limit=20');
    if (!is_array($decoded) || !is_array($decoded['participants'] ?? NULL)) {
      throw new \RuntimeException('Piknik did not return this user\'s places.');
    }
    return array_values(array_filter($decoded['participants'], 'is_array'));
  }

  /**
   * @return array<string, mixed>
   */
  private function fetchPlace(string $id): array {
    try {
      $decoded = $this->get('/api/v2/participants/' . rawurlencode($id));
    }
    catch (\Throwable) {
      return [];
    }
    if (!is_array($decoded)) {
      return [];
    }
    $place = is_array($decoded['participant'] ?? NULL) ? $decoded['participant'] : $decoded;
    return is_array($place) ? $place : [];
  }

  private function get(string $path): mixed {
    $token = $this->flow->bearer('piknik');
    try {
      $response = $this->http->send($this->connection->baseUrl('piknik'), 'GET', $path, [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'Accept' => 'application/json',
        ],
      ]);
    }
    finally {
      sodium_memzero($token);
    }
    if ($response->getStatusCode() !== 200) {
      throw new \RuntimeException('Piknik did not return this place.');
    }
    return json_decode((string) $response->getBody(), TRUE);
  }

  /**
   * @param array<string, mixed> $place
   */
  private function idOf(array $place): string {
    $id = $place['id'] ?? '';
    return is_string($id) && $this->validId($id) ? $id : '';
  }

  /**
   * @param array<string, mixed> $place
   */
  private function nameOf(array $place): string {
    return $this->textOf($place, 'name');
  }

  /**
   * @param array<string, mixed> $place
   */
  private function textOf(array $place, string $key): string {
    $value = $place[$key] ?? NULL;
    return is_string($value) ? trim($value) : '';
  }

  /**
   * List row for one place. Includes description, customEmoji, and computed icon.
   *
   * @return array<string, mixed>
   */
  private function listed(string $id): array {
    try {
      $decoded = $this->get('/api/v2/participants?ids=' . rawurlencode($id));
    }
    catch (\Throwable) {
      return [];
    }
    if (!is_array($decoded) || !is_array($decoded['participants'] ?? NULL)) {
      return [];
    }
    foreach ($decoded['participants'] as $row) {
      if (is_array($row) && $this->idOf($row) === $id) {
        return $row;
      }
    }
    return [];
  }

  /**
   * customEmoji from the list when set, otherwise the list's computed icon.
   *
   * @param array<string, mixed> $listed
   */
  private function listEmoji(array $listed): string {
    foreach (['customEmoji', 'icon'] as $key) {
      $value = $listed[$key] ?? NULL;
      if (!is_string($value)) {
        continue;
      }
      $emoji = trim($value);
      if ($emoji !== '' && mb_strlen($emoji) <= 8) {
        return $emoji;
      }
    }
    return '';
  }

  private function validId(string $id): bool {
    return (bool) preg_match('/^[A-Za-z0-9_-]{8,40}$/', $id);
  }

}
