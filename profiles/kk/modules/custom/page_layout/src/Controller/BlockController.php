<?php

namespace Drupal\page_layout\Controller;

use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\block\Controller\BlockLibraryController;
use Drupal\Core\Url;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Plugin\Context\LazyContextRepository;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Menu\LocalActionManagerInterface;

/**
 * Class BlockLibraryController.
 *
 * Since adding blocks to a page in Page Layout is the same as adding
 * blocks to core Block Layout,
 * this controller extends the BlockLibraryController
 * from block module, to re-use as much functionality as possible.
 *
 * @package Drupal\page_layout\Controller
 */
class BlockController extends BlockLibraryController {
  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * BlockController constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   * @param \Drupal\Core\Plugin\Context\LazyContextRepository $context_repository
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   * @param \Drupal\Core\Menu\LocalActionManagerInterface $local_action_manager
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   */
  public function __construct(BlockManagerInterface $block_manager, LazyContextRepository $context_repository, RouteMatchInterface $route_match, LocalActionManagerInterface $local_action_manager, PageManagerInterface $pageManager) {
    parent::__construct($block_manager, $context_repository, $route_match, $local_action_manager);
    $this->pageManager = $pageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('context.repository'),
      $container->get('current_route_match'),
      $container->get('plugin.manager.menu.local_action'),
      $container->get('page_layout.page_manager')
    );
  }

  /**
   * List all available blocks.
   *
   * @param string $page_id
   * @param string $variant_id
   * @param \Symfony\Component\HttpFoundation\Request $request
   *
   * @return array
   */
  public function listBlocksForPage($page_id, $variant_id, Request $request) {
    $blocks = parent::listBlocks($request, '');
    // Update the add link for all rows,
    // one row represents a block, in the list.
    foreach ($blocks['blocks']['#rows'] as $index => $row) {
      $url = $row['operations']['data']['#links']['add']['url'];
      // Get the plugin ID of the current row from the original add route.
      $parameters = $url->getRouteParameters();
      $pluginId = $parameters['plugin_id'];

      // Unset the "system_main_block",
      // since adding this plugin would make a recursive request.
      if ($pluginId === 'system_main_block') {
        unset($blocks['blocks']['#rows'][$index]);
      }
      else {
        // Update the route for the row.
        $blocks['blocks']['#rows'][$index]['operations']['data']['#links']['add']['url'] = Url::fromRoute('page_layout.admin_block_add',
          [
            'plugin_id' => $pluginId,
            'page_id' => $page_id,
            'variant_id' => $variant_id,
          ]
        );
      }
    }
    return $blocks;
  }

  /**
   * Build the block instance add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the block instance.
   * @param string $page_id
   * @param string $variant_id
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function addBlock($plugin_id, $page_id, $variant_id, Request $request) {
    // Figure out which region to add the block to.
    $region = $request->query->has('region') ? $request->query->get('region') : 'disabled';

    $variant = $this->pageManager->getVariant($page_id, $variant_id);

    if ($variant) {
      // Add the block information to the page.
      $variant['blocks'][] = [
        'id' => uniqid(),
        'plugin' => $plugin_id,
        'region' => $region,
        'weight' => 0,
      ];
    }

    $this->pageManager->saveVariant($page_id, $variant);

    drupal_set_message($this->t('The block was added to the region.'), 'status');
    $url = Url::fromRoute('page_layout.admin_variants_edit', [
      'page_id' => $page_id,
      'variant_id' => $variant_id,
    ], [
      'fragment' => "edit-content",
      'query' => [
        'block-placement' => Html::getClass($plugin_id),
      ],
    ])->toString();

    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));
    return $response;
  }

}
