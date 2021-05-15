<?php


namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\music_search\Form\MusicSearchForm;
use Drupal\music_search\Form\MusicSearchListSearchForm;
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
   * The search form
   * @var \Drupal\music_search\Form\MusicSearchForm
   */
  protected $searchForm;

  /**
   * @var \Drupal\music_search\Form\MusicSearchListSearchForm
   */
  protected $listForm;

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
  public function __construct(MusicSearchService $service, MusicSearchForm $search_form, MusicSearchListSearchForm $list_form) {
    $this->service = $service;
    $this->searchForm = $search_form;
    $this->listForm = $list_form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('music_search.service'),
      $container->get('music_search.form'),
      $container->get('music_search_list.form')
    );
  }

  /**
   * @param string $query
   * @param string[] $types
   * @return array
   */
  public function musicSearch()
  {
    $request = \Drupal::request();
    $session = $request->getSession();

    $searchResults = null;

    $query = $session->get('search_query');
    $types = $session->get('search_types');

    if ($request->getMethod() == 'POST') {
      # $types = $request->request->get('types');
      $session->set('search_query', $request->request->get('q'));
      if ($request->request->get('types')) {
        $session->set('search_types', $request->request->get('types'));
      }
    } else if ($query && $types) {
      $uri = Url::fromRoute('music_search.search')->toString();
      # $searchResults = $this->service->search($query, $types);
    }

    $searchForm = \Drupal::formbuilder()->getForm($this->searchForm);
    $listForm = \Drupal::formbuilder()->getForm($this->listForm);

    $session->remove('search_query');
    # $session->remove('search_types');

    return [
      '#theme' => array('container'),
      '#attributes' => [],
      '#children' => array(
        $searchForm,
        $listForm,
        #$searchResults ? reset($searchResults['spotify']) : null,
        #$searchResults ? reset($searchResults['discogs']) : null,
      )
    ];
  }
}
