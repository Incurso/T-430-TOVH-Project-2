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
class MusicSearchArtistForm extends FormBase {
  /**
   * The Music Artist Service
   *
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $service;

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
    return 'music_search_artist_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    $session = $request->getSession();
    $discogs_id = $request->query->get('discogs_id');
    $spotify_id = $request->query->get('spotify_id');

    $artist = $this->service->getArtist($spotify_id, $discogs_id);

    $query = $session->get('search_query');
    $types = $session->get('search_types');

    $form['#method'] = 'post';
    # $form['#action'] = Url::fromRoute('music_search.search')->toString();

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('Please provide the artist name'),
      # '#default_value' => $artist['name']
    ];

    $form['discogs_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs_ID'),
      '#description' => $this->t('Please provide the discogs id'),
      '#default_value' => $discogs_id
    ];

    $form['spotify_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify_ID'),
      '#description' => $this->t('Please provide the spotify id'),
      '#default_value' => $spotify_id
    ];

    /*
    $photo[] = $this->service->saveFile(
      $artist['images'][0],
      'artist_images',
      'image',
      $artist['name'] . ' - photo',
      $artist['name'] . '_band_photo.jpg'
    );
    */

    /*
    $form['images'] = array(
      '#type' => 'checkbox',
      '#title' => '<img src="' . $artist['images'][1]['url'] .'">',
      '#description' => $this->t('Do you want to add the image'),
    );
    */

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#name' => ''
    ];

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
 /*
    $request = \Drupal::request();
    $id = $request->query->get('id');

    $artist = $this->service->getArtist($id);

    $test = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $test
      ->condition('type', 'artist')
      ->condition('title', $artist['name'])
      ->condition('status', TRUE);
    $ids = $test->execute();

    if ($ids) {
      $asdf = \Drupal::entityTypeManager()->getStorage('node')->load(array_pop($ids));
    }

    $values = [
      'type' => 'artist',
      'title' => $artist['name'],
      'status' => TRUE,
    ];
    $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
    $node->save();
 */
 }


}
