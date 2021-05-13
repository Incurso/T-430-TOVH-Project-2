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
  public function musicSearch()
  {
    return [
      '#markup' => $this->searchForm->buildForm(),
      '#markup' => $this->service->getSalutation(),
    ];
  }

}
