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
class MusicSearchSongForm extends FormBase {
  /**
   * The Music Search Song Service
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
    $id = $request->query->get('id');

    /**
     * Get songs from the web api (discogs or spotify)
     */
    $song = $this->service->getAlbum($id);

    $query = $session->get('search_query');
    $types = $session->get('search_types');

    $form['#method'] = 'post';
    //$form['#action'] = Url::fromRoute('music_search.search')->toString();

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('Please provide the song name'),
      '#default_value' => $song['name']
    ];

    $form['id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('spotify_id'),
      '#description' => $this->t('Please provide the spotify id'),
      '#default_value' => $song['id']
    ];

    $photo[] = $this->service->_save_file(
      $song['images'][0],
      'artist_images',
      'image',
      $song['name'] . ' - photo',
      $song['name'] . '_album_photo.jpg'
    );


    $form['images'] = array(
      '#type' => 'checkbox',
      '#title' => '<img src="' . $song['images'][1]['url'] .'">',
      '#description' => $this->t('Do you want to add the image'),
    );


    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#name' => ''
    ];

    return $form; # parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $client_id = $form_state->getValue('song_name');
    if(strlen($client_id) > 32) {
      $form_state->setErrorByName('song_name', $this->t('The song name is to long'));
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

    $song = $this->service->getArtist($id);

    $test = \Drupal::entityTypeManager()->getStorage('node')->getQuery();
    $test
      ->condition('type', 'artist')
      ->condition('title', $song['name'])
      ->condition('status', TRUE);
    $ids = $test->execute();
    $asdf = \Drupal::entityTypeManager()->getStorage('node')->load(array_pop($ids));

    $values = [
      'type' => 'artist',
      'title' => $song['name'],
      'status' => TRUE,
    ];
    $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
    $node->save();
 */
 }




}
