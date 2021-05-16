<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MusicSearchAlbumForm
 * @package Drupal\music_search\Form
 */
class MusicSearchAlbumForm extends FormBase
{
  /**
   * The Music Search Album Service
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $service;

  /**
   * Function getAlbumTableSelect
   * to generate a table of albums with selectable rows
   * @param $field passes in the fields for the table
   * @param $required passes in weather or not the selection is required
   * @param $multi passes in weather or not it is multi select
   * @return array returns an album table
   */
  private function getAlbumTableselect($caption, $fields, $required, $multi)
  {
    $header = [];
    foreach ($fields as $field) {
      $header[$field] = $this->t(ucfirst($field));
    }

    return [
      '#type' => 'tableselect',
      '#caption' => $this->t(ucfirst($caption)),
      '#required' => $required,
      '#multiple' => $multi,
      '#header' => $header,
      '#options' => [],
    ];
  }

  /**
   * MusicSearchAlbumForm constructor.
   * @param MusicSearchService $service
   */
  public function __construct(MusicSearchService $service)
  {
    $this->service = $service;
  }

  /**
   * Create container function
   * Dependency Injection of the music_search services
   * @param ContainerInterface $container
   * @return MusicSearchAlbumForm|static
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('music_search.service')
    );
  }

  /**
   * Function getFormId
   * {@inheritdoc}
   * @return string return the form id
   */
  public function getFormId()
  {
    return 'music_search_album_form';
  }

  /**
   * Function buildForm
   * to build the Album form
   * @param array $form passes in the form data
   * @param FormStateInterface $form_state passes in the form state
   * @return array returns the form
   */
  public function buildForm(array $form, FormStateInterface $form_state)
  {
    $request = \Drupal::request();
    $discogs_id = $request->query->get('discogs_id');
    $spotify_id = $request->query->get('spotify_id');

    // Get albums from the web api (discogs or spotify)
    $album = $this->service->getAlbum($spotify_id, $discogs_id);

    $form['spotify_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('spotify_id'),
      '#description' => $this->t('Please provide the spotify id'),
      '#default_value' => $spotify_id,
      '#attributes' => [
        'disabled' => 'disabled',
      ],
    ];

    $form['discogs_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('discogs_id'),
      '#description' => $this->t('Please provide the discogs id'),
      '#default_value' => $discogs_id,
      '#attributes' => [
        'disabled' => 'disabled',
      ],
    ];

    // Generate tableselects
    $form['title'] = $this->getAlbumTableselect('title', ['source', 'title'], TRUE, FALSE);
    $form['released'] = $this->getAlbumTableselect('Released', ['source', 'year'], FALSE, FALSE);
    $form['label'] = $this->getAlbumTableselect('label', ['source', 'label'], FALSE, FALSE);
    $form['genres'] = $this->getAlbumTableselect('genres', ['source', 'genre'], FALSE, TRUE);
    $form['description'] = $this->getAlbumTableselect('description', ['source', 'description'], FALSE, FALSE);
    $form['discogs_tracks'] = $this->getAlbumTableselect('Discogs Tracks', ['number', 'title', 'duration'], FALSE, TRUE);
    $form['spotify_tracks'] = $this->getAlbumTableselect('Spotify Tracks', ['number', 'title', 'duration'], FALSE, TRUE);
    $form['cover'] = $this->getAlbumTableselect('images', ['source', 'image'], FALSE, FALSE);

    // Populate tableselects with values
    foreach ($album as $serviceName => $service) {
      $form['title']['#options'][$serviceName] = ['source' => $serviceName, 'title' => $service['title']];
      $form['released']['#options'][$serviceName] = ['source' => $serviceName, 'year' => $service['year']];
      $form['label']['#options'][$serviceName] = ['source' => $serviceName, 'label' => $service['label']];
      $form['description']['#options'][$serviceName] = ['source' => $serviceName, 'description' => $service['description']];

      // Add genres
      foreach ($service['genres'] as $genre) {
        $form['genres']['#options'][$genre] = [
          'source' => $serviceName,
          'genre' => $genre,
        ];
      }

      // Add tracks
      foreach ($service['tracks'] as $track) {
        $form[$serviceName . '_tracks']['#options'][$track['id']] = [
          'number' => $track['position'],
          'title' => $track['title'],
          'duration' => $track['duration']
        ];
      }

      // Add images
      foreach ($service['images'] as $image) {
        $form['cover']['#options'][$image['url']] = [
          'source' => $serviceName,
          'image' => [
            'data' => [
              '#theme' => 'image',
              '#width' => '150',
              '#uri' => $image['url']
            ]
          ]
        ];
      }
    }

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#name' => ''
    ];

    return $form;
  }

  /**
   * Function validateForm
   * @param array $form passes in the form data
   * @param FormStateInterface $form_state passes in the form state
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $client_id = $form_state->getValue('artist_name');
    if (strlen($client_id) > 32) {
      $form_state->setErrorByName('artist_name', $this->t('The artist name is to long'));
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
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $request = \Drupal::request();
    $albumByDiscogsID = [];
    $albumBySpotifyID = [];

    $discogs_id = $request->query->get('discogs_id');
    $spotify_id = $request->query->get('spotify_id');

    $test = \Drupal::entityTypeManager()->getStorage('node')->load(33);
    $album = $this->service->getAlbum($spotify_id, $discogs_id);

    $formValues = $form_state->getUserInput();
    $values = [
      'type' => 'album',
      'status' => TRUE,
      'title' => $album[$formValues['title']]['title'],
      'field_discogs_id' => $discogs_id,
      'field_spotify_id' => $spotify_id,
      'field_desc' => $album[$formValues['description']]['description'],
      'field_released' => $album[$formValues['released']]['year'],
    ];

    // Define entityTypeManager so we can look for entities
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();

    if ($discogs_id) {
      $query
        ->condition('type', 'album')
        ->condition('field_discogs_id', $discogs_id);

      $albumByDiscogsID = $query->execute();
    }

    if ($spotify_id) {
      $query
        ->condition('type', 'album')
        ->condition('field_spotify_id', $spotify_id);

      $albumBySpotifyID = $query->execute();
    }

    // Concat and get unique entity IDs
    $albumID = array_unique(array_merge($albumByDiscogsID, $albumBySpotifyID));

    if (sizeof($albumID) <= 1) {
      $tracks = [];
      foreach($album as $serviceKey => $service) {
        foreach ($service['tracks'] as $track) {
          foreach (array_merge($formValues['discogs_tracks'], $formValues['spotify_tracks']) as $trackID) {
            if ($track['id'] == $trackID) {
              $query
                ->condition('type', 'song')
                ->condition('field_'. $serviceKey .'_id');
              $id = $query->execute();

              if (sizeof($id) <= 1) {
                if (sizeof($id)) {
                  // TODO: update track
                  array_push($tracks, ['target_id' => $id]);
                } else {
                  // TODO: add track
                  $trackValues = [
                    'type' => 'song',
                    'status' => TRUE,
                    'title' => $track['title'],
                    'field_duration' => $track['duration'],
                    'field_position' => $track['position'],
                    'field_'. $serviceKey .'_id' => $track['id']
                  ];
                  $node = \Drupal::entityTypeManager()->getStorage('node')->create($trackValues);
                  $node->save();

                  array_push($tracks, ['target_id' => $node->id()]);
                }
              } else {
                // TODO: throw error
              }
            }
          }
        }
      }

      $values['field_songs'] = $tracks;

      $images = [
        'target_id' => $this->service->saveFile(
          $formValues['cover'],
          'album_images',
          'image',
          $values['title'],
          basename($formValues['cover']),
          basename($formValues['cover'])
        )
      ];

      $values['field_cover'] = $images;

      // We found either one or zero entities
      if (sizeof($albumID)) {
        // We found an entity so we update it
        // TODO: update album
        $entity = \Drupal::entityTypeManager()->getStorage('node')->load(reset($albumID));
        foreach ($values as $key => $value) {
          $entity->$key = $value;
        }
        $entity->save();
        $id = $entity->id();
      } else {
        // We found no entity so we create it
        // TODO: save album
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();
        $id = $node->id();
      }
    } else {
      // We found multiple entities
      // TODO: throw error
    }
  }
}
