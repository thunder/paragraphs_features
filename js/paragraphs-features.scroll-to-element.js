/**
 * @file thunder-paragraph-features.add-in-between.js
 */

(function ($, Drupal, drupalSettings) {

  'use strict';

  Drupal.AjaxCommands.prototype.scrollToElement = function (ajax, response) {

    function isInViewport(element) {
      var rect = element.getBoundingClientRect();
      return (
        rect.top >= 0 &&
        rect.left >= 0 &&
        rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
        rect.right <= (window.innerWidth || document.documentElement.clientWidth)
      );
    }

    var element = document.querySelector(response.selector).parentElement;
    var counter = 0;
    var interval = setInterval(function () {
      element.scrollIntoView({
        block: 'center',
        behavior: 'smooth'
      });
      counter++;
      if (isInViewport(element) || counter > 25) {
        clearInterval(interval);
      }
    }, 50);

  };

}(jQuery, Drupal, drupalSettings));
