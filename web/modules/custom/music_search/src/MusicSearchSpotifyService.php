<?php

namespace Drupal\music_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class MusicSearchSpotifyService
 * @package Drupal\music_search
 */
class MusicSearchSpotifyService {

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
   * MusicSearchSpotifyService constructor.
   * @param ConfigFactoryInterface $config_factory the config factory
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    // Create the request and session to pass it to the local session var
    // and create the config factory
    $requests = \Drupal::request();
    $this->session = $requests->getSession();
    $this->configFactory = $config_factory;
  }

  /**
   * Function login
   * To access the spotify music service api for authentication
   * and get an access token.
   */
  private function login() {
    // get configs and sercrets and the current timestamp
    $config = $this->configFactory->get('music_search.settings');
    $client_id = $config->get('spotify_client_id');
    $client_secret = $config->get('spotify_client_secret');
    $date = new \DateTime();

    // Prepare the header for the spotify api
    $options = array(
      'headers' => array(
        'Accept' => 'application/json',
        'Authorization' => 'Basic '. \base64_encode($client_id.':'.$client_secret),
        'Content-Type' => 'application/x-www-form-urlencoded',
      ),
      'form_params' => array(
        'grant_type' => 'client_credentials'
      )
    );

    // The api uri to access spotify api
    $uri = 'https://accounts.spotify.com/api/token';

    // Authenticate against Spotify
    $response = \Drupal::httpClient()->post($uri, $options);

    // Assume we get access token as a response
    $access_token = \json_decode($response->getBody(), true);
    // Add issued_at to simplify expiration checks
    $access_token['issued_at'] = $date->getTimestamp();
    // Store access token in session
    $this->session->set('spotify_access_token', $access_token);
  }

  /**
   * Function tokenExpired
   * To get a new token when the token has expired
   * @return bool Returns boolean if token is expired or not
   */
  private function tokenExpired() {
    // Gets a new access token and a new time stamp
    $access_token = $this->session->get('spotify_access_token');
    $date = new \DateTime();

    return (
      $access_token == "" &&
      !$access_token ||
      ($access_token['issued_at'] + $access_token['expires_in']) < $date->getTimestamp()
    );
  }

  /**
   * Function queryApi
   * To query the spotify API
   * @param $uri pass in the uri
   * @param $query_params pass in the query parameters
   * @return mixed|\Psr\Http\Message\StreamInterface returns the response body
   */
  private function queryApi($uri, $query_params = null) {
    // Checks if the token is expired
    // and logs in
    if ($this->tokenExpired()) {
      $this->login();
    }

    // Access the token
    $token = $this->session->get('spotify_access_token');

    // Generate the header for the api query
    $options = array(
      'headers' => array(
        'Accept' => 'application/json',
        'Authorization' => $token['token_type'] .' '. $token['access_token']
      ),
      'query' => $query_params
    );

    // Get the response
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
   * @param $types pass in the query types(albums or artists)
   * @return array return an array of items for the search response
   */
  public function search($query, $types) {
    $uri = 'https://api.spotify.com/v1/search';

    // Generate query parameters
    $query_params = array(
      'q' => $query,
      'type' => $types
    );

    // Create the return data
    $returnData = array();

    // Query the spotify api with the parameters
    $response = $this->queryApi($uri, $query_params);


    // Run through the response and pass the return items to the return array
    foreach ($response as $typeKey => $typeValue) {
      if (!array_key_exists($typeKey, $returnData)) {
        $returnData[$typeKey] = array();
      }

      foreach ($typeValue['items'] as $item) {
        switch ($typeKey) {
          case 'albums':
            array_push($returnData[$typeKey], array(
              'id' => $item['id'],
              'title' => $item['name'],
              'year' => $item['release_date'],
              'thumbnail' => $item['images'] ? $item['images'][0]['url'] : '',
            ));
            break;
          case 'artists':
            array_push($returnData[$typeKey], array(
              'id' => $item['id'],
              'title' => $item['name'],
              'thumbnail' => $item['images'] ? $item['images'][0]['url'] : '',
            ));
            break;
        }
      }
    }

    return $returnData;
  }


  /**
   * Function getArtist
   * To get the artist from the spotify API
   * @param $id passes in the artist id
   * @return array returns an array of artists
   */
  public function getArtist($id) {
    // Spotify api url
    $uri = 'https://api.spotify.com/v1/artists/'. $id;

    // Query the spotify api with uri and artist id as parameters
    $response = $this->queryApi($uri);

    // Var to store the images
    $images = array();

    // Add the images url's to an array
    foreach ($response['images'] as $image) {
      array_push($images, array(
        'url' => $image['url']
      ));
    }

    $returnData = array(
      'id' => $response['id'],
      'name' => $response['name'],
      'images' => $images,
      'description' => null,
      'website' => null
    );

    return $returnData;
  }

  /**
   * Function getAlbum
   * To get the albums from spotify
   * @param $id passes in the album id
   * @return array returns an array of albums
   */
  public function getAlbum($id) {
    // Spotify api url
    $uri = 'https://api.spotify.com/v1/albums/'. $id;

    // Query the spotify api with uri and album id as parameters
    $response = $this->queryApi($uri);

    // Var to store the images
    $images = array();

    // Add the images url's to an array
    foreach ($response['images'] as $image) {
      array_push($images, array(
        'url' => $image['url']
      ));
    }

    // Var to store the tracks
    $tracks = array();

    // Add the tracks to an array
    foreach ($response['tracks']['items'] as $track) {
      array_push($tracks, array(
        'id' => $track['id'],
        'title' => $track['name'],
        'duration' => $track['duration_ms'] / 1000,
        'position' => $track['track_number'],
      ));
    }

    $returnData = array(
      'id' => $response['id'],
      'title' => $response['name'],
      'images' => $images,
      'description' => '',
      'tracks' => $tracks,
      'artists' => $response['artists'],
      'genres' => $response['genres'],
      'label' => $response['label'],
      'year' => $response['release_date'],
    );

    return $returnData;
  }
}
