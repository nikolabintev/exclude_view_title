<?php

namespace Drupal\exclude_view_title;

use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class ExcludeViewTitleManager.
 *
 * @package Drupal\exclude_view_title
 */
class ExcludeViewTitleManager implements ExcludeViewTitleManagerInterface {

  /**
   * @var \Drupal\Core\Config\Config
   */
  private $config_factory;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config_factory = $config_factory
      ->getEditable('exclude_view_title.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isViewPageTitleExcluded($view_id, $page_id) {
    $isExcluded = FALSE;

    $view = $this->config_factory->get($view_id . '.' . $page_id);

    if ($view == '1') {
      $isExcluded = TRUE;
    }

    return $isExcluded;
  }
}
