<?php

namespace Drupal\paragraphs_features\Ajax;

use Drupal\Core\Ajax\CommandInterface;

/**
 * Provides an AJAX command for scrolling an element into the view.
 *
 * This command is implemented in Drupal.AjaxCommands.prototype.scrollToElement.
 */
class ScrollToElementCommand implements CommandInterface {

  /**
   * A CSS selector string.
   *
   * @var string
   */
  protected $selector;

  /**
   * Constructs a ScrollToElementCommand object.
   *
   * @param string $selector
   *   A CSS selector.
   */
  public function __construct(string $selector) {
    $this->selector = $selector;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    return [
      'command' => 'scrollToElement',
      'selector' => $this->selector,
    ];
  }

}
