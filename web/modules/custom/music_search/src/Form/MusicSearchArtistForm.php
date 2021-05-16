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

  private function imageCheckboxes ($service_name, $artist) {
    if (!array_key_exists('images', $artist)) {
      return null;
    }

    $images = array();
    foreach ($artist['images'] as $image) {
      $images[$image['url']] = '<img width="150" height="auto" src="'. $image['url'] .'" />';
    }

    return array(
      '#type' => 'checkboxes',
      '#title' => $this->t(ucfirst($service_name) .' Images'),
      '#options' => $images,
    );
  }

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

    $discogs_id = $request->query->get('discogs_id');
    $spotify_id = $request->query->get('spotify_id');

    $artist = $this->service->getArtist($spotify_id, $discogs_id);

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Name'),
      '#description' => $this->t('Please provide the artist name'),
      '#default_value' => array_key_exists('spotify', $artist) ? $artist['spotify']['name'] : $artist['discogs']['name'],
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
     * Generate tableselects
     */
    $form['name'] = $this->getArtistTableselect('name', TRUE, FALSE);
    $form['website'] = $this->getArtistTableselect('website', FALSE, FALSE);
    $form['description'] = $this->getArtistTableselect('description', FALSE, FALSE);
    $form['images'] = $this->getArtistTableselect('image', FALSE, TRUE);

    /*
     * Populate tableselects with values
     */
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

      /*
      $form[$serviceName .'_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Name'),
        //'#description' => $this->t('Please provide the artist name'),
        '#default_value' => $service['name'],
      ];

      $form[$serviceName .'_website'] = [
        '#type' => 'textfield',
        '#title' => $this->t('website'),
        //'#description' => $this->t('Please provide the artist name'),
        '#default_value' => $service['website'],
      ];

      $form[$serviceName .'_description'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Description'),
        //'#description' => $this->t('Please provide the artist name'),
        '#default_value' => $service['description'],
      ];

      $form[$serviceName .'_images'] = $this->imageCheckboxes($serviceName, $service);
      */
    }

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
    $request = \Drupal::request();
    $artistByDiscogsID = [];
    $artistBySpotifyID = [];

    $discogs_id = $request->query->get('discogs_id');
    $spotify_id = $request->query->get('spotify_id');

    $artist = $this->service->getArtist($spotify_id, $discogs_id);

    $formValues = $form_state->getUserInput();
    $values = [
      'type' => 'artist',
      'status' => TRUE,
      'title' => $artist[$formValues['name']]['name'],
      'field_discogs_id' => $discogs_id,
      'field_spotify_id' => $spotify_id,
      'field_desc' => $artist[$formValues['description']]['description'],
      'field_website' => $artist[$formValues['website']]['website'],
    ];

    // Define entityTypeManager so we can look for entitys
    $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery();

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

    if (sizeof($artistID) <= 1) {
      // TODO: save images as media type
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

      // We found either one or zero entities
      if (sizeof($artistID)) {
        // We found an entity so we update it
        // TODO: update artist
        $entity = \Drupal::entityTypeManager()->getStorage('node')->load(reset($artistID));
        foreach ($values as $key => $value) {
          $entity->$key = $value;
        }
        $entity->save();
        $id = $entity->id();
      } else {
        // We found no entity so we create it
        // TODO: save artist
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
