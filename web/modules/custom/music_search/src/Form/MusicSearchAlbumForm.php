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
class MusicSearchAlbumForm extends FormBase {
  /**
   * The Music Search Album Service
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
    return 'music_search_album_form';
  }

  /**
   * {@inheritdoc}
   */
  public function addSongsForm(FormStateInterface $form_state) {
    /**
     * add songs in add album form
     */
    $songs = array(
      array( 'track_name' => 'Indy', 'track_length' => 'Jones', 'uid' => 1),
      array( 'track_name' => 'Darth', 'track_length' => 'Vader', 'uid' => 2),
      array( 'track_name' => 'Super', 'track_length' => 'Man', 'uid' => 3),
    );

    $header = array(
      'track_name' => t('track Name'),
      'track_length' => t('track length'),
    );
    $options = array();

    foreach ($songs as $song) {
      $options[$song['uid']] = array(
        'track_name' => $song['track_name'],
        'track_length' => $song['track_length'],
      );
    }
    $form['table'] = array(
      '#header' => $header,
      '#empty' => t('No users found'),
      '#type' => 'tableselect',
      '#options' => $options,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add Songs'),
    );

    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();
    $session = $request->getSession();
    $id = $request->query->get('id');

    /**
     * Get albums from the web api (discogs or spotify)
     */
    $album = $this->service->getAlbum($id);

    $query = $session->get('search_query');
    $types = $session->get('search_types');

    $form['#method'] = 'post';
    //$form['#action'] = Url::fromRoute('music_search.search')->toString();

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('Please provide the album name'),
      '#default_value' => $album['name']
    ];

    $form['spotifyid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('spotify_id'),
      '#description' => $this->t('Please provide the spotify id'),
      '#default_value' => $album['id']
    ];

    $form['discogsid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('discogs_id'),
      '#description' => $this->t('Please provide the discogs id'),
      '#default_value' => $album['id']
    ];

    $form['genre'] = [
      '#type' => 'textfield',
      '#title' => $this->t('genre'),
      '#description' => $this->t('Please provide the genre'),
      '#default_value' => 'Rock and roll'
    ];

    $form['releasedate'] = [
      '#type' => 'textfield',
      '#title' => $this->t('release date'),
      '#description' => $this->t('Please provide the release date'),
      '#default_value' => '1991'
    ];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('label'),
      '#description' => $this->t('Please provide the record label'),
      '#default_value' => 'Elektra'
    ];


/*
    $photo[] = $this->service->_save_file(
      $album['images'][0],
      'artist_images',
      'image',
      $album['name'] . ' - photo',
      $album['name'] . '_album_photo.jpg'
    );
*/

    $form['images'] = array(
      '#type' => 'checkbox',
      '#title' => '<img src="' . $album['images'][1]['url'] .'">',
      '#description' => $this->t('Do you want to add the image'),
    );

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#name' => ''
    ];





    return array($form, $this->addSongsForm($form_state)); # parent::buildForm($form, $form_state);
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

    $album = $this->service->getArtist($id);

    $test = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $test
      ->condition('type', 'artist')
      ->condition('title', $album['name'])
      ->condition('status', TRUE);
    $ids = $test->execute();
    $asdf = \Drupal::entityTypeManager()->getStorage('node')->load(array_pop($ids));

    $values = [
      'type' => 'artist',
      'title' => $album['name'],
      'status' => TRUE,
    ];
    $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
    $node->save();
 */
 }




}
