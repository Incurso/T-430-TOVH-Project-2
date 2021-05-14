<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * search form for the main music search
 */
class MusicSearchForm extends FormBase {
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
    $request = \Drupal::request();
    $session = $request->getSession();

    $query = $session->get('search_query');
    $types = $session->get('search_types');

    $form['#method'] = 'post';
    # $form['#action'] = Url::fromRoute('music_search.search')->toString();

    $form['q'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search'),
      '#description' => $this->t('Please provide the artist name'),
      '#default_value' => $query ? $query : 'Metallica'
    ];

    $form['types'] = array(
      '#type' => 'radios',
      '#title' => $this->t('Types'),
      '#options' => array(
        'album' => $this->t('Albums'),
        'artist' => $this->t('Artist'),
      ),
      '#default_value' => $types ? $types : 'artist'
    );

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#name' => ''
    ];

    return $form; # parent::buildForm($form, $form_state);
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
    /*
    $this->config('music_search.settings')
      ->set('artist_name', $form_state->getValue('artist_name'))
      ->set('album_name', $form_state->getValue('album_name'))
      ->save();
    */

    # parent::submitForm($form, $form_state);
  }
}
