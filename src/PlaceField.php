<?php

declare(strict_types=1);

namespace Drupal\farm_piknik;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Place card on the board: name, emoji, description, public link, change.
 */
final class PlaceField {

  use StringTranslationTrait;

  /**
   * @return array<string, mixed>
   */
  public function card(PlaceDirectory $places, StateInterface $state, MessengerInterface $messenger, bool $open): array {
    $saved = $state->get('farm_piknik.participant_id', '');
    $saved = is_string($saved) ? $saved : '';
    $section = [
      '#type' => 'container',
      '#weight' => -90,
      '#attributes' => ['class' => ['farm-piknik-place-card']],
    ];
    if ($saved !== '' && !$open) {
      $section['summary'] = $this->summary($places, $saved);
      $section['actions'] = SettingsButton::group([
        'edit_place' => SettingsButton::make('edit_place', 'openPlace', $this->t('Change place'), FALSE),
      ]);
      return $section;
    }
    $section['participant_id'] = $this->select($places, $state, $messenger, $saved);
    $buttons = [
      'save_place' => SettingsButton::make('save_place', 'savePlace', $this->t('Save place'), TRUE, [['participant_id']]),
    ];
    if ($saved !== '') {
      $buttons['cancel_place'] = SettingsButton::make('cancel_place', 'closePlace', $this->t('Cancel'), FALSE);
    }
    $section['actions'] = SettingsButton::group($buttons);
    return $section;
  }

  /**
   * @return array<string, mixed>
   */
  private function summary(PlaceDirectory $places, string $saved): array {
    $profile = $places->profile($saved);
    $name = $profile['name'] !== '' ? $profile['name'] : (string) $this->t('Unnamed place');
    return [
      '#type' => 'inline_template',
      '#template' => '<div class="farm-piknik-place">{% if emoji %}<span class="farm-piknik-place__emoji" aria-hidden="true">{{ emoji }}</span> {% endif %}{% if url %}<a class="farm-piknik-place__name" href="{{ url }}" target="_blank" rel="noopener noreferrer">{{ name }}</a>{% else %}<span class="farm-piknik-place__name">{{ name }}</span>{% endif %}</div>{% if description %}<p class="farm-piknik-place__description">{{ description }}</p>{% endif %}',
      '#context' => [
        'emoji' => $profile['emoji'],
        'name' => $name,
        'description' => $profile['description'],
        'url' => $places->publicUrl($saved, $profile['type'], $profile['role']),
      ],
    ];
  }

  /**
   * @return array<string, mixed>
   */
  private function select(PlaceDirectory $places, StateInterface $state, MessengerInterface $messenger, string $saved): array {
    try {
      $choices = $places->choices();
    }
    catch (\Throwable $e) {
      $messenger->addWarning($e->getMessage());
      $choices = [];
    }
    return [
      '#type' => 'select',
      '#title' => $this->t('Place'),
      '#options' => $choices,
      '#empty_option' => $this->t('- Select -'),
      '#default_value' => isset($choices[$saved]) ? $saved : NULL,
      '#description' => $this->t('Choose the place shoppers should see.'),
    ];
  }

}
