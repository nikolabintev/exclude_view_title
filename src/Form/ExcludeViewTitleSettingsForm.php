<?php

namespace Drupal\exclude_view_title\Form;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Entity\View;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\exclude_view_title\ExcludeViewTitleManagerInterface;

/**
 * Class ExcludeViewTitleSettingsForm.
 *
 * @package Drupal\exclude_view_title\Form
 */
class ExcludeViewTitleSettingsForm extends ConfigFormBase {

  /**
   * ExcludeViewTitleManager object.
   * @var ExcludeViewTitleManagerInterface
   */
  private $titleManager;

  /**
   * CacheTagsInvalidator object.
   * @var CacheTagsInvalidatorInterface
   */
  private $cacheTagsInvalidator;

  /**
   * ExcludeViewTitleSettingsForm constructor.
   *
   * @param ConfigFactoryInterface $config_factory
   *  Config factory object.
   * @param ExcludeViewTitleManagerInterface $titleManager
   *  ExcludeViewTitleManager.
   * @param CacheTagsInvalidatorInterface $cacheTagsInvalidator
   *  CacheTagsInvalidator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExcludeViewTitleManagerInterface $titleManager, CacheTagsInvalidatorInterface $cacheTagsInvalidator) {
    parent::__construct($config_factory);
    $this->titleManager = $titleManager;
    $this->cacheTagsInvalidator = $cacheTagsInvalidator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('exclude_view_title.manager'),
      $container->get('cache_tags.invalidator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'exclude_view_title_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'exclude_view_title.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $views = Views::getAllViews();

    $form['views'] = [
      '#type' => 'details',
      '#title' => $this->t('Exclude title from view display'),
      '#open' => TRUE,
      '#tree' => TRUE,
    ];

    foreach ($views as $view) {
      $form['views'][$view->id()] = [
        '#type' => 'details',
        '#title' => $this->t($view->label()),
        '#open' => FALSE,
        '#tree' => TRUE,
      ];

      foreach ($view->get('display') as $key => $display) {
        $form['views'][$view->id()][$key] = [
          '#type' => 'checkbox',
          '#title' => $this->t($display['display_title']),
          '#default_value' => FALSE,
        ];

        // Open the view details and set the default value to TRUE
        // if the view's page is excluded.
        if ($this->titleManager->isViewPageTitleExcluded($view->id(), $key)) {
          $form['views'][$view->id()]['#open'] = TRUE;
          $form['views'][$view->id()][$key]['#default_value'] = TRUE;
        }
      }
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('exclude_view_title.settings');
    $views = $form_state->getValues()['views'];

    // Get current configurations.
    $currentConfig = $config->getOriginal();

    // Removes views from the current configuration
    // if they have been removed from the administration.
    $deleted_views = array_diff_key($currentConfig, $views);
    if (!empty($deleted_views)) {
      foreach ($deleted_views as $view_key => $view) {
        $config->clear($view_key);
      }
    }

    // Changes the configuration if the views settings are modified.
    foreach ($views as $view_key => $pages) {
      // Add new views configurations.
      if (!isset($currentConfig[$view_key])) {
        foreach ($pages as $page => $value) {
          $config->set($view_key . '.' . $page, $value);
        }
        // Invalidate cache tags of the new views.
        $cache_tags = View::load($view_key)->getCacheTagsToInvalidate();
        $this->cacheTagsInvalidator->invalidateTags($cache_tags);
      }

      // Check for changes.
      $changedPages = array_diff_assoc($pages, $currentConfig[$view_key]);

      // Re-set the configuration of changed pages.
      foreach ($changedPages as $changedPage => $value) {
        $config->set($view_key . '.' . $changedPage, $value);

        // Invalidate cache tags of the changed views.
        $cache_tags = View::load($view_key)->getCacheTagsToInvalidate();
        $this->cacheTagsInvalidator->invalidateTags($cache_tags);
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
