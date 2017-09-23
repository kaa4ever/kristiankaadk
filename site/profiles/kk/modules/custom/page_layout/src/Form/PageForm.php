<?php

namespace Drupal\page_layout\Form;

use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Url;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class PageForm extends FormBase {
  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * PageForm constructor.
   *
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   */
  public function __construct(PageManagerInterface $pageManager, RouteBuilderInterface $routeBuilder) {
    $this->pageManager = $pageManager;
    $this->routeBuilder = $routeBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('page_layout.page_manager'),
      $container->get('router.builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_layout_page_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $page_id = NULL) {
    $form['#attached']['library'][] = 'page_layout/admin';

    $page = FALSE;
    if ($page_id) {
      $form_state->set('page_id', $page_id);
      $page = $this->pageManager->getPage($page_id);
    }

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
      '#default_value' => $page ? $page['title'] : '',
    ];

    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#description' => $this->t('Define the path for the new page. Use % for wildcards, e.g. /node/%'),
      '#required' => TRUE,
      '#default_value' => $page ? $page['path'] : '',
      '#ajax' => [
        'callback' => [$this, 'validatePathByAjax'],
        'event' => 'change',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $page ? $this->t('Save changes') : $this->t('Create page'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Validate the path input.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function validatePathByAjax(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    // Clear any previous errors.
    $response->addCommand(new RemoveCommand('.form-item--error-message'));
    $response->addCommand(new InvokeCommand(".form-item-path input", 'removeClass', ['error']));

    if ($message = $this->validatePath($form_state)) {
      $response->addCommand(new AfterCommand(".form-item-path input", [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['form-item--error-message'],
        ],
        '#children' => [
          '#type' => 'html_tag',
          '#tag' => 'strong',
          '#value' => $this->t($message),
        ],
      ]));
      $response->addCommand(new InvokeCommand(".form-item-path input", 'addClass', ['error']));
    }

    return $response;
  }

  /**
   * Validate the path.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return bool|string
   *   Either FALSE for no errors, or an error string.
   */
  private function validatePath(FormStateInterface $form_state) {
    $page_id = $form_state->get('page_id') ?: FALSE;
    $path = $form_state->getValue('path');

    if (strlen($path) === 0) {
      return 'Path is required';
    }

    // Prepare the path.
    $path = $this->preparePath($path);

    // Make sure the path does not exist already.
    // If a page is being edited, the path can existing, but only on
    // the page being edited.
    $existing = $this->pathExists($path);
    if ($existing && $existing !== $page_id) {
      return 'The path already exists';
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Validate the path.
    if ($message = $this->validatePath($form_state)) {
      $form_state->setError($form['path'], $message);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Either update an existing page, or create a new one.
    if ($form_state->has('page_id')) {
      $url = $this->submitEditPage($form_state);
    }
    else {
      drupal_set_message($this->t('Page was successfully created. You should now either add blocks to the default variant, or create new variants..'), 'status');
      $url = $this->submitCreatePage($form_state);
    }

    $this->routeBuilder->rebuild();

    $form_state->setRedirectUrl($url);
  }

  /**
   * Edit a page.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\GeneratedUrl|string
   */
  private function submitEditPage(FormStateInterface $form_state) {
    $page_id = $form_state->get('page_id');
    $page = $this->pageManager->getPage($page_id);

    $page['title'] = $form_state->getValue('title');
    $page['path'] = $this->preparePath($form_state->getValue('path'));

    $this->pageManager->updatePage($page);

    return Url::fromRoute('page_layout.admin_page_list');
  }

  /**
   * Create a new page.
   *
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\GeneratedUrl|string
   */
  private function submitCreatePage(FormStateInterface $form_state) {
    $title = $form_state->getValue('title');
    $path = $this->preparePath($form_state->getValue('path'));
    $id = $this->pageManager->createPage($title, $path);

    $this->pageManager->createVariant($id, 'Default', $title);

    return Url::fromRoute('page_layout.admin_variants_edit', ['page_id' => $id]);
  }

  /**
   * Check if the path already exists.
   *
   * If the path does exist, return the ID of that page.
   *
   * @param string $path
   *
   * @return bool|int
   *   Either FALSE if it doesn't exists, or the page ID.
   */
  private function pathExists($path) {
    $existing = $this->pageManager->getPageByPath($path);
    if ($existing) {
      return $existing['id'];
    }
    return FALSE;
  }

  /**
   * Prepare a path.
   *
   * @param string $path
   *
   * @return string
   */
  private function preparePath($path) {
    // Make sure the path starts with a slash.
    if ($path[0] !== '/') {
      $path = "/{$path}";
    }

    return $path;
  }

}
