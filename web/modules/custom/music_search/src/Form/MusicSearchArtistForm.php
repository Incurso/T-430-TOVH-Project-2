<?php

namespace Drupal\music_search\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class MusicSearchArtistForm
 * search form for the main music search
 * @package Drupal\music_search\Form
 */
class MusicSearchArtistForm extends FormBase {
  /**
   * The Music Artist Service
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $service;

  /**
   * Function getArtistTableSelect
   * to generate a table of artists with selectable rows
   * @param $field passes in the fields for the table
   * @param $required passes in weater or not the selection is required
   * @param $multi passes in weather or not it is multi select
   * @return array returns an artist table
   */
  private function getArtistTableselect ($field, $required, $multi) {
    return [
      '#type' => 'tableselect',
      '#caption' => $this->t(ucfirst($field) . ($multi ? 's' : '')), # Add s if we are using multiselect
      '#required' => $required,
      '#multiple' => $multi,
      '#header' => [
        'source' => $this->t('Source'),
        $field => $this->t(ucfirst($field)),
      ],
      '#options' => [],
    ];
  }

  /**
   * MusicSearchArtistForm constructor.
   * @param MusicSearchService $service
   */
  public function __construct(MusicSearchService $service) {
    // adds the music service to a local var
    $this->service = $service;
  }

  /**
   * Create container function
   * Dependency Injection of the music_search services
   * @param ContainerInterface $container
   * @return MusicSearchArtistForm|static
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('music_search.service')
    );
  }

  /**
   *  Function getFormId
   * {@inheritdoc}
   * @return string return the form id
   */
  public function getFormId() {
    return 'music_search_artist_form';
  }

  /**
   * Function buildForm
   * to build the Artist form
   * @param array $form passes in the form data
   * @param FormStateInterface $form_state passes in the form state
   * @return array returns the form
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = \Drupal::request();

    // Queries for the id's from the music api's
    $discogs_id = $request->query->get('discogs_id');
    $spotify_id = $request->query->get('spotify_id');

    // Queries the music api's for the artist's
    $artist = $this->service->getArtist($spotify_id, $discogs_id);

    // Form discogs id field
    $form['discogs_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Discogs_ID'),
      '#default_value' => $discogs_id,
      '#attributes' => [
        'disabled' => 'disabled',
      ],
    ];

    // Form spotify id field
    $form['spotify_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Spotify_ID'),
      '#default_value' => $spotify_id,
      '#attributes' => [
        'disabled' => 'disabled',
      ],
    ];

    // Generate selectable tables
    $form['name'] = $this->getArtistTableselect('name', TRUE, FALSE);
    $form['website'] = $this->getArtistTableselect('website', FALSE, FALSE);
    $form['description'] = $this->getArtistTableselect('description', FALSE, FALSE);
    $form['images'] = $this->getArtistTableselect('image', FALSE, TRUE);

    // Populate selectable tables with values
    foreach ($artist as $serviceName => $service) {
      $form['name']['#options'][$serviceName] = ['source' => $serviceName, 'name' => $service['name']];
      $form['website']['#options'][$serviceName] = ['source' => $serviceName, 'website' => $service['website']];
      $form['description']['#options'][$serviceName] = ['source' => $serviceName, 'description' => $service['description']];

      foreach ($service['images'] as $image) {
        $form['images']['#options'][$image['url']] = [
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

    // Form submit button
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
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Get's the artist name
    $client_id = $form_state->getValue('artist_name');

    // If the artist name is longer than 32
    // throw an error
    if(strlen($client_id) > 32) {
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
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $request = \Drupal::request();

    // Vars to store the artists by artist id's from api's
    $artistByDiscogsID = [];
    $artistBySpotifyID = [];

    // Get id's from the api's
    $discogs_id = $request->query->get('discogs_id');
    $spotify_id = $request->query->get('spotify_id');

    // Get's the artists from each music api
    $artist = $this->service->getArtist($spotify_id, $discogs_id);

    // Get's the user input from the form
    $formValues = $form_state->getUserInput();
    // Define the values which we want to submit to the db
    $values = [
      'type' => 'artist',
      'status' => TRUE,
      'title' => $artist[$formValues['name']]['name'],
      'field_discogs_id' => $discogs_id,
      'field_spotify_id' => $spotify_id,
      'field_desc' => $artist[$formValues['description']]['description'],
      'field_website' => $artist[$formValues['website']]['website'],
    ];

    // Define entityTypeManager so we can look for entities
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();

    // If there exists an id from each service in the database
    // then get the id's from the db
    if ($discogs_id) {
      $query
        ->condition('type', 'artist')
        ->condition('field_discogs_id', $discogs_id);

      $artistByDiscogsID = $query->execute();
    }

    if ($spotify_id) {
      $query
        ->condition('type', 'artist')
        ->condition('field_spotify_id', $spotify_id);

      $artistBySpotifyID = $query->execute();
    }

    // Concat and get unique entity IDs
    $artistID = array_unique(array_merge($artistByDiscogsID, $artistBySpotifyID));

    // If there is only one artist
    // then loop through the images and store them
    // and returns the id of the entity to the db
    if (sizeof($artistID) <= 1) {
      $images = [];
      foreach ($formValues['images'] as $image){
        if ($image) {
          array_push($images, [
            'target_id' => $this->service->saveFile(
              $image,
              'artist_images',
              'image',
              $values['title'],
              basename($image),
              basename($image)
            )
          ]);
        }
      }

      $values['field_photos'] = $images;

      // If there already exists an entity
      // then we update it
      if (sizeof($artistID)) {
        // We found an entity so we update it
        $entity = \Drupal::entityTypeManager()->getStorage('node')->load(reset($artistID));
        foreach ($values as $key => $value) {
          $entity->$key = $value;
        }
        $entity->save();
        $id = $entity->id();
      } else {
        // We found no entity so we create it
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
