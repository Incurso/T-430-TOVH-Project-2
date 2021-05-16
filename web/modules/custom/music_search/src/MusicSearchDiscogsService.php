<?php

namespace Drupal\music_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class MusicSearchDiscogsService
 * @package Drupal\music_search
 */
class MusicSearchDiscogsService {

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
   * MusicSearchService constructor.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *  The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    // Create the request and session to pass it to the local session var
    // and create the config factory
    $requests = \Drupal::request();
    $this->session = $requests->getSession();
    $this->configFactory = $config_factory;
  }

  /**
   * Function queryApi
   * To query the discogs API
   * @param $uri pass in the uri
   * @param $query_params pass in the query parameters
   * @return mixed|\Psr\Http\Message\StreamInterface returns the response body
   */
  private function queryApi($uri, $query_params = null) {

    // Get the configs and secrets from discogs
    $config = $this->configFactory->get('music_search_configuration.settings');
    $discogs_key = $config->get('discogs_consumer_key');
    $discogs_secret = $config->get('discogs_consumer_secret');

    // Generate the headers
    $options = array(
      'headers' => array(
        'Accept' => 'application/json',
        'Authorization' => 'Discogs key='. $discogs_key .', secret='. $discogs_secret
      ),
      'query' => $query_params
    );

    // Store the repsonse from discogs
    $response = \Drupal::httpClient()->get($uri, $options);

    // If query is not successfull
    // throw an error
    // and return the request body
    if ($response->getStatusCode() !== 200) {
      $message = 'Status: '. $response->getStatusCode();
      $message .= ' Reason: '. $response->getReasonPhrase();
      $message .= ' Message: '. $response->getBody();

      \Drupal::messenger()->addError($message);

      return $response->getBody();
    }

    return \json_decode($response->getBody(), true);
  }

  /**
   * Function search
   * To run the search query
   * @param $query pass in the search query(text from the search box)
   * @param $search_type pass in the query types(album or artist)
   * @return array
   */
  public function search($query, $search_type) {
    // Api url and return data
    $uri = 'https://api.discogs.com/database/search';
    $returnData = array();

    // Search type that discogs wants to see and response variables
    $type = null;
    $response = null;

    // Switches on the type of search album or artist
    // and queries the api
    switch ($search_type) {
      case 'album':
        $type = 'albums';
        $response = $this->queryApi($uri, array('q' => $query, 'type' => 'master', 'format' => 'album'));
        break;
      case 'artist':
        $type = 'artists';
        $response = $this->queryApi($uri, array('q' => $query, 'type' => 'artist'));
        break;
    }

    // Checks if there exists a search type in the return array
    // if not, pass it to the return array
    if (!array_key_exists($type, $returnData)) {
      $returnData[$type] = array();
    }

    // Runs through the api response
    // and adds the albums or artists to the return array
    foreach ($response['results'] as $item) {
      switch ($type) {
        case 'albums':
          array_push($returnData[$type], array(
            'id' => $item['id'],
            'title' => $item['title'],
            'year' => array_key_exists('year', $item) ? $item['year'] : '',
            'thumbnail' => $item['thumb']
          ));
          break;
        case 'artists':
          array_push($returnData[$type], array(
            'id' => $item['id'],
            'title' => $item['title'],
            'thumbnail' => $item['thumb']
          ));
          break;
      }
    }

    return $returnData;
  }

  /**
   * Function getArtist
   * To get the artist from the discogs API
   * @param $id passes in the artist id
   * @return array returns an array of artists
   */
  public function getArtist($id) {
    // Discogs api url
    $uri = 'https://api.discogs.com/artists/'. $id;

    // Query the discogs api with uri and artist id as parameters
    $response = $this->queryApi($uri);

    // Var to store the images
    $images = array();

    // Add the images url's to an array
    foreach ($response['images'] as $image) {
      array_push($images, array(
        'url' => $image['uri']
      ));
    }
    $returnData = array(
      'id' => $response['id'],
      'name' => $response['name'],
      'images' => $images,
      'description' => $response['profile'],
      'website' => reset($response['urls'])
    );

    return $returnData;
  }

  /**
   * Function getAlbum
   * To get the albums from discogs
   * @param $id passes in the album id
   * @return array returns an array of albums
   */
  public function getAlbum($id) {
    // Discogs api url
    $uri = 'https://api.discogs.com/masters/'. $id;

    // Query the discogs api with uri and album id as parameters
    $response = $this->queryApi($uri);

    // Var to store the images
    $images = array();

    // Add the images url's to an array
    foreach ($response['images'] as $image) {
      array_push($images, array(
        'url' => $image['uri']
      ));
    }

    // Var to store the tracks
    $tracks = array();
    // Add the tracks to an array
    foreach ($response['tracklist'] as $track) {
      array_push($tracks, array(
        'id' => $id .'-'. $track['position'],
        'title' => $track['title'],
        'duration' => $track['duration'],
        'position' => $track['position'],
      ));
    }

    $returnData = array(
      'id' => $response['id'],
      'title' => $response['title'],
      'images' => $images,
      'description' => array_key_exists('notes', $response) ? $response['notes'] : '',
      'tracks' => $tracks,
      'artists' => $response['artists'],
      'genres' => $response['genres'],
      'label' => '',
      'year' => $response['year'],
    );

    return $returnData;
  }
}
