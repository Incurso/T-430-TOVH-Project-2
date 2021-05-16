<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MusicSearchListSearchForm
 * @package Drupal\music_search\Form
 */
class MusicSearchListSearchForm extends FormBase {
  /**
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $service;

  /**
   * Function getEditableConfigNames
   * @return string[] returns the config names
   */
  protected function getEditableConfigNames() {
    return [
      'music_search.settings',
    ];
  }

  /**
   * MusicSearchListSearchForm constructor.
   * @param MusicSearchService $service
   */
  public function __construct(MusicSearchService $service) {
    $this->service = $service;
  }

  /**
   * @param ContainerInterface $container
   * @return MusicSearchListSearchForm|static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('music_search.service')
    );
  }

  /**
   * Function getFormId
   * @return string returns name of the form
   */
  public function getFormId() {
    return 'music_search_list_form';
  }

  /**
   * Function buildForm
   * to build the configuration form
   * @param array $form passes in the form data
   * @param FormStateInterface $form_state passes in the form state
   * @return array returns the form
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
   * Function validateForm
   * @param array $form passes in the form data
   * @param FormStateInterface $form_state passes in the form state
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $userInput = $form_state->getUserInput();
    if(!array_key_exists('spotify_id', $userInput) && !array_key_exists('discogs_id', $userInput)) {
      $form_state->setErrorByName('spotify', $this->t('Select at least one item before you try to add.'));
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
