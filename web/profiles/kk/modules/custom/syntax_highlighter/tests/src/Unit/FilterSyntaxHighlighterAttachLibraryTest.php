<?php

/**
 * @file
 * Contains \Drupal\Tests\syntax_highlighter\Unit\FilterSyntaxHighlighterAttachLibraryTest.
 */

namespace Drupal\Tests\syntax_highlighter\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\syntax_highlighter\Plugin\Filter\FilterSyntaxHighlighterAttachLibrary;

/**
 * @coversDefaultClass \Drupal\syntax_highlighter\Plugin\Filter\FilterSyntaxHighlighterAttachLibrary
 * @group filter
 */
class FilterSyntaxHighlighterAttachLibraryTest extends UnitTestCase {

  /**
   * @dataProvider providerFilterCkEditorPluginMarkup
   *
   * @param string $html
   *   Input HTML.
   * @param array $expected
   *   The expected flag for attachments added to result.
   */
  public function testFilterIncludesAttachments($html) {
    $filter = new FilterSyntaxHighlighterAttachLibrary([], 'syntax_highlighter_attach_library_filter', ['provider' => 'test']);
    $result = $filter->process($html, NULL);

    $this->assertNotEmpty($result->getAttachments());
    $this->assertArraySubset($result->getAttachments(), ['library' => ['syntax_highlighter/highlight']]);
  }

  /**
   * @dataProvider providerFilterInvalidPreMarkup
   *
   * @param string $html
   *   Input HTML.
   * @param array $expected
   *   The expected flag for attachments added to result.
   */
  public function testFilterDoesNotIncludeAttachmentsIfContentDoesNotIncludeExpectedMarkup($html) {
    $filter = new FilterSyntaxHighlighterAttachLibrary([], 'syntax_highlighter_attach_library_filter', ['provider' => 'test']);
    $result = $filter->process($html, NULL);

    $this->assertEmpty($result->getAttachments());
  }

  /**
   * Provides data for testFilterIncludesAttachments.
   *
   * @return array
   *   An array of test data.
   */
  public function providerFilterCkEditorPluginMarkup() {
    return [
      ['<div><pre class="brush:php;">foo</pre></div>', TRUE],
      ['<pre class="brush:as3;gutter:false;toolbar:false;ruler:true;" title="Foo">bar</pre>', TRUE],
      ['<pre class="brush:foo;">foo</pre>', TRUE],
    ];
  }

  /**
   * Provides data for testFilterDoesNotIncludeAttachmentsIfContentDoesNotIncludeExpectedMarkup.
   *
   * @return array
   *   An array of test data.
   */
  public function providerFilterInvalidPreMarkup() {
    return [
      ['<div><pre>foo</pre></div>', FALSE],
      ['<pre>foo</pre>', FALSE],
      ['<p />', FALSE],
      ['<p>bar</p>', FALSE],
    ];
  }

}
