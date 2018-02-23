/**
 * @file thunder-paragraph-features.add-in-between.js
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Init add in between option for form display settings form.
   *
   * @type {Object}
   */
  Drupal.behaviors.paragraphsFeaturesAddInBetweenFormDisplaySettings = {
    attach: function () {
      var $addInBetweenOption = $('.paragraphs-features__add-in-between__option');
      var $selectAddMode = $addInBetweenOption.closest('.field-plugin-settings-edit-form')
        .find('select[name$="[add_mode]"]');

      $selectAddMode.once('init-on-change').on('change', function () {
        if ($selectAddMode.val() === 'modal') {
          $addInBetweenOption.parent().show();
        }
        else {
          $addInBetweenOption.parent().hide();
          $addInBetweenOption.prop('checked', false);
        }
      });

      // Initial state, to hide checkbox if it's needed.
      if ($selectAddMode.val() !== 'modal') {
        $addInBetweenOption.parent().hide();
      }
    }
  };

}(jQuery, Drupal));
