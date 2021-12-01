/**
 * @file thunder-paragraph-features.add-in-between.js
 */

(($, Drupal, once) => {

  'use strict';

  /**
   * Ensure namespace for paragraphs features exists.
   */
  Drupal.paragraphs_features = Drupal.paragraphs_features || {};

  /**
   * Namespace for add in between paragraphs feature.
   *
   * @type {Object}
   */
  Drupal.paragraphs_features.add_in_between = {};

  /**
   * Define add in between row template.
   *
   * @param {object} config
   *   Configuration options for add in between row template.
   *
   * @return {HTMLElement}
   *   Returns element for add in between row.
   */
  Drupal.theme.paragraphsFeaturesAddInBetweenRow = (config) => {
    const input = document.createElement('input');
    const wrapper = document.createElement('div');
    const td = document.createElement('td');
    const row = document.createElement('tr');

    input.classList.add('paragraphs-features__add-in-between__button', 'button--small', 'js-show', 'button', 'js-form-submit', 'form-submit');
    input.type = 'submit';
    input.value = config.text;
    wrapper.classList.add('paragraphs-features__add-in-between__wrapper');
    wrapper.appendChild(input);
    td.setAttribute('colspan', '100%');
    td.appendChild(wrapper);
    row.classList.add('paragraphs-features__add-in-between__row');
    row.appendChild(td);

    return row;
  };

  /**
   * Attach add in between buttons on paragraphs table.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior.
   */
  Drupal.behaviors.paragraphsFeaturesAddInBetweenInit = {
    attach: (context, settings) => {
      Object.values(settings.paragraphs_features.add_in_between || {}).forEach((field) => {
        document.querySelectorAll('#' + field.wrapperId).forEach((wrapper) => {
          Drupal.paragraphs_features.add_in_between.initParagraphsWidget(wrapper, field);
        });
      });
    }
  };

  /**
   * Get paragraphs add modal block in various themes structures.
   *
   *  gin:
   *   .layer-wrapper table
   *   .form-actions
   * claro:
   *   table
   *   .form-actions
   * thunder-admin / seven:
   *   table
   *   .clearfix
   *
   * @param {HTMLElement} table
   * The table element.
   *
   * @return {HTMLElement} addModalBlock
   *   the add modal block element.
   */
  Drupal.paragraphs_features.add_in_between.getAddModalBlock = (table) => {
    const fromParent = (elem) => {
      let sibling = elem.parentNode.firstChild;
      while (sibling) {
        if (sibling.nodeType === 1 && sibling !== elem) {
          const addModalBlock = sibling.querySelector('.paragraphs-add-wrapper');
          if (addModalBlock) {
            return addModalBlock;
          }
        }
        sibling = sibling.nextSibling;
      }
    };
    return fromParent(table) || fromParent(table.parentNode);
  };

  /**
   * Init paragraphs widget with add in between functionality.
   *
   * @param {HTMLDocument|HTMLElement} [context=document]
   *   An element to attach behaviors to.
   * @param {array} field
   *   The paragraphs field config.
   */
  Drupal.paragraphs_features.add_in_between.initParagraphsWidget = function (context, field) {
    const [table] = once('paragraphs-features-add-in-between-init', '.field-multiple-table', context);
    if (!table) {
      return;
    }
    const addModalBlock = Drupal.paragraphs_features.add_in_between.getAddModalBlock(table);
    // Ensure that paragraph list uses modal dialog.
    if (!addModalBlock) {
      return;
    }
    // A new button for adding at the end of the list is used.
    addModalBlock.style.display = 'none';

    const addModalButton = addModalBlock.querySelector('.paragraph-type-add-modal-button');
    const buttonRowElement = () => {
      return Drupal.theme('paragraphsFeaturesAddInBetweenRow', {text: Drupal.t('+ Add')});
    };

    // Add buttons and adjust drag-drop functionality.
    let tableBody = table.querySelector(':scope > tbody');

    // Add a new button for adding a new paragraph to the end of the list.
    if (!tableBody) {
      tableBody = document.createElement('tbody');
      table.append(tableBody);
    }

    tableBody.querySelectorAll(':scope > tr').forEach((rowElement) => {
      rowElement.insertAdjacentElement('beforebegin', buttonRowElement());
    });
    tableBody.appendChild(buttonRowElement());

    // Adding of a new paragraph can be disabled for some reason.
    if (addModalButton.getAttribute('disabled')) {
      tableBody.querySelectorAll('.paragraphs-features__add-in-between__button').forEach((button) => {
        button.setAttribute('disabled', 'disabled');
        button.classList.add('is-disabled');
      });
    }

    // Trigger attaching of behaviors for added buttons.
    Drupal.behaviors.paragraphsFeaturesAddInBetweenRegister.attach(table);
  };

  /**
   * Click handler for click "Add" button between paragraphs.
   *
   * @type {Object}
   */
  Drupal.behaviors.paragraphsFeaturesAddInBetweenRegister = {
    attach: (context) => {
      once('paragraphs-features-add-in-between', '.paragraphs-features__add-in-between__button', context).forEach((button) => {
        button.addEventListener('click', (event) => {
          const dialog = Drupal.paragraphs_features.add_in_between.getAddModalBlock(button.closest('table')).querySelector('.paragraphs-add-dialog');
          const row = button.closest('tr');
          const delta = Array.prototype.indexOf.call(row.parentNode.children, row) / 2;

          // Set delta before opening of dialog.
          Drupal.paragraphs_features.add_in_between.setDelta(dialog, delta);
          Drupal.paragraphsAddModal.openDialog(dialog, Drupal.t('Add In Between'));

          // Stop default execution of click event.
          event.preventDefault();
          event.stopPropagation();
        });
      });
    }
  };

  /**
   * Set delta into hidden field, where a new paragraph will be added.
   *
   * @param {Object} dialog
   *   jQuery object for add more wrapper element.
   * @param {int} delta
   *   Integer value for delta position where a new paragraph should be added.
   */
  Drupal.paragraphs_features.add_in_between.setDelta = (dialog, delta) => {
    let deltaInput = dialog.closest('.paragraphs-add-wrapper').querySelector('.paragraph-type-add-modal-delta');

    deltaInput.value = delta;
  };

  /**
   * Init Drag-Drop handling for add in between buttons for paragraphs table.
   *
   * @type {Object}
   */
  Drupal.behaviors.paragraphsFeaturesAddInBetweenTableDragDrop = {
    attach: (context, settings) => {
      Object.keys(settings.tableDrag || {}).forEach((tableId) => {
        Drupal.paragraphs_features.add_in_between.adjustDragDrop(tableId);
        // Show / hide row weights.
        once('in-between-buttons-columnschange', '#' + tableId, context).forEach((table) => {
          // drupal tabledrag uses jquery events.
          $(table).on('columnschange', (event) => {
            Drupal.paragraphs_features.add_in_between.adjustDragDrop(event.target.id);
          });
        });
      });
    }
  };

  /**
   * Adjust drag-drop functionality for paragraphs with "add in between"
   * buttons.
   *
   * @param {string} tableId
   *   Table ID for paragraphs table with adjusted drag-drop behavior.
   */
  Drupal.paragraphs_features.add_in_between.adjustDragDrop = (tableId) => {
    // Ensure that function changes are executed only once.
    if (!Drupal.tableDrag[tableId] || Drupal.tableDrag[tableId].paragraphsDragDrop) {
      return;
    }
    Drupal.tableDrag[tableId].paragraphsDragDrop = true;

    // Helper function to create sequence execution of two bool functions.
    const sequenceBoolFunctions = (originalFn, newFn) => {
      // Arrow functions do not support arguments.
      return function () {
        let result = originalFn.apply(this, arguments);

        if (result) {
          result = newFn.apply(this, arguments);
        }

        return result;
      };
    };

    // Allow row swap if it's not in between button.
    const paragraphsIsValidSwap = (row) => {
      return !row.classList.contains('paragraphs-features__add-in-between__row');
    };

    // Sequence default .isValidSwap() function with custom paragraphs function.
    const rowObject = Drupal.tableDrag[tableId].row;
    rowObject.prototype.isValidSwap = sequenceBoolFunctions(rowObject.prototype.isValidSwap, paragraphsIsValidSwap);

    // provide custom .onSwap() handler to reorder "Add" buttons.
    rowObject.prototype.onSwap = (row) => {
      const table = row.closest('table');
      const allDrags = table.querySelectorAll(':scope > tbody > tr.draggable');
      const allAdds = table.querySelectorAll(':scope > tbody > tr.paragraphs-features__add-in-between__row');

      // We have to re-stripe add in between rows.
      allDrags.forEach((dragElem, index) => {
        if (allAdds.item(index)) {
          dragElem.insertAdjacentElement('beforebegin', allAdds.item(index));
        }
      });
    };
  };

})(jQuery, Drupal, once);
