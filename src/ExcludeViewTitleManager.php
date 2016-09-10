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
   *  Config object
   */
  private $configFactory;

  /**
   * Constructor.
   */
  public function __construct(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory
      ->getEditable('exclude_view_title.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function isViewPageTitleExcluded($view_id, $page_id) {
    $isExcluded = FALSE;

    $view = $this->configFactory->get($view_id . '.' . $page_id);

    if ($view == '1') {
      $isExcluded = TRUE;
    }

    return $isExcluded;
  }

}
