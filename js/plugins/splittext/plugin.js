/**
 * CKEditor plugin for split text feature for Paragraphs text fields.
 *
 * @file plugin.js
 */

(function ($, Drupal, drupalSettings, CKEDITOR) {

  'use strict';

  /**
   * Ensure namespace for paragraphs features exists.
   */
  if (typeof Drupal.paragraphs_features === 'undefined') {
    Drupal.paragraphs_features = {};
  }

  /**
   * Namespace for split text paragraphs feature.
   *
   * @type {Object}
   */
  Drupal.paragraphs_features.split_text = {
    // Temporal object is used to preserve data over ajax requests.
    _tmp: {}
  };

  /**
   * Register split text plugin for custom CKEditor.
   *
   * @param {object} editorSettings
   *   CKEditor settings object.
   */
  Drupal.paragraphs_features.split_text.registerPlugin = function (editorSettings) {
    // Split text toolbar and plugin should be registered only once.
    if (editorSettings.extraPlugins.indexOf('splittext') !== -1) {
      return;
    }

    // We want to have plugin enabled for all text editors.
    editorSettings.extraPlugins += ',splittext';

    // Split text option should be added as last one in toolbar and preserved
    // there after ajax requests are executed.
    var toolbar = editorSettings.toolbar;
    if (typeof editorSettings._splittextIndex === 'undefined') {
      editorSettings._splittextIndex = toolbar.length - 1;
      toolbar.push('/');
    }

    toolbar[editorSettings._splittextIndex] = {
      name: Drupal.t('Split text'),
      items: ['SplitText']
    };
  };

  /**
   * Register split text plugin for all CKEditors.
   *
   * @type {{attach: attach}}
   */
  Drupal.behaviors.setSplitTextPlugin = {
    attach: function () {
      if (!drupalSettings || !drupalSettings.editor || !drupalSettings.editor.formats) {
        return;
      }

      $.each(drupalSettings.editor.formats, function (editorId, editorInfo) {
        if (editorInfo.editor === 'ckeditor') {
          Drupal.paragraphs_features.split_text.registerPlugin(editorInfo.editorSettings);
        }
      });
    }
  };

  /**
   * Create new paragraph with same type after one where editor is placed.
   *
   * -------------------------------------------------------------------------*
   * Important Note:
   * This could be provided in future as option where split text could work
   * without any add mode, not just modal.
   * -------------------------------------------------------------------------*
   *
   * @param {object} editor
   *   CKEditor object.
   */
  Drupal.paragraphs_features.split_text.createNewParagraphOverDuplicate = function (editor) {
    // Split text namespace.
    var stNs = Drupal.paragraphs_features.split_text;

    var actionButton = $('#' + editor.name).closest('.paragraphs-subform')
      .parent()
      .find('.paragraphs-actions input[name$="_duplicate"]');

    stNs.storeTempData(editor, actionButton.attr('name'));

    actionButton.trigger('mousedown');
  };

  /**
   * Create new paragraph with same type after one where editor is placed.
   *
   * @param {object} editor
   *   CKEditor object.
   */
  Drupal.paragraphs_features.split_text.createNewParagraphOverModal = function (editor) {
    // Split text namespace.
    var stNs = Drupal.paragraphs_features.split_text;

    var $paragraphRow = $('#' + editor.name).closest('.paragraphs-subform').closest('tr');
    var paragraphType = $paragraphRow.find('[data-paragraphs-split-text-type]').attr('data-paragraphs-split-text-type');
    var $deltaField = $paragraphRow.closest('table').siblings('.clearfix').find('input.paragraph-type-add-modal-delta');

    // Stop splitting functionality if add button is disabled or not available.
    var $addButton = $deltaField.siblings('.paragraph-type-add-modal-button');
    if ($addButton.length === 0 || $addButton.is(':disabled')) {
      return;
    }

    // New paragraph is always added after existing one - all post ajax
    // functionality expects that.
    var insertionDelta = $paragraphRow.index() + 1;

    // Add in between buttons doubles number of rows.
    if ($paragraphRow.siblings('.paragraphs-features__add-in-between__row').length !== 0) {
      insertionDelta /= 2;
    }
    $deltaField.val(insertionDelta);

    var paragraphTypeButtonSelector = $deltaField.attr('data-drupal-selector').substr('edit-'.length).replace(/-add-more-add-modal-form-area-add-more-delta$/, '-' + paragraphType + '-add-more').replace(/_/g, '-');
    var $actionButton = $('[data-drupal-selector^="' + paragraphTypeButtonSelector + '"]');

    // Triggering element name is required for proper handling of ajax response.
    stNs.storeTempData(editor, $actionButton.attr('name'));

    $actionButton.trigger('mousedown');
  };

  /**
   * Store temporal data required after ajax request is finished.
   *
   * @param {object} editor
   *   CKEditor object.
   * @param {string} triggerElementName
   *   Name of trigger element, required for ajax response handling.
   */
  Drupal.paragraphs_features.split_text.storeTempData = function (editor, triggerElementName) {
    var tmpObject = Drupal.paragraphs_features.split_text._tmp;

    var $editorObject = $('#' + editor.name);
    var selection = editor.getSelection();
    var ranges = selection.getRanges();

    // Last node that should be selected to cut content.
    var lastNode = ranges[0].document.getBody().getLast();
    ranges[0].setEndAfter(lastNode);
    selection.selectRanges(ranges);

    // Temporal container is used to preserve data over ajax requests.
    tmpObject.originalEditorSelector = $editorObject.data('drupal-selector');

    // Triggering element is required for proper handling of ajax response.
    tmpObject.triggeringElementName = triggerElementName;

    // First we "cut" text that will be "pasted" to new added paragraph.
    tmpObject.newContent = editor.extractSelectedHtml(true);
    tmpObject.oldContent = editor.getData();

    tmpObject.split_trigger = true;
  };

  /**
   * Handler for ajax requests.
   *
   * It handles updating of editors are new paragraph is added.
   *
   * @param {object} e
   *   Event object.
   * @param {object} xhr
   *   XHR object.
   * @param {object} settings
   *   Request settings.
   */
  Drupal.paragraphs_features.split_text.onAjaxSplit = function (e, xhr, settings) {
    // Split text namespace.
    var stNs = Drupal.paragraphs_features.split_text;

    // Only relevant ajax actions should be handled.
    if (settings.extraData._triggering_element_name !== stNs._tmp.triggeringElementName || !stNs._tmp.split_trigger) {
      return;
    }

    // Set relevant data to original paragraph.
    var $originalEditor = $('[data-drupal-selector="' + stNs._tmp.originalEditorSelector + '"]');
    var originalEditor = CKEDITOR.instances[$originalEditor.attr('id')];
    var $originalRow = $originalEditor.closest('tr');
    stNs.updateEditor($originalEditor.attr('id'), stNs._tmp.oldContent);

    // Set "cut" data ot new paragraph.
    var $newRow = $originalRow.nextAll($originalRow.hasClass('odd') ? '.even' : '.odd');
    var wrapperSelector = stNs.getEditorWrapperSelector(originalEditor);
    var fieldIndex = $originalEditor.closest('div[data-drupal-selector="' + wrapperSelector + '"]').index();
    var $newEditor = $($newRow.find('.paragraphs-subform > div')[fieldIndex]).find('textarea');
    stNs.updateEditor($newEditor.attr('id'), stNs._tmp.newContent);

    // Cleanup states.
    stNs._tmp.split_trigger = false;

    // Delta field has to be cleaned up for proper working of add button. It
    // will not make any impact on non modal add mode.
    $originalRow.closest('table').siblings('.clearfix').find('input.paragraph-type-add-modal-delta').val('');
  };

  /**
   * Helper function to update content of CKEditor.
   *
   * @param {string} editorId
   *   Editor ID.
   * @param {string} data
   *   HTML as text for CKEditor.
   */
  Drupal.paragraphs_features.split_text.updateEditor = function (editorId, data) {
    if (typeof editorId === 'undefined') {
      return;
    }

    CKEDITOR.instances[editorId].setData(data, {
      callback: function () {
        this.updateElement();
        this.element.data('editor-value-is-changed', true);
      }
    });
  };

  /**
   * Makes split of paragraph text on cursor position.
   *
   * @param {object} editor
   *   CKEditor object.
   */
  Drupal.paragraphs_features.split_text.split = function (editor) {
    // Split text namespace.
    var stNs = Drupal.paragraphs_features.split_text;

    // After ajax response correct values should be placed in text editors.
    $(document).once('ajax-paragraph').ajaxComplete(stNs.onAjaxSplit);

    stNs.createNewParagraphOverModal(editor);
  };

  /**
   * Get wrapper Drupal selector for CKEditor.
   *
   * @param {object} editor
   *   CKEditor object.
   *
   * @return {string}
   *   Returns CKEditor wrapper ID.
   */
  Drupal.paragraphs_features.split_text.getEditorWrapperSelector = function (editor) {
    return editor.name.replace(/-[0-9]+-value(--[0-9A-Za-z_-]+)?$/, '-wrapper');
  };

  /**
   * Verify if field is direct field of paragraph with enabled split text.
   *
   * Solution is to check that text field wrapper id direct child of subform.
   * And additionally that Wrapper ID is in list of enabled widgets.
   *
   * @param {object} editor
   *   CKEditor object.
   *
   * @return {boolean}
   *   Returns if editor is for valid paragraphs text field.
   */
  Drupal.paragraphs_features.split_text.isValidParagraphsField = function (editor) {
    // Split text namespace.
    var stNs = Drupal.paragraphs_features.split_text;

    var wrapperSelector = stNs.getEditorWrapperSelector(editor);
    var $subForm = $('#' + editor.name).closest('.paragraphs-subform');

    // Paragraphs split text should work only on widgets where that option is enabled.
    var paragraphWrapperId = $subForm.closest('.paragraphs-tabs-wrapper').attr('id');
    if (!drupalSettings.paragraphs_features.split_text[paragraphWrapperId]) {
      return false;
    }

    return $subForm.find('> div[data-drupal-selector="' + wrapperSelector + '"]').length === 1;
  };

  /**
   * Register define new plugin.
   */
  CKEDITOR.plugins.add('splittext', {
    hidpi: true,
    requires: '',

    init: function (editor) {
      // Split text namespace.
      var stNs = Drupal.paragraphs_features.split_text;
      var modulePath = drupalSettings.paragraphs_features.split_text._path;

      // Split Text functionality should be added only for paragraphs Text fields.
      if (!stNs.isValidParagraphsField(editor)) {
        return;
      }

      editor.addCommand('splitText', {
        exec: function (editor) {
          stNs.split(editor, 'before');
        }
      });

      editor.ui.addButton('SplitText', {
        label: 'Split Text',
        icon: '/' + modulePath + '/js/plugins/splittext/icons/splittext.png',
        command: 'splitText'
      });

      if (editor.addMenuItems) {
        editor.addMenuGroup('splittext');
        editor.addMenuItems({
          splittext: {
            label: Drupal.t('Split Text'),
            command: 'splitText',
            icon: '/' + modulePath + '/js/plugins/splittext/icons/splittext.png',
            group: 'splittext',
            order: 1
          }
        });
      }

      if (editor.contextMenu) {
        editor.contextMenu.addListener(function () {
          return {
            splittext: CKEDITOR.TRISTATE_OFF
          };
        });
      }
    }
  });

}(jQuery, Drupal, drupalSettings, CKEDITOR));
