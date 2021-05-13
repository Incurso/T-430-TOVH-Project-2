<?php


namespace Drupal\music_search\Controller;

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
  public function musicSearch()
  {
    return [
      '#markup' => $this->service->search('Metallica', 'artist'),
    ];
    /*
    return [
      '#theme' => 'item_list',
      '#list_type' => 'ul',
      '#items' => $this->service->getSalutation()
    ];
    */
    /*
    return [
      '#theme' => array('container'),
      '#attributes' => [
        'class' => ['more-link'],
      ],
      '#children' => $this->service->getSalutation()
    ];
    */
  }

}
