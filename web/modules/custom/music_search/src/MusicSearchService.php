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
   * The session variable to keep the api tokens from music apis intact and refresh them when they've expired.
   *
   */
  protected $session;

  /**
   * @var \Drupal\music_search\MusicSearchDiscogsService
   */
  protected $discogsService;

  /**
   * @var \Drupal\music_search\MusicSearchSpotifyService
   */
  protected $spotifyService;

  /**
   * MusicSearchService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MusicSearchDiscogsService $discogs_service, MusicSearchSpotifyService $spotify_service) {
    $requests = \Drupal::request();
    $this->session = $requests->getSession();
    $this->configFactory = $config_factory;
    $this->discogsService = $discogs_service;
    $this->spotifyService = $spotify_service;
  }

  /**
   * @param $query
   * @param $types
   * @return mixed|\Psr\Http\Message\StreamInterface
   */
  public function search($query, $types) {
    $serviceResponse['discogs'] = $this->discogsService->search($query, $types);
    $serviceResponse['spotify'] = $this->spotifyService->search($query, $types);

    $returnData = array(
      'discogs' => array(),
      'spotify' => array(),
    );

    foreach ($serviceResponse as $serviceKey => $serviceValue) {
      foreach ($serviceValue as $typeKey => $typeValue) {
        switch ($typeKey) {
          case 'albums':
            if (!array_key_exists($typeKey, $returnData[$serviceKey])) {
              $returnData[$serviceKey][$typeKey] = array(
                '#theme' => 'table',
                '#caption' => ucfirst($serviceKey) .' Albums',
                '#header' => [
                  [],
                  ['data' => t('Title')],
                  ['data' => t('Release date')],
                  []
                ],
                '#rows' => array()
              );
            }

            foreach ($typeValue as $item) {
              array_push($returnData[$serviceKey][$typeKey]['#rows'], array(
                ['data' => ['#theme' => 'image', '#width' => 150, '#alt' => $item['thumbnail'], '#uri' => $item['thumbnail']]],
                ['data' => $item['title']],
                ['data' => $item['year']],
                ['data' => ['#markup' => '<a href="./music_search/album/?id='. $item['id'] .'">' . t('Add') . '</a>']]
              ));
            }
            break;
          case 'artists':
            if (!array_key_exists($typeKey, $returnData[$serviceKey])) {
              $returnData[$serviceKey][$typeKey] = array(
                '#theme' => 'table',
                '#caption' => ucfirst($serviceKey) .' Artists',
                '#header' => [
                  [],
                  ['data' => t('Title')],
                  []
                ],
                '#rows' => array()
              );
            }

            foreach ($typeValue as $item) {
              array_push($returnData[$serviceKey][$typeKey]['#rows'], array(
                ['data' => ['#theme' => 'image', '#width' => 150, '#alt' => $item['thumbnail'], '#uri' => $item['thumbnail']]],
                ['data' => $item['title']],
                ['data' => ['#markup' => '<a href="./music_search/artist/?id='. $item['id'] .'">' . t('Add') . '</a>']]
              ));
            }
            break;
        }
      }
    }

    return $returnData;
  }

  public function getArtist($id) {
    return $this->spotifyService->getArtist($id);
  }
}
