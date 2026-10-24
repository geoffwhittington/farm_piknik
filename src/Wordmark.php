<?php

declare(strict_types=1);

namespace Drupal\farm_piknik;

/**
 * Piknik wordmark: full intro on settings, logo only on the board.
 */
final class Wordmark {

  /**
   * Logo plus “what / why join” intro — settings only.
   */
  public static function element(): array {
    $urls = self::imageUrls();
    return [
      '#type' => 'inline_template',
      '#weight' => -100,
      '#template' => '<div class="farm-piknik-wordmark"><img class="farm-piknik-wordmark__on-light" src="{{ light }}" alt="Piknik"><img class="farm-piknik-wordmark__on-dark" src="{{ dark }}" alt=""></div><div class="farm-piknik-intro"><p>{{ what }}</p><p><strong>{{ why_label }}</strong></p><ul><li>{{ business }}</li><li>{{ operational }}</li></ul></div>',
      '#context' => [
        'light' => $urls['light'],
        'dark' => $urls['dark'],
        'what' => t('Connect this farm to your local food system.'),
        'why_label' => t('Why join:'),
        'business' => t('Nearby buyers can find what you have available, and you can post a listing or a request.'),
        'operational' => t('When a harvest record is marked done, Piknik can post one offer from that record.'),
      ],
    ];
  }

  /**
   * Board logo. The place card sits under this, where the old summary was.
   */
  public static function board(): array {
    $urls = self::imageUrls();
    return [
      '#type' => 'inline_template',
      '#weight' => -100,
      '#template' => '<div class="farm-piknik-wordmark"><img class="farm-piknik-wordmark__on-light" src="{{ light }}" alt="Piknik"><img class="farm-piknik-wordmark__on-dark" src="{{ dark }}" alt=""></div>',
      '#context' => [
        'light' => $urls['light'],
        'dark' => $urls['dark'],
      ],
    ];
  }

  /**
   * @return array{light: string, dark: string}
   */
  private static function imageUrls(): array {
    $path = \Drupal::service('extension.list.module')->getPath('farm_piknik');
    $urls = \Drupal::service('file_url_generator');
    return [
      'light' => $urls->generateString($path . '/images/piknik-wordmark.png'),
      'dark' => $urls->generateString($path . '/images/piknik-wordmark-light.png'),
    ];
  }

}
