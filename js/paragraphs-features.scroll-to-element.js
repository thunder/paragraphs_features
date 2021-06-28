/**
 * @file thunder-paragraph-features.scroll-to-element.js
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.AjaxCommands.prototype.scrollToElement = function (ajax, response) {

    var element = document.querySelector(response.selector);
    var resizeObserver = new ResizeObserver(function (event) {
      element.scrollIntoView({ block: "center"});
    });

    var elements = document.documentElement.getElementsByTagName("*");
    for (var i = 0; i < elements.length; i++) {
      resizeObserver.observe(elements[i]);
    };

    setTimeout(function () {
      for (var i = 0; i < elements.length; i++) {
        resizeObserver.unobserve(elements[i]);
      }
    }, 500);
  };

}(jQuery, Drupal, drupalSettings));
