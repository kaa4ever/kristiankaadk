<?php

namespace Drupal\page_layout\Routing;

use Drupal\page_layout\PageManagerInterface;
use Symfony\Cmf\Component\Routing\NestedMatcher\RouteFilterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 *
 */
class VariantRouteFilter implements RouteFilterInterface {
  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * VariantRouteFilter constructor.
   *
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   */
  public function __construct(PageManagerInterface $pageManager) {
    $this->pageManager = $pageManager;
  }

  /**
   * {@inheritdoc}
   *
   * Filter down the route collection to include a route from page_layout, but only if a variant fits.
   */
  public function filter(RouteCollection $collection, Request $request) {
    $routes = $collection->all();
    $found = FALSE;

    // When we are iterating available routes, we temporary store the parameters for all other routes than the
    // Page Layout routes, to set them on the Page Layout route, if the following search will find one.
    $existingRoutesParameters = [];

    // Iterate the RouteCollection and check if one of the routes is a Page Layout route.
    foreach ($routes as $name => $routeInCollection) {
      $isPageLayoutRoute = strpos($name, 'page_layout_dynamic.') === 0;
      /** @var \Symfony\Component\Routing\Route $routeInCollection */
      if ($isPageLayoutRoute) {
        // Check if the found Page Layout route has a valid variant for the request.
        // Since the filter is run before any contexts is set, we should not check the conditions.
        if ($this->pageManager->getMatchingVariant($routeInCollection->getDefault('page_id'), FALSE)) {
          $found = ['name' => $name, 'route' => $routeInCollection];
        }
        else {
          // If no variant was found, remove filter out the Page Layout route from the collection,
          // since this should not be selected for rendering.
          $collection->remove($name);
        }
      }

      if (!$isPageLayoutRoute && $parameters = $routeInCollection->getOption('parameters')) {
          $existingRoutesParameters = array_merge($existingRoutesParameters, $parameters);
      }
    }

    // If a Page Layout route was found, return a route collection only with this route, so it will always be
    // selected for render.
    if ($found) {
      // Set the original parameters for the overridden route.
      $found['route']->setOption('parameters', $existingRoutesParameters);

      $collection = new RouteCollection();
      $collection->add($found['name'], $found['route']);
    }

    return $collection;
  }

}
