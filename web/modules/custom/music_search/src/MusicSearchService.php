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
   * @var \Drupal\music_search\MusicSearchSpotifyService
   */
  protected $service;

  /**
   * MusicSearchService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, MusicSearchSpotifyService $service) {
    $requests = \Drupal::request();
    $this->session = $requests->getSession();
    $this->configFactory = $config_factory;
    $this->service = $service;
  }

  /**
   * @param $query
   * @param $types
   * @return mixed|\Psr\Http\Message\StreamInterface
   */
  public function search($query, $types) {
    return $this->service->search($query, $types);
  }

  public function getArtist($id) {
    return $this->service->getArtist($id);
  }
}
