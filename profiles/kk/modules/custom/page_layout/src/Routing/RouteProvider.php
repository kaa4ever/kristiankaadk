<?php

namespace Drupal\page_layout\Routing;

use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Database\Connection;

/**
 *
 */
class RouteProvider {
  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   *
   */
  public function __construct(PageManagerInterface $pageManager, Connection $connection) {
    $this->pageManager = $pageManager;
    $this->connection = $connection;
  }

  /**
   * @return \Symfony\Component\Routing\RouteCollection
   */
  public function routes() {
    $routeCollection = new RouteCollection();

    foreach ($this->pageManager->getPages() as $page) {
      // Generate a path where all generics (*) are converted to placeholders
      // ({placeholder*}).
      $path = $this->fromPatternOutlineToPath($page['path']);

      $route = new Route($path);

      $route->setDefault('_controller', '\Drupal\page_layout\Controller\RouteController::render');
      $route->setDefault('_title_callback', '\Drupal\page_layout\Controller\RouteController::title');
      $route->setRequirement('_access', 'TRUE');
      $route->setDefault('page_id', $page['id']);

      $routeCollection->add("page_layout_dynamic.{$page['id']}", $route);
    }

    return $routeCollection;
  }

  /**
   * Replace all generic definitions (*) with a placeholder ({placeholder}).
   *
   * @param string $patternOutline
   *   The pattern to transform.
   *
   * @return string
   *   The transformed path.
   */
  private function fromPatternOutlineToPath($patternOutline) {
    // If a route are being overridden, use the path from the original route.
    if ($path = $this->getRouteFromPatternOutline($patternOutline)) {
      return $path;
    }

    $count = 0;
    // Split the path definition into parts.
    $path_parts = explode('/', $patternOutline);
    // Replace all generic definitions (*), with a placeholder.
    $path_parts = array_map(function ($part) use (&$count) {
      if ($part === '%') {
        $count++;
        return "{placeholder$count}";
      }
      return $part;
    }, $path_parts);
    // Rebuild a path to use for looking up.
    return implode('/', $path_parts);
  }

  /**
   * Do a lookup for a path from a pattern outline.
   *
   * @param string $patternOutline
   *
   * @return string|null
   */
  private function getRouteFromPatternOutline($patternOutline) {
    return $this->connection
      ->select('router', 'r')
      ->fields('r', ['path'])
      ->condition('r.pattern_outline', $patternOutline)
      ->execute()
      ->fetchField(0);
  }

}
