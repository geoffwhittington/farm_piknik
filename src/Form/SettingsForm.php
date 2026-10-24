<?php

declare(strict_types=1);

namespace Drupal\farm_piknik\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\farm_oauth_client\Connection;
use Drupal\farm_oauth_client\OAuthFlow;
use Drupal\farm_oauth_client\SecretBox;
use Drupal\farm_piknik\ConnectionFields;
use Drupal\farm_piknik\PlaceDirectory;
use Drupal\farm_piknik\ConnectionProbe;
use Drupal\farm_piknik\SettingsPage;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * OAuth client credentials, connect / disconnect, and selected place.
 */
final class SettingsForm extends FormBase {

  public function __construct(
    private StateInterface $state,
    private OAuthFlow $flow,
    private Connection $connection,
    private SecretBox $secrets,
    private PlaceDirectory $places,
    private ConnectionProbe $probe,
  ) {}

  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('state'),
      $container->get('farm_oauth_client.flow'),
      $container->get('farm_oauth_client.connection'),
      $container->get('farm_oauth_client.secrets'),
      $container->get('farm_piknik.places'),
      $container->get('farm_piknik.connection_probe'),
    );
  }

  public function getFormId(): string {
    return 'farm_piknik_settings';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attached']['library'][] = 'farm_piknik/wordmark';
    $form['#attached']['library'][] = 'farm_piknik/settings';
    $form['#attributes']['class'][] = 'farm-piknik-settings';
    $tested = $form_state->get('connection_test');
    $form += (new SettingsPage())->build(
      $this->flow->connected('piknik'),
      (bool) $form_state->get('connection_open'),
      $this->connection,
      is_string($tested) ? $tested : '',
    );
    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $name = (string) ($form_state->getTriggeringElement()['#name'] ?? '');
    if ($name === 'continue_address') {
      ConnectionFields::validateAddress($form_state);
      return;
    }
    if ($name !== 'save') {
      return;
    }
    ConnectionFields::validate($form_state, $this->connection, $this->secrets);
  }

  public function submitForm(array &$form, FormStateInterface $form_state): void {}

  public function saveAddress(array &$form, FormStateInterface $form_state): void {
    ConnectionFields::storeAddress($form_state, $this->connection);
    $form_state->setRebuild(TRUE);
    $this->messenger()->addStatus($this->t('Piknik address saved.'));
  }

  public function saveCredentials(array &$form, FormStateInterface $form_state): void {
    ConnectionFields::store($form_state, $this->connection);
    $form_state->set('connection_open', FALSE);
    $form_state->setRebuild(TRUE);
    $this->messenger()->addStatus($this->t('Sign-in details saved.'));
  }

  public function openConnection(array &$form, FormStateInterface $form_state): void {
    $form_state->set('connection_open', TRUE);
    $form_state->set('place_open', FALSE);
    $form_state->setRebuild(TRUE);
  }

  public function closeConnection(array &$form, FormStateInterface $form_state): void {
    $form_state->set('connection_open', FALSE);
    $form_state->setRebuild(TRUE);
  }

  public function openPlace(array &$form, FormStateInterface $form_state): void {
    $form_state->set('place_open', TRUE);
    $form_state->set('connection_open', FALSE);
    $form_state->setRebuild(TRUE);
  }

  public function closePlace(array &$form, FormStateInterface $form_state): void {
    $form_state->set('place_open', FALSE);
    $form_state->setRebuild(TRUE);
  }

  public function savePlace(array &$form, FormStateInterface $form_state): void {
    $this->storePlace($form_state);
    $form_state->set('place_open', FALSE);
    $form_state->setRebuild(TRUE);
    $this->messenger()->addStatus($this->t('Place saved.'));
  }

  public function signIn(array &$form, FormStateInterface $form_state): void {
    if ($form_state->hasValue('client_id')) {
      ConnectionFields::store($form_state, $this->connection);
    }
    $url = Url::fromRoute('farm_oauth_client.connect', ['provider' => 'piknik']);
    $url->setOption('query', ['token' => \Drupal::csrfToken()->get($url->getInternalPath())]);
    $form_state->setRedirectUrl($url);
  }

  public function testConnection(array &$form, FormStateInterface $form_state): void {
    $ok = FALSE;
    try {
      $ok = $this->probe->credentialsWork();
    }
    catch (\Throwable) {
      $ok = FALSE;
    }
    $form_state->set('connection_test', $ok ? 'ok' : 'fail');
    $form_state->setRebuild(TRUE);
  }

  public function disconnect(array &$form, FormStateInterface $form_state): void {
    $this->flow->disconnect('piknik');
    $this->state->set('farm_piknik.opt_in', FALSE);
    $this->messenger()->addStatus($this->t('This farm is disconnected from Piknik. Harvest sharing is off.'));
  }

  private function storePlace(FormStateInterface $form_state): void {
    if (!$this->flow->connected('piknik')) {
      return;
    }
    $place = (string) $form_state->getValue('participant_id');
    if ($place === '') {
      $this->state->delete('farm_piknik.participant_id');
      return;
    }
    if (preg_match('/^[A-Za-z0-9_-]{8,40}$/', $place)) {
      $this->state->set('farm_piknik.participant_id', $place);
    }
  }

}
