<?php

namespace Drupal\page_layout\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Form\EnforcedResponseException;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 *
 */
class RouteController extends ControllerBase {
  /**
   * @var \Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * RouteController constructor.
   *
   * @param \Drupal\Core\Template\TwigEnvironment $twig
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layoutPluginManager
   */
  public function __construct(
        TwigEnvironment $twig,
        BlockManagerInterface $blockManager,
        PageManagerInterface $pageManager,
        LayoutPluginManagerInterface $layoutPluginManager
    ) {
    $this->twig = $twig;
    $this->blockManager = $blockManager;
    $this->pageManager = $pageManager;
    $this->layoutPluginManager = $layoutPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('twig'),
        $container->get('plugin.manager.block'),
        $container->get('page_layout.page_manager'),
        $container->get('plugin.manager.layout_plugin')
    );
  }

  /**
   * Get a title for the page.
   *
   * @param string $page_id
   *
   * @return string
   */
  public function title($page_id) {
    $variant = $this->pageManager->getMatchingVariant($page_id);

    if (strlen($variant['title']) === 0) {
      $page = $this->pageManager->getPage($page_id);
      return $page['title'];
    }

    return $variant['title'];
  }

  /**
   * Render a dynamic route.
   *
   * @param string $page_id
   *
   * @return array
   *   A render-able array.
   */
  public function render($page_id) {
    $variant = $this->pageManager->getMatchingVariant($page_id);

    // If a variant does not exists for the route,
    // it means that a Page Layout is defined
    // for the route, but no variants fit the current context.
    // Therefore return false.
    if (!$variant) {
      throw new NotFoundHttpException();
    }

    // Load the layout selected in the variant.
    $layout = $this->layoutPluginManager->getDefinition($variant['layout']);

    // Prepare the content object for the template.
    $blocks = isset($variant['blocks']) ? $variant['blocks'] : [];
    $content = [];

    // Build the regions of the content object.
    // The regions must be available in the template,
    // since a template does not know if it's empty or not.
    foreach ($layout['regions'] as $id => $region) {
      $content[$id] = [];
    }

    // Order blocks by weight.
    usort($blocks, function ($a, $b) {
      if ($a['weight'] == $b['weight']) {
          return 0;
      }
      return ($a['weight'] < $b['weight']) ? -1 : 1;
    });

    try {
      foreach ($blocks as $block) {
        $instance = $this->blockManager->createInstance($block['plugin']);
        $content[$block['region']][] = $instance->build();
      }
    }
    catch (EnforcedResponseException $e) {
      return $e->getResponse();
    }

    $layoutBuilder = $this->layoutPluginManager->createInstance($variant['layout']);

    return $layoutBuilder->build($content);
  }

}
