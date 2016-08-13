<?php

namespace Drupal\exclude_view_title;

/**
 * Interface ExcludeViewTitleManagerInterface.
 *
 * @package Drupal\exclude_view_title
 */
interface ExcludeViewTitleManagerInterface {

  /**
   * Checks whether the View's Page title is excluded.
   * @param $view_id
   *  View id.
   * @param $page_id
   *  View's page id.
   * @return boolean
   *  Returns true or false.
   */
  public function isViewPageTitleExcluded($view_id, $page_id);
}
