<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form definition for the salutation message
 */
class MusicSearchConfigurationForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'music_search_configuration.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'music_search_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('music_search_configuration.settings');

    $form['discogs_consumer_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs Consumer Key'),
      '#description' => $this->t('Please provide the Discogs Consumer key'),
      '#default_value' => $config->get('discogs_consumer_key')
    ];

    $form['discogs_consumer_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs Consumer Secret'),
      '#description' => $this->t('Please provide the Discogs Consumer Secret'),
      '#default_value' => $config->get('discogs_consumer_secret')
    ];

    $form['spotify_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client ID'),
      '#description' => $this->t('Please provide the Spotify Client ID'),
      '#default_value' => $config->get('spotify_client_id')
    ];

    $form['spotify_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify Client Secret'),
      '#description' => $this->t('Please provide the Spotify Client Secret'),
      '#default_value' => $config->get('spotify_client_secret')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $client_id = $form_state->getValue('spotify_client_id');
    if(strlen($client_id) > 32) {
      $form_state->setErrorByName('spotify_client_id', $this->t('The Spotify Client ID is to long'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('music_search_configuration.settings')
      ->set('discogs_consumer_key', $form_state->getValue('discogs_consumer_key'))
      ->set('discogs_consumer_secret', $form_state->getValue('discogs_consumer_secret'))
      ->set('spotify_client_id', $form_state->getValue('spotify_client_id'))
      ->set('spotify_client_secret', $form_state->getValue('spotify_client_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
