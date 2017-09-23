<?php

namespace Drupal\page_layout\Form;

use Drupal\Core\Condition\ConditionManager;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\page_layout\PageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 *
 */
class ConditionAddForm extends FormBase {
  /**
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * @var \Drupal\page_layout\PageManagerInterface
   */
  protected $pageManager;

  /**
   * PageAddSelectionRuleForm constructor.
   *
   * @param \Drupal\Core\Condition\ConditionManager $conditionManager
   * @param \Drupal\page_layout\PageManagerInterface $pageManager
   */
  public function __construct(ContextRepositoryInterface $contextRepository, ConditionManager $conditionManager, PageManagerInterface $pageManager) {
    $this->contextRepository = $contextRepository;
    $this->conditionManager = $conditionManager;
    $this->pageManager = $pageManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('context.repository'),
      $container->get('plugin.manager.condition'),
      $container->get('page_layout.page_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'page_layout_condition_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $page_id = NULL, $variant_id = NULL, Request $request = NULL) {
    $form['#tree'] = TRUE;

    // Store the page ID and Variant for the submit function.
    $form_state->set('page_id', $page_id);
    $form_state->set('variant_id', $variant_id);

    // Store the gathered contexts in the form state for other objects to use
    // during form building.
    $form_state->setTemporaryValue('gathered_contexts', $this->contextRepository->getAvailableContexts());

    $condition = FALSE;
    if ($request->query->has('condition_id')) {
      $form_state->set('condition_id', $request->query->get('condition_id'));
      $condition = $this->pageManager->getCondition($page_id, $variant_id, $request->query->get('condition_id'));
    }

    // Get all condition plugins defined.
    $plugins = array_map(function ($plugin) {
      return $plugin['id'];
    }, $this->conditionManager->getDefinitions());

    $form['condition'] = [
      '#type' => 'select',
      '#required' => TRUE,
      '#title' => $this->t('Condition'),
      '#options' => $plugins,
      '#default_value' => $condition ? $condition['plugin'] : NULL,
      '#ajax' => [
        'callback' => [$this, 'conditionSelected'],
        'wrapper' => 'condition-configuration-wrapper',
      ],
    ];

    $form['configuration'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'condition-configuration-wrapper',
      ],
    ];

    if ($form_state->getValue('condition') || $condition) {
      /** @var \Drupal\Core\Condition\ConditionPluginBase $plugin */
      if ($form_state->getValue('condition')) {
        $plugin = $this->conditionManager->createInstance($form_state->getValue('condition'));
      }
      elseif ($condition) {
        $plugin = $this->conditionManager->createInstance($condition['plugin'], $condition['configuration']);
      }
      $form['configuration'] = array_merge($form['configuration'], $plugin->buildConfigurationForm([], $form_state));
    }

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $condition ? $this->t('Update condition') : $this->t('Add condition'),
    ];

    return $form;
  }

  /**
   * Callback when a condition is selected in the dropdown.
   *
   * @param array $form
   *
   * @return mixed
   */
  public function conditionSelected(array $form) {
    return $form['configuration'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $page_id = $form_state->get('page_id');
    $variant_id = $form_state->get('variant_id');
    $condition_id = $form_state->has('condition_id') ? $form_state->get('condition_id') : FALSE;
    $configuration = $form_state->hasValue('configuration') ? $form_state->getValue('configuration') : [];

    $this->pageManager->saveCondition($page_id, $variant_id, $form_state->getValue('condition'), $configuration, $condition_id);

    // Set a message and redirect.
    $message = $condition_id ? 'Condition successfully updated.' : 'Condition successfully saved.';
    drupal_set_message($this->t($message));
    $form_state->setRedirect('page_layout.admin_variants_edit', ['page_id' => $page_id, 'variant_id' => $variant_id], ['fragment' => "edit-conditions"]);
  }

}
