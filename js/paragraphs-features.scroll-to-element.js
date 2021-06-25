/**
 * @file thunder-paragraph-features.scroll-to-element.js
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.AjaxCommands.prototype.scrollToElement = function (ajax, response) {

    var element = document.querySelector(response.selector);
    var resizeObserver = new ResizeObserver(function (event) {
      console.log(event)
      element.scrollIntoView({
        block: 'center'
      });
    });
    resizeObserver.observe(element);
    setTimeout(function () {
        resizeObserver.unobserve(element)
      }, 500
    );

  };

}(jQuery, Drupal, drupalSettings));
