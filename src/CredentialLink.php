<?php

declare(strict_types=1);

namespace Drupal\farm_piknik;

use Drupal\Core\Url;
use Drupal\farm_oauth_client\Connection;
use Drupal\farm_oauth_client\SiteCallback;

/**
 * Link to Piknik's sign-in details page. The name is reflected there.
 */
final class CredentialLink {

  public static function url(Connection $connection): string {
    $base = $connection->baseUrl('piknik');
    if ($base === '') {
      $base = ConnectionFields::testing() ? 'http://localhost:3000' : ConnectionFields::ADDRESS;
    }
    $callback = SiteCallback::url('piknik');
    $query = http_build_query([
      'app' => 'FarmOS',
      'scopes' => 'marketplace:write',
      'redirect_uri' => $callback,
    ]);
    return rtrim($base, '/') . '/oauth/credentials?' . $query;
  }

  /**
   * @return array<string, mixed>
   */
  public static function element(Connection $connection): array {
    return [
      '#type' => 'link',
      '#title' => t('Create sign-in details on Piknik'),
      '#url' => Url::fromUri(self::url($connection)),
      '#attributes' => [
        'class' => ['farm-piknik-credential-link'],
        'target' => '_blank',
        'rel' => 'noopener noreferrer',
      ],
    ];
  }

}
