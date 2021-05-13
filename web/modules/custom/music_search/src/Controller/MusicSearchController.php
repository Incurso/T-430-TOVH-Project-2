<?php


namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\music_search\Form\MusicSearchForm;
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
   * The search form
   * @var \Drupal\music_search\Form\MusicSearchForm
   */
  protected $searchForm;

  /**
   * MusicSearchController constructor.
   *
   * @param \Drupal\music_search\MusicSearchService $service
   * The search service.
   * @param \Drupal\music_search\Form\MusicSearchForm $searchForm
   * the search form
   */
  public function __construct(MusicSearchService $service, MusicSearchForm $searchForm) {
    $this->service = $service;
    $this->searchForm = $searchForm;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('music_search.service'),
      $container->get('music_search.form')
    );
  }

  /**
   * @param string $query
   * @param string[] $types
   * @return array
   */
  public function musicSearch(/*$query = 'Metallica', $types=['album', 'artist', 'track']*/)
  {
    $request = \Drupal::request();
    $session = $request->getSession();

    $albums = null;
    $artists = null;
    $tracks = null;

    if ($request->request->all()) {
      $query = \Drupal::request()->request->get('q');
      $types = \Drupal::request()->request->get('types');

      $request = \Drupal::request();
      $search_results = $this->service->search($query, implode(',', $types));

      if (in_array('artist', $types)) {
        $artists = array(
          '#theme' => 'table',
          '#caption' => 'Artists',
          '#header' => [
            [],
            ['data' => t('Artist')],
            []
          ],
          '#rows' => array()
        );
        foreach ($search_results['artists']['items'] as $item) {
          $id = $item['id'];
          $name = $item['name'];
          $image_url = $item['images'] ? array_pop($item['images'])['url'] : null;

          array_push($artists['#rows'], array(
            ['data' => ['#theme' => 'image', '#width' => 150, '#alt' => $image_url, '#uri' => $image_url]],
            ['data' => $name],
            ['data' => ['#markup' => '<a href="#' . $id . '">' . t('Add') . '</a>']]
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
    }

    return [
      '#theme' => array('container'),
      '#attributes' => [],
      '#children' => array(
        \Drupal::formbuilder()->getForm($this->searchForm),
        $artists,
        $albums,
        $tracks,
      )
    ];
  }
}
