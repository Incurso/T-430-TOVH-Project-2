<?php

namespace Drupal\music_search\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\music_search\Form\MusicSearchForm;
use Drupal\music_search\Form\MusicSearchListSearchForm;
use Drupal\music_search\MusicSearchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Controller for the Music Search Module
 * Inherits from the Drupal ControllerBase
 */
class MusicSearchController extends ControllerBase
{

  /**
   * Local Search Service variable to access the main MusicSearch Service
   * @var \Drupal\music_search\MusicSearchService
   */
  protected $service;

  /**
   * Local Music search form variable to access the main Music Search Form
   * @var \Drupal\music_search\Form\MusicSearchForm
   */
  protected $searchForm;

  /**
   * Local Music List Search form variable to access the main Music Search List Form
   * @var \Drupal\music_search\Form\MusicSearchListSearchForm
   */
  protected $listForm;

  /**
   * MusicSearchController constructor.
   * @param \Drupal\music_search\MusicSearchService $service
   * The main search service.
   * @param \Drupal\music_search\Form\MusicSearchForm $searchForm
   * the main search form
   * @param \Drupal\music_search\Form\MusicSearchListSearchForm $list_form
   * the main music list form
   */
  public function __construct(MusicSearchService $service, MusicSearchForm $search_form, MusicSearchListSearchForm $list_form) {
    $this->service = $service;
    $this->searchForm = $search_form;
    $this->listForm = $list_form;
  }

  /**
   * Create container function
   * Dependency Injection of the music_search services
   * {@inheritdoc}
   * @return containers of the music_search services
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
   * The main musicSearch function
   * @return array of the theme, the attributes and the forms.
   */
  public function musicSearch()
  {
    // Create a request and a session
    $request = \Drupal::request();
    $session = $request->getSession();

    // Initialize the search results
    $searchResults = null;

    // Get a search query and search types
    $query = $session->get('search_query');
    $types = $session->get('search_types');

    // Store search parameters in session
    if ($request->getMethod() == 'POST') {
      $session->set('search_query', $request->request->get('q'));
      if ($request->request->get('types')) {
        $session->set('search_types', $request->request->get('types'));
      }
    } else if ($query && $types) {
      $uri = Url::fromRoute('music_search.search')->toString();
    }

    // Generate forms
    $searchForm = \Drupal::formbuilder()->getForm($this->searchForm);
    $listForm = \Drupal::formbuilder()->getForm($this->listForm);

    return [
      '#theme' => array('container'),
      '#attributes' => [],
      '#children' => array(
        $searchForm,
        $listForm,
      )
    ];
  }
}
