<?php

namespace Drupal\music_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Prepares the salutation to the world.
 */
class MusicSearchSpotifyService {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * The session variable to keep the api tokens from music apis intact and refresh them when they've expired.
   *
   */
  protected $session;

  /**
   * MusicSearchService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $requests = \Drupal::request();
    $this->session = $requests->getSession();
    $this->configFactory = $config_factory;
  }

  /**
   * Returns access token
   */
  private function login() {
    #$request = \Drupal::request();
    #$session = $request->getSession();
    #$access_token = $session->get('spotify_access_token');
    $access_token = $this->session->get('spotify_access_token');
    $config = $this->configFactory->get('music_search.settings');
    $client_id = $config->get('spotify_client_id');
    $client_secret = $config->get('spotify_client_secret');
    $date = new \DateTime();

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

    $uri = 'https://accounts.spotify.com/api/token';

    # TODO: Attempt login
    # Authenticate against Spotify
    $response = \Drupal::httpClient()->post($uri, $options);

    # Assume we get access token as a response
    $access_token = \json_decode($response->getBody(), true);
    # Add issued_at to simplify expiration checks
    $access_token['issued_at'] = $date->getTimestamp();
    # Store access token in session
    $this->session->set('spotify_access_token', $access_token);
  }

  /**
   * Returns boolean if token is expired or not
   */
  private function token_expired() {
    #$request = \Drupal::request();
    #$session = $request->getSession();
    $access_token = $this->session->get('spotify_access_token');
    $date = new \DateTime();

    return (
      $access_token == "" &&
      !$access_token ||
      ($access_token['issued_at'] + $access_token['expires_in']) < $date->getTimestamp()
    );
  }

  /**
   * @param $uri
   * @param $query_params
   * @return mixed|\Psr\Http\Message\StreamInterface
   */
  private function query_api($uri, $query_params = null) {
    if ($this->token_expired()) {
      $this->login();
    }

    $token = $this->session->get('spotify_access_token');

    $options = array(
      'headers' => array(
        'Accept' => 'application/json',
        'Authorization' => $token['token_type'] .' '. $token['access_token']
      ),
      'query' => $query_params
    );

    $response = \Drupal::httpClient()->get($uri, $options);

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
   * @param $query
   * @param $types
   * @return mixed|\Psr\Http\Message\StreamInterface
   */
  public function search($query, $types) {
    $uri = 'https://api.spotify.com/v1/search';
    $query_params = array(
      'q' => $query,
      'type' => $types
    );

    $returnData = array();

    $response = $this->query_api($uri, $query_params);

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

  public function getArtist($id) {
    $uri = 'https://api.spotify.com/v1/artists/'. $id;

    $response = $this->query_api($uri);

    $returnData = array(
      'id' => $response['id'],
      'name' => $response['name'],
      'images' => $response['images'],
      'description' => '',
      'website' => ''
    );

    return $returnData;
  }

  public function getAlbum($id) {
    $uri = 'https://api.spotify.com/v1/albums/'. $id;

    $response = $this->query_api($uri);

    $returnData = array(
      'id' => $response['id'],
      'title' => $response['title'],
      'images' => $response['images'],
      'description' => $response['notes'],
      'tracks' => $response['tracklist'],
      'artists' => $response['artists'],
      'genres' => '',
      'label' => '',
      'year' => '',
    );

    return $returnData;
  }
}
