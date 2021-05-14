<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configuration form definition for the salutation message
 */
class DiscogsSearchConfigurationForm extends ConfigFormBase {
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

    $form['discogs_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('discogs Client ID'),
      '#description' => $this->t('Please provide the discogs Client ID'),
      '#default_value' => $config->get('discogs_client_id')
    ];

    $form['discogs_client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('discogs Client Secret'),
      '#description' => $this->t('Please provide the discogs Client Secret'),
      '#default_value' => $config->get('discogs_client_secret')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $client_id = $form_state->getValue('discogs_client_id');
    if(strlen($client_id) > 32) {
      $form_state->setErrorByName('discogs_client_id', $this->t('The discogs Client ID is to long'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('music_search_configuration.settings')
      ->set('discogs_client_id', $form_state->getValue('discogs_client_id'))
      ->set('discogs_client_secret', $form_state->getValue('discogs_client_secret'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
