<?php


namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\music_search\Form\MusicSearchForm;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the salutation message.
 */
class MusicSearchController extends ControllerBase
{

  /**
   * SpotifySearchService
   *
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $service;

  /**
   * discogs search service
   *
   * @var \Drupal\music_search\DiscogsSearchService
   */
  protected $discogsService;


  /**
   * The search form
   * @var \Drupal\music_search\Form\MusicSearchForm
   */
  protected $search_form;

  /**
   * MusicSearchController constructor.
   *
   * @param \Drupal\music_search\MusicSearchService $service
   * The spotify search service.
   * @param \Drupal\music_search\DiscogsSearchService $discogsService
   * the discogs search service
   * @param \Drupal\music_search\Form\MusicSearchForm $searchForm
   * the search form
   */
  public function __construct(MusicSearchService $service, MusicSearchForm $searchForm) {
    $this->service = $service;
    //$this->discogsService = $discogsService;
    $this->search_form = $searchForm;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('music_search.service'),
      //$container->get('discogs_search.service'),
      
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

    $query = $session->get('search_query');
    $types = $session->get('search_types');

    if ($request->getMethod() == 'POST') {
      $session->set('search_query', $request->request->get('q'));
      $session->set('search_types', $request->request->get('types'));
    } else if ($query && $types) {
      $uri = Url::fromRoute('music_search.search')->toString();
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
            ['data' => ['#markup' => '<a href="'. $uri .'/artist/?id='. $id . '">' . t('Add') . '</a>']]
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
            ['data' => ['#markup' => '<a href="'. $uri .'/album/?id='. $id . '">' . t('Add') . '</a>']]
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
          $id = $item['album']['id'];
          $name = $item['name'];
          #$image_url = $item['images'] ? array_pop($item['images'])['url'] : '';

          array_push($tracks['#rows'], array(
            ['data' => $item['track_number']],
            ['data' => $item['album']['name']],
            ['data' => $name],
            ['data' => ['#markup' => '<a href="'. $uri .'/album/?id='. $id . '">' . t('Add') . '</a>']]
          ));
        }
      }
    }

    $search_form = \Drupal::formbuilder()->getForm($this->search_form);

    $session->remove('search_query');
    $session->remove('search_types');

    return [
      '#theme' => array('container'),
      '#attributes' => [],
      '#children' => array(
        $search_form,
        $artists,
        $albums,
        $tracks,
      )
    ];
  }
}
