<?php

declare(strict_types=1);

namespace Drupal\farm_piknik;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\farm_oauth_client\Connection;

/**
 * Sign-in step: address, then credentials, then a summary.
 */
final class SettingsLayout {

  use StringTranslationTrait;

  /**
   * @return array<string, mixed>
   */
  public function build(Connection $connection, bool $connected, bool $editing): array {
    $section = [
      '#type' => 'container',
      '#weight' => 20,
      '#attributes' => ['class' => ['farm-piknik-stage', 'farm-piknik-connection']],
      'heading' => ['#weight' => -10] + $this->heading($this->t('Sign in')),
      'link' => ['#weight' => -4] + CredentialLink::element($connection),
    ];
    $ready = $connection->clientId('piknik') !== '' && $connection->hasSecret('piknik');
    if ($ready && !$editing) {
      $section['summary'] = $this->summary($connection);
      $buttons = [
        'edit_connection' => SettingsButton::make('edit_connection', 'openConnection', $this->t('Edit'), FALSE),
      ];
      if (!$connected) {
        $buttons = [
          'signin' => SettingsButton::make('signin', 'signIn', $this->t('Connect'), TRUE),
        ] + $buttons;
      }
      $section['actions'] = SettingsButton::group($buttons);
      return $section;
    }
    if (!ConnectionFields::addressIsSet($connection)) {
      $section['fields'] = $this->addressOnly($connection);
      $section['actions'] = SettingsButton::group([
        'continue_address' => SettingsButton::make('continue_address', 'saveAddress', $this->t('Continue'), TRUE, [['base_url']]),
      ]);
      return $section;
    }
    $section['lead'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#weight' => -5,
      '#value' => $this->t('Add the client ID and secret from Piknik.'),
    ];
    $section['fields'] = $this->credentials($connection);
    $buttons = [
      'save' => SettingsButton::make('save', 'saveCredentials', $this->t('Save'), TRUE),
    ];
    if ($editing && $ready) {
      $buttons['cancel_connection'] = SettingsButton::make('cancel_connection', 'closeConnection', $this->t('Cancel'), FALSE);
    }
    $section['actions'] = SettingsButton::group($buttons);
    return $section;
  }

  /**
   * @return array<string, mixed>
   */
  private function summary(Connection $connection): array {
    $address = $connection->baseUrl('piknik');
    return [
      '#type' => 'inline_template',
      '#template' => '<dl class="farm-piknik-summary">{% for row in rows %}<div class="farm-piknik-summary__row"><dt>{{ row.label }}</dt><dd>{{ row.value }}</dd></div>{% endfor %}</dl>',
      '#context' => [
        'rows' => [
          ['label' => $this->t('Piknik address'), 'value' => $address !== '' ? $address : ConnectionFields::ADDRESS],
          ['label' => $this->t('Client ID'), 'value' => $connection->clientId('piknik')],
          ['label' => $this->t('Client secret'), 'value' => $this->t('Saved')],
        ],
      ],
    ];
  }

  /**
   * @return array<string, mixed>
   */
  private function addressOnly(Connection $connection): array {
    $fields = ConnectionFields::elements($connection);
    unset($fields['client_id'], $fields['client_secret'], $fields['callback']);
    if (isset($fields['base_url'])) {
      $fields['base_url']['#description'] = $this->t('The Piknik site this farm uses.');
    }
    return $fields;
  }

  /**
   * @return array<string, mixed>
   */
  private function credentials(Connection $connection): array {
    $fields = ConnectionFields::elements($connection);
    $callback = $fields['callback'];
    unset($fields['callback']);
    $fields['advanced'] = [
      '#type' => 'details',
      '#title' => $this->t('Advanced'),
      '#open' => FALSE,
      '#weight' => 60,
      'callback' => $callback,
    ];
    return $fields;
  }

  /**
   * @return array<string, mixed>
   */
  private function heading(\Stringable $label): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $label,
      '#attributes' => ['class' => ['farm-piknik-section']],
    ];
  }

}
