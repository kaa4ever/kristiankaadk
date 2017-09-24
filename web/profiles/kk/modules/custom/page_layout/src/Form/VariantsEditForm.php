<?php

namespace Drupal\page_layout\Form;

use Drupal\block\BlockInterface;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a class to build a listing of block entities.
 *
 * @see \Drupal\block\Entity\Block
 */
class VariantsEditForm extends FormBase {
  /**
   * @var string
   */
  protected $pageId;

  /**
   * @var array
   */
  protected $variant;

  /**
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * VariantsEditForm constructor.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $blockManager
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layoutPluginManager
   */
  public function __construct(BlockManagerInterface $blockManager, PageManagerInterface $pageManager, ConfigFactoryInterface $configFactory, LayoutPluginManagerInterface $layoutPluginManager) {
    $this->blockManager = $blockManager;
    $this->pageManager = $pageManager;
    $this->configFactory = $configFactory;
    $this->layoutPluginManager = $layoutPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('page_layout.page_manager'),
      $container->get('config.factory'),
      $container->get('plugin.manager.layout_plugin')

    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_layout_variants_edit_form';
  }

  /**
   * Get the title for the page being edited.
   *
   * @param string $page_id
   *
   * @return string
   */
  public function getTitle($page_id = NULL) {
    $page = $this->pageManager->getPage($page_id);
    return $page['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $page_id = NULL, $variant_id = NULL, Request $request = NULL) {
    $this->pageId = $page_id;
    $this->request = $request;
    $this->variant = $this->pageManager->getVariant($page_id, $variant_id);

    $form['#tree'] = TRUE;
    $form['#attached']['library'][] = 'page_layout/admin';
    $form['#attached']['library'][] = 'block/drupal.block';
    $form['#attached']['library'][] = 'block/drupal.block.admin';

    $form['advanced'] = [
      '#type' => 'vertical_tabs',
      '#title' => $this->variant['title'],
      '#description' => $this->t('Here you can edit how to display the page, when to display it, and you can add content onto the page.<br />You can add more "variants" to the page, if e.g. you what to display two node types in a different way.'),
      '#title_display' => 'invisible',
      '#description_display' => 'before',
    ];

    if ($this->variant) {
      $form['settings'] = [
        '#type' => 'details',
        '#open' => FALSE,
        '#title' => $this->t('Settings'),
        '#description' => $this->t('Update the settings for this variant.'),
        '#group' => 'advanced',
      ];
      $this->buildSettingsForm($form['settings']);

      $form['conditions'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Conditions'),
        '#description' => $this->t('Specify the conditions for when this variant should be selected.'),
        '#group' => 'advanced',
      ];
      $this->buildConditionsForm($form['conditions']);

      $form['content'] = [
        '#type' => 'details',
        '#open' => TRUE,
        '#title' => $this->t('Content'),
        '#description' => $this->t('Update the content for this variant, by adding, removing or re-ordering blocks.'),
        '#group' => 'advanced',
      ];
      $this->buildContentForm($form['content']);

      $form['actions'] = [
        '#type' => 'actions',
      ];

      $form['actions']['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save variant'),
        '#button_type' => 'primary',
      ];
    }

    return $form;
  }

  /**
   * Build the settings form for the variant.
   *
   * @param array $container
   *   A render-able container array.
   */
  protected function buildSettingsForm(array &$container) {
    $container['admin_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administration title'),
      '#description' => $this->t('The title displayed in the tabs, if the page has multiple variants.'),
      '#required' => TRUE,
      '#default_value' => $this->variant['admin_title'],
    ];

    $container['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Page title'),
      '#description' => $this->t('The title displayed on the page for this variant.<br />If an existing page is overwritten, leave this empty to use the original title.'),
      '#default_value' => $this->variant['title'],
    ];

    $options = [];
    foreach ($this->layoutPluginManager->getDefinitions() as $id => $definition) {
      $option = $definition['label'];
      if ($definition['icon']) {
        $option = '<img src="/' . $definition['icon'] . '" height="100" width="auto" />' . $option;
      }
      $options[$id] = $option;
    }

    $container['layout'] = [
      '#type' => 'radios',
      '#title' => $this->t('Layout'),
      '#description' => $this->t('Select the layout to use for this variant.'),
      '#options' => $options,
      '#required' => TRUE,
      '#default_value' => $this->variant['layout'],
      '#attributes' => [
        'class' => ['page-layout-selector'],
        'data-layout' => $this->variant['id'],
      ],
      '#ajax' => [
        'callback' => [$this, 'variantUpdateLayout'],
      ],
    ];

    $container['actions'] = [
      '#type' => 'actions',
    ];

    $container['actions']['delete'] = [
      '#type' => 'link',
      '#title' => $this->t('Delete variant'),
      '#url' => Url::fromRoute('page_layout.admin_variants_delete', ['page_id' => $this->pageId, 'variant_id' => $this->variant['id']]),
      '#attributes' => [
        'class' => ['button', 'button--danger', 'use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 700]),
      ],
    ];
  }

  /**
   * Callback for selecting a layout.
   *
   * @param array $form
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   */
  public function variantUpdateLayout($form, FormStateInterface $form_state) {
    // Get the selections from the form.
    $selected_layout = $form_state->getTriggeringElement()['#value'];

    // Get the first available region for the selected layout.
    $layout = $this->layoutPluginManager->getDefinition($selected_layout);
    $available_region = key(array_slice($layout['regions'], 0, 1));

    // Update the layout to the selected.
    $this->variant['layout'] = $selected_layout;

    // Move all blocks from the current layout into the first available region
    // in the new selected layout.
    foreach ($this->variant['blocks'] as &$block) {
      $block['region'] = $available_region;
    }

    // Save the changes.
    $this->pageManager->saveVariant($this->pageId, $this->variant);

    // Issue a refresh.
    drupal_set_message($this->t('The layout was updated.'), 'status');
    $url = Url::fromRoute('page_layout.admin_variants_edit',
      [
        'page_id' => $this->pageId,
        'variant_id' => $this->variant['id'],
      ],
      [
        'fragment' => "edit-settings",
        'query' => [
          // Add a unique query parameter to force a page reload.
          'force' => time(),
        ],
      ])->toString();

    $response = new AjaxResponse();
    $response->addCommand(new RedirectCommand($url));
    return $response;
  }

  /**
   * Build the conditions table for the variant.
   *
   * @param array $container
   *   The render-able container to extend.
   */
  protected function buildConditionsForm(array &$container) {
    $container['actions'] = [
      '#type' => 'actions',
    ];

    $container['table'] = [
      '#type' => 'table',
      '#empty' => $this->t('No conditions added.'),
      '#header' => [
        $this->t('Name'),
        $this->t('Operations'),
      ],
    ];

    foreach ($this->variant['conditions'] as $key => $condition) {
      $container['table'][$key]['name'] = [
        '#markup' => $condition['plugin'],
      ];
      $container['table'][$key]['operations'] = [
        '#type' => 'operations',
        '#links' => [],
      ];
      $container['table'][$key]['operations']['#links']['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('page_layout.admin_condition_add',
          [
            'page_id' => $this->pageId,
            'variant_id' => $this->variant['id'],
            'condition_id' => $condition['id'],
          ]
        ),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => 700]),
        ],
      ];
      $container['table'][$key]['operations']['#links']['remove'] = [
        'title' => $this->t('Remove'),
        'url' => Url::fromRoute('page_layout.admin_condition_remove',
          [
            'page_id' => $this->pageId,
            'variant_id' => $this->variant['id'],
            'condition_id' => $condition['id'],
          ]
        ),
        'attributes' => [
          'class' => ['use-ajax'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => 700]),
        ],
      ];
    }

    $container['actions']['add'] = [
      '#type' => 'link',
      '#title' => $this->t('Add condition'),
      '#url' => Url::fromRoute('page_layout.admin_condition_add', ['page_id' => $this->pageId, 'variant_id' => $this->variant['id']]),
      '#attributes' => [
        'class' => ['button', 'use-ajax'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => Json::encode(['width' => 700]),
      ],
    ];
  }

  /**
   * Build a block table for the variant.
   *
   * @param array $container
   *   The render-able container to extend.
   */
  protected function buildContentForm(array &$container) {
    // Build blocks first for each region.
    $blocks = $this->getBlocksByRegion($this->variant);

    $container['table'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Block'),
        $this->t('Category'),
        $this->t('Region'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#attributes' => [
        'id' => 'blocks',
      ],
    ];

    // Weights range from -delta to +delta, so delta should be at least half
    // of the amount of blocks present. This makes sure all blocks in the same
    // region get an unique weight.
    $weight_delta = isset($this->variant['blocks']) ? round(count($this->variant['blocks']) / 2) : 10;

    $placement = FALSE;
    if ($this->request->query->has('block-placement')) {
      $placement = $this->request->query->get('block-placement');
      $container['table']['#attached']['drupalSettings']['blockPlacement'] = $placement;
    }

    // Get the layout selected for the page.
    $layout = $this->layoutPluginManager->getDefinition($this->variant['layout']);
    foreach ($layout['regions'] as $region_id => $region) {
      $container['table']['#tabledrag'][] = [
        'action' => 'match',
        'relationship' => 'sibling',
        'group' => 'block-region-select',
        'subgroup' => 'block-region-' . $region_id,
        'hidden' => FALSE,
      ];
      $container['table']['#tabledrag'][] = [
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'block-weight',
        'subgroup' => 'block-weight-' . $region_id,
      ];

      $container['table']['region-' . $region_id] = [
        '#attributes' => [
          'class' => ['region-title', 'region-title-' . $region_id],
          'no_striping' => TRUE,
        ],
      ];
      $container['table']['region-' . $region_id]['title'] = [
        '#theme_wrappers' => [
          'container' => [
            '#attributes' => ['class' => 'region-title__action'],
          ],
        ],
        '#prefix' => $region['label'],
        '#type' => 'link',
        '#title' => $this->t('Place block', ['%region' => $region['label']]),
        '#url' => Url::fromRoute('page_layout.admin_block_library', ['page_id' => $this->pageId, 'variant_id' => $this->variant['id']], ['query' => ['region' => $region_id]]),
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
        '#attributes' => [
          'class' => ['use-ajax', 'button', 'button--small'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'width' => 700,
          ]),
        ],
      ];

      $container['table']['region-' . $region_id . '-message'] = [
        '#attributes' => [
          'class' => [
            'region-message',
            'region-' . $region_id . '-message',
            empty($blocks[$region_id]) ? 'region-empty' : 'region-populated',
          ],
        ],
      ];
      $container['table']['region-' . $region_id . '-message']['message'] = [
        '#markup' => '<em>' . $this->t('No blocks in this region') . '</em>',
        '#wrapper_attributes' => [
          'colspan' => 5,
        ],
      ];

      if (isset($blocks[$region_id])) {
        foreach ($blocks[$region_id] as $block) {
          $key = $block['id'];

          try {
            // Load information about the plugin.
            $info = $this->blockManager->getDefinition($block['plugin']);

            $container['table'][$key] = [
              '#attributes' => [
                'class' => ['draggable'],
              ],
            ];

            if ($placement && $placement == Html::getClass($block['plugin'])) {
              $container['table'][$key]['#attributes']['class'][] = 'color-success';
              $container['table'][$key]['#attributes']['class'][] = 'js-block-placed';
            }
            $container['table'][$key]['info'] = [
              '#plain_text' => $info['admin_label'],
              '#wrapper_attributes' => [
                'class' => ['block'],
              ],
            ];
            $container['table'][$key]['type'] = [
              '#markup' => $info['category'],
            ];
            $container['table'][$key]['region'] = [
              '#type' => 'select',
              '#default_value' => $region_id,
              '#empty_value' => BlockInterface::BLOCK_REGION_NONE,
              '#title' => $this->t('Region for @block block', ['@block' => $info['admin_label']]),
              '#title_display' => 'invisible',
              '#options' => $this->getRegionOptions($layout),
              '#attributes' => [
                'class' => ['block-region-select', 'block-region-' . $region_id],
              ],
            ];
            $container['table'][$key]['weight'] = [
              '#type' => 'weight',
              '#default_value' => $block['weight'],
              '#delta' => $weight_delta,
              '#title' => $this->t('Weight for @block block', ['@block' => $info['admin_label']]),
              '#title_display' => 'invisible',
              '#attributes' => [
                'class' => ['block-weight', 'block-weight-' . $region_id],
              ],
            ];
            $container['table'][$key]['operations']['delete'] = [
              '#type' => 'link',
              '#title' => $this->t('Remove'),
              '#url' => Url::fromRoute('page_layout.admin_block_remove', [
                'plugin_id' => $block['plugin'],
                'page_id' => $this->pageId,
                'variant_id' => $this->variant['id'],
              ], [
                'query' => [
                  'weight' => $block['weight'],
                  'region' => $region_id,
                ],
              ]),
              '#attributes' => [
                'class' => ['use-ajax'],
                'data-dialog-type' => 'modal',
                'data-dialog-options' => Json::encode([
                  'width' => 700,
                ]),
              ],
            ];
          }
          catch (PluginNotFoundException $e) {
            $this->removeBlock($block['id']);

            drupal_set_message($this->t('A block with ID :id did not exist and was removed', [
              ':id' => $block['id'],
            ]), 'warning');
          }
        }
      }
    }
  }

  /**
   * Removes a block from the current variant.
   *
   * @param string $blockId
   *
   * @throws PluginNotFoundException
   */
  private function removeBlock($blockId) {
    $found = FALSE;

    foreach ($this->variant['blocks'] as $key => $block) {
      if ($block['id'] === $blockId) {
        $found = $key;
        break;
      }
    }

    if ($found !== FALSE) {
      unset($this->variant['blocks'][$found]);
      $this->pageManager->saveVariant($this->pageId, $this->variant);
    }
    else {
      throw new PluginNotFoundException($blockId, "Could not find a block with $blockId on current variant");
    }
  }

  /**
   * Iterate all blocks defined on the page.
   *
   * Split the blocks into the regions they exists in.
   *
   * @param array $variant
   *   The variant to build blocks for.
   *
   * @return array
   *   An array with region as key, and block IDs as values.
   */
  protected function getBlocksByRegion($variant) {
    $regions = [];
    foreach ($variant['blocks'] as $block) {
      $block['weight'] = $block['weight'] ?: 0;
      $regions[$block['region']][] = $block;
    }
    // Sort blocks by weight, ascending order.
    foreach ($regions as &$region) {
      uasort($region, function ($a, $b) {
        if ($a['weight'] === $b['weight']) {
          return 0;
        }
        return $a['weight'] < $b['weight'] ? -1 : 1;
      });
    }
    return $regions;
  }

  /**
   * Get regions for an option element.
   *
   * @param array $layout
   *   The layout configuration.
   *
   * @return array
   *   Regions for option.
   */
  protected function getRegionOptions($layout) {
    $regions = [];
    foreach ($layout['regions'] as $id => $region) {
      $regions[$id] = $region['label'];
    }
    return $regions;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Update the title of the variant.
    $this->variant['admin_title'] = $form_state->getValue('settings')['admin_title'];
    $this->variant['title'] = $form_state->getValue('settings')['title'];

    // Update the weights for the blocks defined.
    foreach ($this->variant['blocks'] as &$block) {
      $block['weight'] = $form_state->getValue('content')['table'][$block['id']]['weight'];
      $block['region'] = $form_state->getValue('content')['table'][$block['id']]['region'];
    }

    $this->pageManager->saveVariant($this->pageId, $this->variant);

    drupal_set_message($this->t('All changes was saved.'));
  }

}
