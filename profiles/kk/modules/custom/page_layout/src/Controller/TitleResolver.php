<?php

namespace Drupal\page_layout\Controller;

use Drupal\Core\Controller\ControllerResolverInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Controller\TitleResolver as CoreTitleResolver;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 *
 */
class TitleResolver extends CoreTitleResolver {
  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   *
   */
  public function __construct(ControllerResolverInterface $controller_resolver, TranslationInterface $string_translation, PageManagerInterface $pageManager) {
    parent::__construct($controller_resolver, $string_translation);
    $this->pageManager = $pageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getTitle(Request $request, Route $route) {
    if ($route->getDefault('_controller') === '\Drupal\page_layout\Controller\RouteController::render') {
      $page_id = $route->getDefault('page_id');
      $variant = $this->pageManager->getMatchingVariant($page_id);
      // If no variants exists,
      // do not use the PageLayouts RouteController title callback.
      if (!$variant || empty($variant['title'])) {
        $route->setDefault('_title_callback', FALSE);
        // If the original route has a title callback defined, set this.
        if ($callback = $route->getDefault('original_title_callback')) {
          $route->setDefault('_title_callback', $callback);
        }
      }
    }

    // Use the core resolver to get the title.
    return parent::getTitle($request, $route);
  }

}
