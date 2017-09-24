<?php
/**
 * @file
 * Contains \Drupal\syntax_highlighter\Plugin\CKEditorPlugin\SyntaxHighlighter.
 */

namespace Drupal\syntax_highlighter\Plugin\CKEditorPlugin;

use Drupal\ckeditor\CKEditorPluginBase;
use Drupal\ckeditor\CKEditorPluginConfigurableInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\editor\Entity\Editor;

/**
 * Defines the "syntaxhighlight" plugin.
 *
 * @CKEditorPlugin(
 *   id = "syntaxhighlight",
 *   label = @Translation("Syntax Highlight"),
 *   module = "ckeditor"
 * )
 */
class SyntaxHighlighter extends CKEditorPluginBase implements CKEditorPluginConfigurableInterface {

  /**
   * {@inheritdoc}
   */
  public function getFile() {
    return drupal_get_path('module', 'syntax_highlighter') . '/js/plugins/syntaxhighlight/plugin.js';
  }

  /**
   * {@inheritdoc}
   */
  public function getConfig(Editor $editor) {
    return array(
      'syntaxHighlight_dialogTitleAdd' => t('Insert Syntax'),
      'syntaxHighlight_dialogTitleEdit' => t('Edit Syntax'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getButtons() {
    return array(
      'Syntaxhighlight' => array(
        'label' => t('Syntax Highlighter'),
        'image' => drupal_get_path('module', 'syntax_highlighter') . '/js/plugins/syntaxhighlight/icons/syntaxhighlight.png',
      ),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @see \Drupal\editor\Form\EditorImageDialog
   * @see editor_image_upload_settings_form()
   */
  public function settingsForm(array $form, FormStateInterface $form_state, Editor $editor) {
    return $form;
  }

}
