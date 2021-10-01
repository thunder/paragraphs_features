(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.paragraphsFeaturesHideRowWeightSort = {
    attach: function (context, settings) {

      Object.keys(settings.paragraphs_features.hide_row_weight_sort).forEach(function (paragraphsWidgetId) {
        var wrapper = document.querySelector('#' + paragraphsWidgetId);
        if (!wrapper) {
          return;
        }
        var table = wrapper.querySelector('.field-multiple-table');
        if (table && Drupal.tableDrag[table.id] && !Drupal.tableDrag[table.id].hideRowWeightSort) {
          Drupal.tableDrag[table.id].$toggleWeightButton.hide();
          Drupal.tableDrag[table.id].hideRowWeightSort = true;
        }
      });
    }
  };
}(jQuery, Drupal));
