<?php

/**
 * @file
 * Contains \Drupal\syntax_highlighter\Plugin\Filter\FilterSyntaxHighlighterAttachLibrary
 */

namespace Drupal\syntax_highlighter\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;

/**
 * Provides a filter to attach syntax highlighter library if code tags are present.
 *
 * @Filter(
 *   id = "syntax_highlighter_attach_library_filter",
 *   title = @Translation("Attach syntax highlighter library"),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_REVERSIBLE
 * )
 */
class FilterSyntaxHighlighterAttachLibrary extends FilterBase {

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $result = new FilterProcessResult($text);

    if (stristr($text, '<pre') !== FALSE) {
      $attach = FALSE;
      $nodes = Html::load($text)->getElementsByTagName('pre');

      foreach ($nodes as $node) {
        if (preg_match('/\bbrush\b:(.*?);/i', $node->getAttribute('class'))) {
          $attach = TRUE;
          break;
        }
      }

      if ($attach) {
        $result->addAttachments([
          'library' => [
            'syntax_highlighter/highlight',
          ]
        ]);
      }
    }

    return $result;
  }

}
