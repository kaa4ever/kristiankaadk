<?php

namespace Drupal\page_layout\Plugin\Block;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteProvider;
use Drupal\Core\Entity\HtmlEntityFormController;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides an 'Existing content' Block.
 *
 * @Block(
 *   id = "page_layout_existing_content",
 *   admin_label = @Translation("Existing content"),
 * )
 */
class ExistingContentBlock extends BlockBase implements ContainerFactoryPluginInterface {
  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * @var \Drupal\Core\Routing\RouteProviderInterface
   */
  protected $routeProvider;

  /**
   * @var \Drupal\Core\Entity\HtmlEntityFormController
   */
  protected $htmlEntityFormController;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * ExistingContentBlock constructor.
   *
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   * @param \Drupal\Core\Routing\CurrentRouteMatch $currentRouteMatch
   * @param \Drupal\Core\Routing\RouteProvider $routeProvider
   * @param \Drupal\Core\Entity\HtmlEntityFormController $htmlEntityFormController
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   */
  public function __construct(
        array $configuration,
        $plugin_id,
        $plugin_definition,
        RequestStack $requestStack,
        CurrentRouteMatch $currentRouteMatch,
        RouteProvider $routeProvider,
        HtmlEntityFormController $htmlEntityFormController,
        LoggerChannelFactoryInterface $logger,
        EntityTypeManagerInterface $entityTypeManager,
        FormBuilderInterface $formBuilder
    ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $requestStack;
    $this->currentRouteMatch = $currentRouteMatch;
    $this->routeProvider = $routeProvider;
    $this->htmlEntityFormController = $htmlEntityFormController;
    $this->logger = $logger->get('page_layout');
    $this->entityTypeManager = $entityTypeManager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
        $configuration,
        $plugin_id,
        $plugin_definition,
        $container->get('request_stack'),
        $container->get('current_route_match'),
        $container->get('router.route_provider'),
        $container->get('controller.entity_form'),
        $container->get('logger.factory'),
        $container->get('entity_type.manager'),
        $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $request = $this->requestStack->getCurrentRequest();

    $originalRoute = FALSE;
    foreach ($this->routeProvider->getRouteCollectionForRequest($request) as $name => $route) {
      if (strpos($name, 'page_layout_dynamic.') !== 0) {
        $originalRoute = ['name' => $name, 'route' => $route];
        break;
      }
    }

    if ($originalRoute) {

      try {
        // Iterate all route and try to load an entity (node, user) from them.
        foreach ($this->currentRouteMatch->getParameters() as $key => $value) {
          if ($value instanceof Entity) {
            $view_builder = $this->entityTypeManager->getViewBuilder($key);
            return $view_builder->view($value);
          }
        }

        // If the route results in a form render it.
        if ($form = $originalRoute['route']->getDefault('_form')) {
          return $this->formBuilder->getForm($form);
        }

        // Last resort is trying to render an EntityForm.
        $routeMatch = new RouteMatch($originalRoute['name'], $originalRoute['route']);
        return $this->htmlEntityFormController->getContentResult($request, $routeMatch);
      }
      catch (EnforcedResponseException $e) {
        // If the compiling of the block throws a response, make it possible for the RouteController to
        // respond to it, be re-throwing the exception.
        throw $e;
      }
      catch (\Exception $e) {
        $this->logger->error($e->getMessage());
        return $this->notFound();
      }
    }

    return $this->notFound();
  }

  /**
   * Content for the block if none existing content could be found.
   */
  private function notFound() {
    return ['#markup' => '<p>' . $this->t('Failed to load existing content') . '</p>'];
  }

}
