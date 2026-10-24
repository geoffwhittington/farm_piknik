<?php

declare(strict_types=1);

namespace Drupal\farm_piknik;

use Drupal\farm_oauth_client\Connection;

/**
 * Settings page. Connected state is the gray panel. Sign-in stays for setup.
 */
final class SettingsPage {

  /**
   * @return array<string, mixed>
   */
  public function build(
    bool $connected,
    bool $connectionOpen,
    Connection $connection,
    string $testResult = '',
  ): array {
    $page = [
      'wordmark' => Wordmark::element(),
      'status' => (new SettingsStatus())->build($connected, $connection, $testResult),
    ];
    if (!$connected) {
      $page['connection'] = (new SettingsLayout())->build($connection, $connected, $connectionOpen);
    }
    return $page;
  }

}
