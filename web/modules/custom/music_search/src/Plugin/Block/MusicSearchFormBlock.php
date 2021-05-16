<?php

namespace Drupal\music_search\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\music_search\Form\MusicSearchForm;

/**
 * Hello World Salutation block.
 *
 * @Block(
 *  id = "music_search_form_block",
 *  admin_label = @Translation("Music Service Search"),
 * )
 */
class MusicSearchFormBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * @var \Drupal\music_search\Form\MusicSearchForm
   */
  protected $searchForm;

  /**
   * HelloWorldSalutationBlock constructor.
   * @param array $configuration
   *  A configuration array containing information about the plugin interface.
   * @param $plugin_id
   *  The plugin_id for the plugins instance.
   * @param $plugin_definition
   *  The plugins implementation definition.
   * @param MusicSearchForm $searchForm
   *  The salutation service
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MusicSearchForm $searchForm) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->searchForm = $searchForm;
  }

  /**
   * @param ContainerInterface $container
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('music_search.form')
    );
  }

  /**
   * @return array
   */
  public function build() {
    return \Drupal::formbuilder()->getForm('Drupal\music_search\Form\MusicSearchForm');
  }
}
