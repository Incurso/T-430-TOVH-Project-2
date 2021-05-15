<?php

namespace Drupal\music_search;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Prepares the salutation to the world.
 */
class MusicSearchDiscogsService {

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
   * @param $uri
   * @param $query_params
   * @return mixed|\Psr\Http\Message\StreamInterface
   */
  private function query_api($uri, $query_params = null) {
    $config = $this->configFactory->get('music_search_configuration.settings');
    $discogs_key = $config->get('discogs_consumer_key');
    $discogs_secret = $config->get('discogs_consumer_secret');

    $options = array(
      'headers' => array(
        'Accept' => 'application/json',
        'Authorization' => 'Discogs key='. $discogs_key .', secret='. $discogs_secret
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
  public function search($query, $searchType) {
    $uri = 'https://api.discogs.com/database/search';
    $returnData = array();

    $type = null;
    $response = null;

    switch ($searchType) {
      case 'album':
        $type = 'albums';
        $response = $this->query_api($uri, array('q' => $query, 'type' => 'master', 'format' => 'album'));
        break;
      case 'artist':
        $type = 'artists';
        $response = $this->query_api($uri, array('q' => $query, 'type' => 'artist'));
        break;
    }

    if (!array_key_exists($type, $returnData)) {
      $returnData[$type] = array();
    }

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

  public function getArtist($id) {
    $uri = 'https://api.discogs.com/artists/'. $id;

    $response = $this->query_api($uri);

    $returnData = array(
      'id' => $response['id'],
      'name' => $response['name'],
      'images' => $response['images'],
      'description' => $response['profile'],
      'website' => reset($response['urls'])
    );

    return $returnData;
  }
}
