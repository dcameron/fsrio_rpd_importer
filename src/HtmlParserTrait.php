<?php

namespace Drupal\fsrio_rpd_importer;

/**
 * Provides useful functions for parsing HTML with PHP's DOM classes.
 */
trait HtmlParserTrait {

  /**
   * Dumps the content of a node's children.
   *
   * The method is only intended for use in testing and debugging!
   *
   * @param \DOMNode $node
   *   The parent node.
   */
  protected function listChildNodes(\DOMNode $node) {
    foreach ($node->childNodes as $child) {
      var_dump($child->nodeValue);
    }
  }

  /**
   * Recursively returns the value of a DOMNode's child.
   *
   * @param \DOMNode $node
   *   The parent node.
   * @param array $child_indexes
   *   The indexes of child nodes in which the value will be found.  The indexes
   *   should be listed from highest to lowest in depth.
   *
   * @return string
   *   The value of the lowest-depth node, trimmed of any whitespace.
   */
  protected function parseChildNodes(\DOMNode $node, array $child_indexes) {
    $index = array_shift($child_indexes);
    $child = $node->childNodes->item($index);
    if (empty($child_indexes)) {
      // Node values will often have leading and trailing whitespace that needs
      // to be trimmed.
      return trim($child->nodeValue);
    }
    return $this->parseChildNodes($child, $child_indexes);
  }

}
