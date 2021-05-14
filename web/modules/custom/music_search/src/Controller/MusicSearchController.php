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
   * The salutation service.
   *
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $service;


  /**
   * The search form
   * @var \Drupal\music_search\Form\MusicSearchForm
   */
  protected $search_form;

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
    $this->search_form = $searchForm;
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
  public function musicSearch()
  {
    $request = \Drupal::request();
    $session = $request->getSession();

    $searchResults = null;

    $query = $session->get('search_query');
    $types = $session->get('search_types');

    if ($request->getMethod() == 'POST') {
      $session->set('search_query', $request->request->get('q'));
      $session->set('search_types', $request->request->get('types'));
    } else if ($query && $types) {
      $uri = Url::fromRoute('music_search.search')->toString();
      $searchResults = $this->service->search($query, $types);
    }

    $search_form = \Drupal::formbuilder()->getForm($this->search_form);

    $session->remove('search_query');
    $session->remove('search_types');

    return [
      '#theme' => array('container'),
      '#attributes' => [],
      '#children' => array(
        $search_form,
        $searchResults ? reset($searchResults['spotify']) : null,
        $searchResults ? reset($searchResults['discogs']) : null,
      )
    ];
  }
}
