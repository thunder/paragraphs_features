/**
 * @file thunder-paragraph-features.scroll-to-element.js
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.AjaxCommands.prototype.scrollToElement = function (ajax, response) {

    var resizeObserver = new ResizeObserver(function (event) {
      document
        .querySelector('[data-drupal-selector=' + response.drupalElementSelector + ']')
        .scrollIntoView({block: 'center'});
    });

    var parent = document.querySelector('[data-drupal-selector=' + response.drupalParentSelector + ']');
    resizeObserver.observe(parent);

    setTimeout(function () {
      resizeObserver.unobserve(parent);
    }, 500);
  };

}(jQuery, Drupal, drupalSettings));
