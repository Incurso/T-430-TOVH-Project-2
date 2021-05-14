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
   * add album attributes in add album form
   */
  public function addSongsForm(FormStateInterface $form_state) {
    /**
     * add songs in add album form
     */
    $attributes = array(
      array( 'track_name' => 'lag1', 'track_length' => '3:54', 'uid' => 1),
      array( 'track_name' => 'lag2', 'track_length' => '2:22', 'uid' => 2),
      array( 'track_name' => 'lag3', 'track_length' => '5:30', 'uid' => 3),
    );

    $header = array(
      'track_name' => t('track Name'),
      'track_length' => t('track length'),
    );
    $options = array();

    foreach ($attributes as $attribute) {
      $options[$attribute['uid']] = array(
        'track_name' => $attribute['track_name'],
        'track_length' => $attribute['track_length'],
      );
    }
    $form['table'] = array(
      '#caption' => $this->t('Available Songs'),
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
     * add album attributes in add album form
     */
  public function addAlbumAttributes(FormStateInterface $formState){
    $attributes = array(
      array('name' => 'Genre (spotify)', 'description' => 'Rock', 'uid' => 1),
      array('name' => 'Label (spotify)', 'description' => 'Elektra', 'uid' => 2),
      array('name' => 'Label (discogs)', 'description' => 'Virgin', 'uid' => 3),
      array('name' => 'Release Date', 'description' => '1990-01-01', 'uid' => 4),
    );
    $options = array();

    $header = array(
      'name' => t('name'),
      'description' => t('description'),
    );

    foreach ($attributes as $attribute) {
      $options[$attribute['uid']] = array(
        'name' => $attribute['name'],
        'description' => $attribute['description'],
      );
    }
    $form['table'] = array(
      '#caption' => $this->t('Add items from services'),
      '#empty' => t('No users found'),
      '#header' => $header,
      '#type' => 'tableselect',
      '#options' => $options,
    );
    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Add Attributes'),
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



    return [
      $form,
      $this->addAlbumAttributes($form_state),
      $this->addSongsForm($form_state)
    ]; # parent::buildForm($form, $form_state);
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
