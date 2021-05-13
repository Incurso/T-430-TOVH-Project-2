<?php


namespace Drupal\music_search\Controller;

use Drupal\Core\File;
use Drupal\Core\Controller\ControllerBase;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the salutation message.
 */
class MusicSearchController extends ControllerBase
{

  /**
   * The salutation service.
   *
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $service;

  /**
   * MusicSearchController constructor.
   *
   * @param \Drupal\music_search\MusicSearchService $service
   *   The salutation service.
   */
  public function __construct(MusicSearchService $service) {
    $this->service = $service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('music_search.service')
    );
  }

  /**
   * Music Search.
   *
   * @return array
   *   Our message.
   */
  public function musicSearch($query = 'Linkin Park', $types=['album', 'artist', 'track']) {
    $albums = null;
    $artists = null;
    $tracks = null;

    $search_results = $this->service->search($query, implode(',', $types));

    if (in_array('artist', $types)) {
      $artists = array(
        '#theme' => 'table',
        '#caption' => 'Artists',
        '#header' => [
          [],
          ['data' => t('Name')],
          []
        ],
        '#rows' => array()
      );
      foreach ($search_results['artists']['items'] as $item) {
        $id = $item['id'];
        $name = $item['name'];
        $image_url = $item['images'] ? array_pop($item['images'])['url'] : '';

        array_push($artists['#rows'], array(
          ['data' => ['#theme' => 'image', '#width' => 150, '#alt' => $image_url, '#uri' => $image_url]],
          ['data' => $name],
          ['data' => ['#markup' => '<a href="#'. $id .'">'. t('Add') .'</a>']]
        ));
      }
    }

    if (in_array('album', $types)) {
      $albums = array(
        '#theme' => 'table',
        '#caption' => 'Albums',
        '#header' => [
          [],
          ['data' => t('Album')],
          ['data' => t('Release date')],
          []
        ],
        '#rows' => array()
      );
      foreach ($search_results['albums']['items'] as $item) {
        $id = $item['id'];
        $name = $item['name'];
        $image_url = $item['images'] ? array_pop($item['images'])['url'] : '';

        array_push($albums['#rows'], array(
          ['data' => ['#theme' => 'image', '#width' => 150, '#alt' => $image_url, '#uri' => $image_url]],
          ['data' => $name],
          ['data' => $item['release_date']],
          ['data' => ['#markup' => '<a href="#' . $id . '">' . t('Add') . '</a>']]
        ));
      }
    }

    if (in_array('track', $types)) {
      $tracks = array(
        '#theme' => 'table',
        '#caption' => 'Tracks',
        '#header' => [
          ['data' => t('#')],
          ['data' => t('Album')],
          ['data' => t('Name')],
          []
        ],
        '#rows' => array()
      );
      foreach ($search_results['tracks']['items'] as $item) {
        $id = $item['id'];
        $name = $item['name'];
        #$image_url = $item['images'] ? array_pop($item['images'])['url'] : '';

        array_push($tracks['#rows'], array(
          ['data' => $item['track_number']],
          ['data' => $item['album']['name']],
          ['data' => $name],
          ['data' => ['#markup' => '<a href="#' . $id . '">' . t('Add') . '</a>']]
        ));
      }
    }

    return [
      '#theme' => array('container'),
      '#attributes' => [],
      '#children' => array(
        $artists,
        $albums,
        $tracks
      )
    ];
  }
}
