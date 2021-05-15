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
                  ['data' => t('Album Cover')],
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
                ['data' => ['#markup' => '<input type="checkbox" name="'. $item['id'] .'" value="'. $item['id'] .'"/>']],
                ['data' => ['#markup' => '<a href="./music_search/album/?id='. $item['id'] .'">' . t('Add') . '</a>']]
              ));
            }
            break;
          case 'artists':
            if (!array_key_exists($typeKey, $returnData[$serviceKey])) {
              $returnData[$serviceKey][$typeKey] = array(
                '#type' => 'tableselect',
                '#caption' => ucfirst($serviceKey) .' Artists',
                '#header' => [
                  'thumbnail' => '',
                  'title' => t('Title'),
                ],
                '#multiple' => FALSE,
                '#options' => array()
              );
            }

            foreach ($typeValue as $item) {
              $returnData[$serviceKey][$typeKey]['#options'][$item['id']] = array(
                'thumbnail' => ['data' => ['#theme' => 'image', '#width' => 150, '#alt' => $item['thumbnail'], '#uri' => $item['thumbnail']]],
                'title' => ['data' => $item['title']],
              );
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

  public function getAlbum($id) {
    return $this->spotifyService->getAlbum($id);
  }


  /**
   * Saves a file, based on it's type
   *
   * @param $url
   *   Full path to the image on the internet
   * @param $folder
   *   The folder where the image is stored on your hard drive
   * @param $type
   *   Type should be 'image' at all time for images.
   * @param $title
   *   The title of the image (like ALBUM_NAME - Cover), as it will appear in the Media management system
   * @param $basename
   *   The name of the file, as it will be saved on your hard drive
   *
   * @return int|null|string
   * @throws EntityStorageException
   */
  function _save_file($url, $folder, $type, $title, $basename, $uid = 1) {
    if(!is_dir(\Drupal::config('system.file')->get('default_scheme').'://' . $folder)) {
      return null;
    }
    $destination = \Drupal::config('system.file')->get('default_scheme').'://' . $folder . '/'.basename($basename);
    if(!file_exists($destination)) {
      $file = file_get_contents($url);
      $file = file_save_data($file, $destination);
    }
    else {
      $file = \Drupal\file\Entity\File::create([
        'uri' => $destination,
        'uid' => $uid,
        'status' => FILE_STATUS_PERMANENT
      ]);

      $file->save();
    }

    $file->status = 1;

    $media_type_field_name = 'field_media_image';

    $media_array = [
      $media_type_field_name => $file->id(),
      'name' => $title,
      'bundle' => $type,
    ];
    if($type == 'image') {
      $media_array['alt'] = $title;
    }

    $media_object = \Drupal\media\Entity\Media::create($media_array);
    $media_object->save();
    return $media_object->id();
  }
}
