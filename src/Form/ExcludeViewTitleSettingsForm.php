<?php

namespace Drupal\exclude_view_title\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Cache\Cache;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\exclude_view_title\ExcludeViewTitleManagerInterface;
use Drupal\views\Views;

/**
 * Class ExcludeViewTitleSettingsForm.
 *
 * @package Drupal\exclude_view_title\Form
 */
class ExcludeViewTitleSettingsForm extends ConfigFormBase {

  /**
   * @var ExcludeViewTitleManagerInterface
   */
  private $titleManager;

  /**
   * Constructor.
   * ExcludeViewTitleSettingsForm constructor.
   * @param ConfigFactoryInterface $config_factory
   *  Config factory object.
   * @param ExcludeViewTitleManagerInterface $titleManager
   *  ExcludeViewTitleManager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ExcludeViewTitleManagerInterface $titleManager) {
    parent::__construct($config_factory);
    $this->titleManager = $titleManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('exclude_view_title.manager')
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

    foreach ($views as $view_key => $pages) {
      foreach ($pages as $page => $value) {
        $config->set('view.' . $view_key . ':' . $page, $value);
      }
    }
    $config->save();

    parent::submitForm($form, $form_state);

    foreach (Cache::getBins() as $service_id => $cache_backend) {
      $cache_backend->deleteAll();
    }
  }
}
