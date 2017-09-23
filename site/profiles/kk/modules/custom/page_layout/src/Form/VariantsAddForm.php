<?php

namespace Drupal\page_layout\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 *
 */
class VariantsAddForm extends FormBase {
  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * @var \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface
   */
  protected $layoutPluginManager;

  /**
   * VariantsAddForm constructor.
   *
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   * @param \Drupal\layout_plugin\Plugin\Layout\LayoutPluginManagerInterface $layoutPluginManager
   */
  public function __construct(PageManagerInterface $pageManager, LayoutPluginManagerInterface $layoutPluginManager) {
    $this->pageManager = $pageManager;
    $this->layoutPluginManager = $layoutPluginManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('page_layout.page_manager'),
      $container->get('plugin.manager.layout_plugin')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_layout_variants_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $page_id = NULL) {
    $form_state->set('page_id', $page_id);

    $form['#attached']['library'][] = 'page_layout/admin';

    $form['admin_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Administration title'),
      '#description' => $this->t('The title displayed in the tabs, if the page has multiple variants.'),
      '#required' => TRUE,
    ];

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#description' => $this->t('The title displayed on the page for this variant.'),
    ];

    $options = [];
    foreach ($this->layoutPluginManager->getDefinitions() as $id => $definition) {
      $option = $definition['label'];
      if ($definition['icon']) {
        $option = '<img src="/' . $definition['icon'] . '" height="100" width="auto" />' . $option;
      }
      $options[$id] = $option;
    }

    $form['layout'] = [
      '#type' => 'radios',
      '#title' => $this->t('Layout'),
      '#description' => $this->t('Select the layout to use for this variant.'),
      '#options' => $options,
      '#required' => TRUE,
      '#attributes' => [
        'class' => ['page-layout-selector'],
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create variant'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $page_id = $form_state->get('page_id');
    $admin_title = $form_state->getValue('admin_title');
    $title = $form_state->getValue('title');
    $layout = $form_state->getValue('layout');

    $variant_id = $this->pageManager->createVariant($page_id, $admin_title, $title, $layout);

    drupal_set_message($this->t('Variant was successfully created. You should now add content into the regions of the variant.'), 'status');

    $form_state->setRedirect('page_layout.admin_variants_edit', ['page_id' => $page_id, 'variant_id' => $variant_id], ['fragment' => "edit-content"]);
  }

}
