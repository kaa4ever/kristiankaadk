<?php

namespace Drupal\page_layout\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class PageDeleteForm extends FormBase {
  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * PageDeleteForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   */
  public function __construct(ConfigFactoryInterface $configFactory, PageManagerInterface $pageManager, RouteBuilderInterface $routeBuilder) {
    $this->configFactory = $configFactory;
    $this->pageManager = $pageManager;
    $this->routeBuilder = $routeBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('page_layout.page_manager'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_layout_delete_page_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $page_id = NULL) {
    // Store the page for re-use in submit.
    $form_state->set('page_id', $page_id);

    $form['warning'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('This will delete the page. Are you sure?'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t('Confirm'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $page_id = $form_state->get('page_id');
    $this->configFactory->getEditable("page_layout.page.$page_id")->delete();
    $this->routeBuilder->rebuild();
    $form_state->setRedirect('page_layout.admin_page_list');
  }

}
