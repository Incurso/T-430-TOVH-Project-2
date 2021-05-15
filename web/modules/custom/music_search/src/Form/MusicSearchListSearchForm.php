<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * search form for the main music search
 */
class MusicSearchListSearchForm extends FormBase {
  protected $service;
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'music_search.settings',
    ];
  }


  public function __construct(MusicSearchService $service) {
    $this->service = $service;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('music_search.service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'music_search_list_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    $session = $request->getSession();

    $searchResults = null;

    $query = $session->get('search_query');
    $types = $session->get('search_types');

    if ($query && $types) {
      $searchResults = $this->service->search($query, $types);
    }

    $form['spotify_id'] = $searchResults ? reset($searchResults['spotify']) : null;
    $form['discogs_id'] = $searchResults ? reset($searchResults['discogs']) : null;

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Add'),
      '#button_type' => 'primary',
    );

    return $form;
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
    $request = \Drupal::request();
    $session = $request->getSession();

    $searchResults = null;

    $types = $session->get('search_types');

    $userInput = $form_state->getUserInput();
    $parameters = array();
    if (array_key_exists('spotify_id', $userInput)) {
      $parameters['spotify_id'] = $userInput['spotify_id'];
    }
    if (array_key_exists('discogs_id', $userInput)) {
      $parameters['discogs_id'] = $userInput['discogs_id'];
    }

    $form_state->setRedirect('music_search.'. $types, $parameters);
  }
}
