<?php

declare(strict_types=1);

namespace Drupal\farm_piknik;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\farm_oauth_client\Connection;

/**
 * Connected state is a gray panel: address, client ID, Disconnect.
 */
final class SettingsStatus {

  use StringTranslationTrait;

  /**
   * @return array<string, mixed>
   */
  public function build(bool $connected, Connection $connection, string $testResult = ''): array {
    if (!$connected) {
      return [
        '#type' => 'container',
        '#weight' => 0,
        '#attributes' => ['class' => ['farm-piknik-status', 'is-disconnected']],
        'message' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Not connected to Piknik.'),
        ],
      ];
    }
    $address = $connection->baseUrl('piknik');
    $href = $this->httpUrl($address);
    $panel = [
      '#type' => 'container',
      '#weight' => 0,
      '#attributes' => ['class' => ['farm-piknik-status', 'is-connected']],
      'message' => [
        '#type' => 'inline_template',
        '#template' => '<p>{% if address and url %}{{ lead }} <a href="{{ url }}">{{ address }}</a>{% elseif address %}{{ lead }} {{ address }}{% else %}{{ fallback }}{% endif %}</p>',
        '#context' => [
          'lead' => $this->t('Connected to Piknik at'),
          'address' => $address,
          'url' => $href,
          'fallback' => $this->t('Connected to Piknik.'),
        ],
      ],
      'client' => [
        '#type' => 'inline_template',
        '#template' => '<dl class="farm-piknik-summary"><div class="farm-piknik-summary__row"><dt>{{ label }}</dt><dd>{{ value }}</dd></div></dl>',
        '#context' => [
          'label' => $this->t('Client ID'),
          'value' => $connection->clientId('piknik'),
        ],
      ],
      'actions' => SettingsButton::group([
        'test_connection' => SettingsButton::make('test_connection', 'testConnection', $this->t('Test connection'), TRUE),
        'disconnect' => SettingsButton::make('disconnect', 'disconnect', $this->t('Disconnect'), FALSE),
      ]),
    ];
    if ($testResult === 'ok' || $testResult === 'fail') {
      $panel['result'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $testResult === 'ok' ? $this->t('Connected') : $this->t('Failed to connect'),
        '#attributes' => ['class' => ['farm-piknik-test', $testResult === 'ok' ? 'is-ok' : 'is-fail']],
      ];
    }
    return $panel;
  }

  private function httpUrl(string $url): string {
    return preg_match('#^https?://#i', $url) === 1 ? $url : '';
  }

}
