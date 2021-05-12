<?php

namespace Drupal\music_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Prepares the salutation to the world.
 */
class MusicSearchService {

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
   * MusicSearchService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   The event dispatcher.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * Returns access token
   */
  private function login() {
    $request = \Drupal::request();
    $session = $request->getSession();
    $access_token = $session->get('spotify_access_token');
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
    $session->set('spotify_access_token', $access_token);
  }

  /**
   * Returns boolean if token is expired or not
   */
  private function token_expired() {
    $request = \Drupal::request();
    $session = $request->getSession();
    $access_token = $session->get('spotify_access_token');
    $date = new \DateTime();

    return (
      $access_token == "" &&
      !$access_token ||
      ($access_token['issued_at'] + $access_token['expires_in']) < $date->getTimestamp()
    );
  }

  /**
   * Returns the salutation.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The salutation message.
   */
  public function getSalutation() {
    $request = \Drupal::request();
    $session = $request->getSession();
    $access_token = $session->get('spotify_access_token');
    #$session->set('spotify_access_token', '');

    if ($this->token_expired()) {
      $this->login();
      $access_token = $session->get('spotify_access_token');
    }

    $options = array(
      'headers' => array(
        'Accept' => 'application/json',
        'Authorization' => $access_token['token_type'] .' '. $access_token['access_token']
      ),
      'query' => array(
        'q' => 'Metallica',
        'type' => 'album,artist'
      )
    );

    $uri = 'https://api.spotify.com/v1/search';
    $response = \Drupal::httpClient()->get($uri, $options);

    return (string) $response->getBody();

    #return var_dump($access_token);
    return implode('|', $access_token) .'|'. $date->getTimestamp() .'|'. ($access_token['issued_at'] + 300);
    #return $access_token->get('access_token');

    #$response = \Drupal::httpClient()->post($uri, $options);
    $data = $response->getBody()->getContents();

    #$uri = 'http://api.spotify.com/search?q=artist:Metallica';
    #$response = \Drupal::httpClient()->get($uri, array('headers' => array('Accept' => 'application/json')));
    #$data = (string) $response->getBody();

    return $data;
    /*
    if ($client_id !== "" && $client_id) {
      return $client_id;
    }
    */



    /*
    $time = new \DateTime();
    if ((int) $time->format('G') >= 00 && (int) $time->format('G') < 12) {
      return $this->t('Good morning world');
    }

    if ((int) $time->format('G') >= 12 && (int) $time->format('G') < 18) {
      return $this->t('Good afternoon world');
    }

    if ((int) $time->format('G') >= 18) {
      return $this->t('Good evening world');
    }
    */
  }

}
