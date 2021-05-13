<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * search form for the main music search
 */
class MusicSearchForm extends ConfigFormBase {
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'music_search.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'music_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('music_search.settings');

    $form['artist_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Artist'),
      '#description' => $this->t('Please provide the artist name'),
      '#default_value' => $config->get('artist_name')
    ];

    $form['album_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Album'),
      '#description' => $this->t('Please provide the album name'),
      '#default_value' => $config->get('album_name')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $client_id = $form_state->getValue('artist_name');
    if(strlen($client_id) > 32) {
      $form_state->setErrorByName('artist_name', $this->t('The artist name is to long'));
    }

    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('music_search.settings')
      ->set('artist_name', $form_state->getValue('artist_name'))
      ->set('album_name', $form_state->getValue('album_name'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}