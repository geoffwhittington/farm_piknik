<?php

declare(strict_types=1);

namespace Drupal\farm_piknik;

/**
 * One settings button. Only the step in progress is primary.
 */
final class SettingsButton {

  /**
   * @param list<list<string>> $limit
   * @return array<string, mixed>
   */
  public static function make(string $name, string $method, \Stringable|string $label, bool $primary, array $limit = []): array {
    $button = [
      '#type' => 'submit',
      '#value' => $label,
      '#name' => $name,
      '#submit' => ['::' . $method],
      '#limit_validation_errors' => $limit,
    ];
    if ($primary) {
      $button['#attributes']['class'][] = 'button--primary';
    }
    return $button;
  }

  /**
   * @param array<string, mixed> $buttons
   * @return array<string, mixed>
   */
  public static function group(array $buttons): array {
    return [
      '#type' => 'container',
      '#attributes' => ['class' => ['farm-piknik-stage__actions']],
    ] + $buttons;
  }

}
