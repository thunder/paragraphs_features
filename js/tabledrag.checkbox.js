(function ($, Drupal) {

  'use strict';

  /**
   * Adds a sorting button to the top of the table and adds checkboxes to the rows.
   *
   * On click on the sorting button, show/hide the checkboxes and add/remove sorting targets.
   */
  Drupal.tableDrag.prototype.initCkbx = function () {

    this.sortCheckbox = $('<span class="tabledrag-checkbox-wrapper"><input type="checkbox" class="tabledrag-checkbox" /></span>')
      .on('change', $.proxy(function (e) {
        // At least one checkbox is checked.
        var oneChecked = Array.prototype.slice.call(this.table.querySelectorAll('input.tabledrag-checkbox[type="checkbox"]')).some(input => input.checked);

        this.$table.find()
        if (!this.$table.hasClass('tabledrag-checkbox-active')) {
          this.triggerStartEvent();
          this.$table.addClass('tabledrag-checkbox-active');
          this.addSortTargets();
        }
        else if (!oneChecked) {
          this.removeSortTargets()
          this.$table.removeClass('tabledrag-checkbox-active');
          this.triggerEndEvent();
        }

      }, this));

    this.addInBeetween = !(
      typeof Drupal.behaviors.paragraphsFeaturesAddInBetweenInit === 'undefined' &&
      typeof Drupal.behaviors.initInBetweenButtons === 'undefined'
    );

    // Add spacer rows.
    this.addSpacer();

    // Add sorting checkbox to items.
    this.$table.find('tbody > tr.draggable > .field-multiple-drag .tabledrag-handle').hide();
    this.$table.find('> tbody > tr.draggable > .field-multiple-drag .tabledrag-cell-content').prepend(this.sortCheckbox);
    this.$table.addClass('tabledrag-checkbox-sort');
  };

  /**
   * Disable/enable related (parents, children) tabledrag sort buttons.
   */
  Drupal.tableDrag.prototype.toggleRelatedButtons = function () {
    var rootTable = this.$table.parents('table.field-multiple-table').last();

    if (!rootTable.length) {
      rootTable = this.$table;
    }

    rootTable.find('table.field-multiple-table').addBack().not(this.$table).each(toggleButton());

    function toggleButton() {
      return function () {
        $(this).find('> thead button.tabledrag-toggle-checkbox').attr('disabled', function (index, value) {
          return !value;
        });
      };
    }
  };

  /**
   * Adds/Removes sort targets.
   */
  Drupal.tableDrag.prototype.toggleSortTargets = function () {
    if (this.$table.hasClass('tabledrag-checkbox-active')) {
      this.removeSpacer();
      this.addSortTargets();
    }
    else {
      this.removeSortTargets();
      this.addSpacer();
    }
  };

  /**
   * Triggers a start event.
   */
  Drupal.tableDrag.prototype.triggerStartEvent = function () {
    this.$table.triggerHandler('tabledrag-checkbox-start');
  };

  /**
   * Triggers an end event.
   */
  Drupal.tableDrag.prototype.triggerEndEvent = function () {
    this.$table.triggerHandler('tabledrag-checkbox-end');
  };

  /**
   * Add spacer rows
   */
  Drupal.tableDrag.prototype.addSpacer = function () {
    if (!this.addInBeetween) {
      var spacer = '<tr class="tabledrag-sort-spacer"></tr>';
      this.$table.find('> tbody > tr.draggable:first').before(spacer);
      this.$table.find('> tbody > tr.draggable').after(spacer);
    }
  };

  /**
   * Remove spacer rows.
   */
  Drupal.tableDrag.prototype.removeSpacer = function () {
    if (!this.addInBeetween) {
      this.$table.find('> tbody > tr.tabledrag-sort-spacer').remove();
    }
  };

  /**
   * Adds sorting targets to the table, which handle the sorting on click.
   */
  Drupal.tableDrag.prototype.addSortTargets = function () {
    var $target = $(
      '<a href="#" class="tabledrag-sort-target">' +
        '<span class="tabledrag-sort-target-left"></span>' +
        '<span class="tabledrag-sort-target-middle"></span>' +
        '<span class="tabledrag-sort-target-right"></span>' +
      '</a>'
    )
      .on('click', $.proxy(function (e) {
        e.preventDefault();

        var $targetWrapper = $(e.target).closest('tr');
        var row = $targetWrapper.prev();
        var swapAfter = true;

        // on click on the first target, the rows should be inserted before the first row.
        if ($targetWrapper.hasClass('tabledrag-sort-before')) {
          row = $targetWrapper.next();
          swapAfter = false;
        }

        this.removeSortTargets();
        this.sort(row, swapAfter);
        // End sort, clean up.
        this.disableCheckboxes();
        this.$table.removeClass('tabledrag-checkbox-active');
        this.triggerEndEvent();

      }, this))
      .wrap('<tr class="tabledrag-sort-target-wrapper"><td class="tabledrag-sort-target-column" colspan="3"></td></tr>')
      .parent().parent();

    this.$table.find('> tbody > tr.draggable').after($target);
    this.$table.find('> tbody > tr.draggable:first').before($target.clone(true).addClass('tabledrag-sort-before'));

  };

  /**
   * Removes all sorting targets from the table.
   */
  Drupal.tableDrag.prototype.removeSortTargets = function () {
    this.$table.find('tr.tabledrag-sort-target-wrapper').remove();
  };

  /**
   * Uncheck checked checkboxes.
   */
  Drupal.tableDrag.prototype.disableCheckboxes = function () {
    this.$table.find('> tbody > tr.draggable > .field-multiple-drag .tabledrag-checkbox:checked').prop('checked', false);
  };

  /**
   * Switches the visibility between the tabledrag checkbox and handle.
   */
  Drupal.tableDrag.prototype.toggleCheckboxes = function () {
    // The tabledrag handle is toggled via CSS
    this.$table.find('> tbody > tr.draggable > .field-multiple-drag .tabledrag-checkbox-wrapper').toggle();
  };

  /**
   * Sorts all selected rows before/after a specified row.
   *
   * @param {Object} row - row before/after which selected rows should be inserted.
   * @param {boolean} swapAfter - if the rows should be inserted after specified row
   */
  Drupal.tableDrag.prototype.sort = function (row, swapAfter) {
    swapAfter = swapAfter || false;

    var checkboxes = this.$table.find('> tbody > tr.draggable > .field-multiple-drag input.tabledrag-checkbox:checked');
    var rowsToBeMoved = checkboxes.closest('tr.draggable');

    // Iterate over selected rows and swap each separately.
    rowsToBeMoved.each($.proxy(function (index, element) {
      var currentRow = new this.row(rowsToBeMoved[index], 'pointer', self.indentEnabled, self.maxDepth, true);
      this.rowObject = currentRow;

      if (swapAfter) {
        currentRow.swap('after', row);
        // Since we want to keep the order and inserting after a row,
        // we have to move the next row to after this one.
        row = $(rowsToBeMoved[index]);
      }
      else {
        currentRow.swap('before', row);
        row = $(rowsToBeMoved[index]);
        swapAfter = true;
      }

      this.rowObject.markChanged();

      // also updates the weights.
      this.updateFields(currentRow.element);
    }, this));

    this.restripeTable();
    this.onDrop();

  };

  Drupal.behaviors.tableDragCheckbox = {
    attach: function (context, settings) {
      for (var base in settings.tableDrag) {
        if (Object.prototype.hasOwnProperty.call(settings.tableDrag, base)) {
          var $table = $(context).find('#' + base).once('tabledrag-checkbox');
          if ($table.length) {
            Drupal.tableDrag[base].initCkbx();
          }
        }
      }
    }
  };

})(jQuery, Drupal);
