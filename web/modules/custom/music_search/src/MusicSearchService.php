<?php

namespace Drupal\music_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class MusicSearchService
 * @package Drupal\music_search
 */
class MusicSearchService {

  use StringTranslationTrait;

  /**
   * The config factory.
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The session variable to keep the api tokens from music apis intact and refresh them when they've expired.
   */
  protected $session;

  /**
   * Local discogs Service variable to access the main MusicSearchDiscogs Service
   * @var \Drupal\music_search\MusicSearchDiscogsService
   */
  protected $discogsService;

  /**
   * Local spotify Service variable to access the main MusicSearchSpotify Service
   * @var \Drupal\music_search\MusicSearchSpotifyService
   */
  protected $spotifyService;

  /**
   * MusicSearchService constructor.
   * @param ConfigFactoryInterface $config_factory
   * the config factory
   * @param MusicSearchDiscogsService $discogs_service
   * import the discogs service
   * @param MusicSearchSpotifyService $spotify_service
   * import the spotify service
   */
  public function __construct(ConfigFactoryInterface $config_factory, MusicSearchDiscogsService $discogs_service, MusicSearchSpotifyService $spotify_service) {
    // Generate the requests, session, config factory and api services
    $requests = \Drupal::request();
    $this->session = $requests->getSession();
    $this->configFactory = $config_factory;
    $this->discogsService = $discogs_service;
    $this->spotifyService = $spotify_service;
  }

  /**
   * function search
   * To run the search queries against the two Music services
   * @param $query pass in the search query
   * @param $types pass in the types of search (album or artist)
   * @return array|array[] returns arrays of the data returned from the music services
   */
  public function search($query, $types) {
    // Vars to store the service response
    $serviceResponse['discogs'] = $this->discogsService->search($query, $types);
    $serviceResponse['spotify'] = $this->spotifyService->search($query, $types);

    // Var to store the response data
    $returnData = array(
      'discogs' => array(),
      'spotify' => array(),
    );


    // Runs through the service responses and generates the tables of data returned from the music API's
    foreach ($serviceResponse as $serviceKey => $serviceValue) {
      foreach ($serviceValue as $typeKey => $typeValue) {
        switch ($typeKey) {
          case 'albums':
            if (!array_key_exists($typeKey, $returnData[$serviceKey])) {
              $returnData[$serviceKey][$typeKey] = array(
                '#type' => 'tableselect',
                '#caption' => ucfirst($serviceKey) .' Albums',
                '#header' => [
                  'thumbnail' => t('Album Cover'),
                  'title' => t('Title'),
                  'year' => t('Release date'),
                ],
                '#multiple' => FALSE,
                '#options' => array()
              );
            }

            foreach ($typeValue as $item) {
              $returnData[$serviceKey][$typeKey]['#options'][$item['id']] = array(
                'thumbnail' => ['data' => ['#theme' => 'image', '#width' => 150, '#alt' => $item['thumbnail'], '#uri' => $item['thumbnail']]],
                'title' => ['data' => $item['title']],
                'year' => ['data' => $item['year']],
              );
            }
            break;
          case 'artists':
            if (!array_key_exists($typeKey, $returnData[$serviceKey])) {
              $returnData[$serviceKey][$typeKey] = array(
                '#type' => 'tableselect',
                '#caption' => ucfirst($serviceKey) .' Artists',
                '#header' => [
                  'thumbnail' => '',
                  'name' => t('Title'),
                ],
                '#multiple' => FALSE,
                '#options' => array()
              );
            }

            foreach ($typeValue as $item) {
              $returnData[$serviceKey][$typeKey]['#options'][$item['id']] = array(
                'thumbnail' => ['data' => ['#theme' => 'image', '#width' => 150, '#alt' => $item['thumbnail'], '#uri' => $item['thumbnail']]],
                'name' => ['data' => $item['title']],
              );
            }
            break;
        }
      }
    }
    return $returnData;
  }

  /**
   * function getArtist
   * Get's an artist from the id
   * @param null $spotify_id passes in the spotify id
   * @param null $discogs_id passes in the dicogs id
   * @return array returns the artist
   */
  public function getArtist($spotify_id = null, $discogs_id = null) {
    // Var to store the service response
    $serviceResponse = array();

    // Checks if there exists an id in either of the services or both
    if ($discogs_id) {
      $serviceResponse['discogs'] = $this->discogsService->getArtist($discogs_id);
    }
    if ($spotify_id) {
      $serviceResponse['spotify'] = $this->spotifyService->getArtist($spotify_id);
    }

    return $serviceResponse;
  }

  /**
   * function getAlbum
   * Get's an album from the id
   * @param null $spotify_id passes in the spotify id
   * @param null $discogs_id passes in the dicogs id
   * @return array returns the artist
   */
  public function getAlbum($spotify_id = null, $discogs_id = null) {
    // Var to store the service response
    $serviceResponse = array();

    // Checks if there exists an id in either of the services or both
    if ($discogs_id) {
      $serviceResponse['discogs'] = $this->discogsService->getAlbum($discogs_id);
    }
    if ($spotify_id) {
      $serviceResponse['spotify'] = $this->spotifyService->getAlbum($spotify_id);
    }

    return $serviceResponse;
  }


  /**
   * function saveFile
   * Saves a file, based on it's type
   * @param $url
   *   Full path to the image on the internet
   * @param $folder
   *   The folder where the image is stored on your hard drive
   * @param $type
   *   Type should be 'image' at all time for images.
   * @param $title
   *   The title of the image (like ALBUM_NAME - Cover), as it will appear in the Media management system
   * @param $basename
   *   The name of the file, as it will be saved on your hard drive
   *
   * @return int|null|string
   * @throws EntityStorageException
   */
  function saveFile($url, $folder, $type, $title, $basename, $uid = 1) {
    $directory = \Drupal::config('system.file')->get('default_scheme').'://' . $folder;
    if(!is_dir($directory)) {
      // Create it if it doesn't exist
      \Drupal::service('file_system')->mkdir($directory);
    }
    $destination = \Drupal::config('system.file')->get('default_scheme').'://' . $folder . '/'.basename($basename);
    if(!file_exists($destination)) {
      $file = file_get_contents($url);
      $file = file_save_data($file, $destination);
    }
    else {
      $file = \Drupal\file\Entity\File::create([
        'uri' => $destination,
        'uid' => $uid,
        'status' => FILE_STATUS_PERMANENT
      ]);

      $file->save();
    }

    $file->status = 1;

    $media_type_field_name = 'field_media_image';

    $media_array = [
      $media_type_field_name => $file->id(),
      'name' => $title,
      'bundle' => $type,
    ];
    if($type == 'image') {
      $media_array['alt'] = $title;
    }

    $media_object = \Drupal\media\Entity\Media::create($media_array);
    $media_object->save();
    return $media_object->id();
  }
}
