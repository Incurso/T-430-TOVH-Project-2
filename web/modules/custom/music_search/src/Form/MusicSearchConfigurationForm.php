<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class MusicSearchConfigurationForm
 * @package Drupal\music_search\Form
 */
class MusicSearchConfigurationForm extends ConfigFormBase {
  /**
   * Function getEditableConfigNames
   * @return string[] returns the config names
   */
  protected function getEditableConfigNames() {
    return [
      'music_search_configuration.settings',
    ];
  }

  /**
   * Function getFormId
   * @return string returns name of the form
   */
  public function getFormId() {
    return 'music_search_configuration_form';
  }

  /**
   * Function buildForm
   * to build the configuration form
   * @param array $form passes in the form data
   * @param FormStateInterface $form_state passes in the form state
   * @return array returns the form
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
   * Function validateForm
   * @param array $form passes in the form data
   * @param FormStateInterface $form_state passes in the form state
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
   * Function submitForm
   * @param array $form passes in the form data
   * @param FormStateInterface $form_state passes in the form state
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
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
