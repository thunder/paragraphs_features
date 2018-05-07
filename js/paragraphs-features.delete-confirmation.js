/**
 * @file
 * Paragraphs actions JS code for paragraphs actions button.
 */

(function ($, Drupal) {

  'use strict';

  // Ensure namespace.
  Drupal.paragraphs_features = Drupal.paragraphs_features || {};

  /**
   * Theme function for remove button
   *
   * @return {string}
   *   Returns markup.
   */
  Drupal.theme.paragraphsFeaturesDeleteConfirmationButton = function () {
    return '<button type="button" class="paragraphs-features__delete-confirm paragraphs-dropdown-action button js-form-submit form-submit">' + Drupal.t('Remove') + '</button>';
  };

  /**
   * Theme functions for confirmation message.
   *
   * @param options
   *   Configuration options used to construct the markup.
   * @return {string}
   *   Returns markup.
   */
  Drupal.theme.paragraphsFeaturesDeleteConfirmationMessage = function (options) {
    return '' +
      '<div class="paragraphs-features__delete-confirmation">' +
      '  <div class="paragraphs-features__delete-confirmation__message">' + options.message + '</div>' +
      '  <div class="form-actions js-form-wrapper form-wrapper" id="edit-actions">' +
      '    <button type="button" class="paragraphs-features__delete-confirmation__remove-button button button--primary js-form-submit form-submit">' + options.remove + '</button>' +
      '    <button type="button" class="paragraphs-features__delete-confirmation__cancel-button button js-form-submit form-submit">' + options.cancel + '</button>' +
      '  </div>' +
      '</div>';
  };

  /**
   * Handler for paragraphs_actions custom remove button.
   * Also adds Confirmation message, buttons and their handlers.
   *
   * @returns {Function}
   */
  Drupal.paragraphs_features.deleteConfirmHandler = function () {
    return function (event) {
      var $wrapper = $(event.target).parents('div[id*="-item-wrapper"]');
      // Hide children.
      $wrapper.children().wrap('<div class="visually-hidden"></div>');
      // Add markup.
      $wrapper.append(Drupal.theme('paragraphsFeaturesDeleteConfirmationMessage', { message: Drupal.t('Are you sure you want to remove this paragraph?'), remove: Drupal.t('Remove'), cancel: Drupal.t('Cancel') }));
      // Add handlers for buttons.
      $wrapper.find('.paragraphs-features__delete-confirmation__cancel-button').bind('mousedown', Drupal.paragraphs_features.deleteConfirmRemoveHandler());
      $wrapper.find('.paragraphs-features__delete-confirmation__remove-button').bind('mousedown', Drupal.paragraphs_features.deleteConfirmCancelHandler());
    };
  };

  /**
   * Handler for remove action.
   *
   * @param event
   * @returns {Function}
   */
  Drupal.paragraphs_features.deleteConfirmCancelHandler = function () {
    return function (event) {
      $(event.target).parents('div[id*="-item-wrapper"]').find('input.paragraphs-dropdown-action[data-drupal-selector$="remove"]').trigger('mousedown');
    };
  };

  /**
   * Handler for cancel action.
   *
   * @param event
   * @returns {Function}
   */
  Drupal.paragraphs_features.deleteConfirmRemoveHandler = function () {
    return function (event) {
      var $wrapper = $(event.target).parents('div[id*="-item-wrapper"]');
      $wrapper.children('.paragraphs-features__delete-confirmation').remove();
      $wrapper.children('.visually-hidden').children().unwrap();
    };
  };

  /**
   * Init inline remove confirmation form.
   *
   * @type {{attach: attach}}
   */
  Drupal.behaviors.paragraphsFeaturesDeleteConfirmationInit = {
    attach: function (context, settings) {
      var $actions = $(context).find('.paragraphs-actions').once('paragraphs-features-delete-confirmation-init');
      $actions.find('input.paragraphs-dropdown-action[data-drupal-selector*="remove"]').each(function () {
        // Add custom button and handler.
        $(Drupal.theme('paragraphsFeaturesDeleteConfirmationButton')).insertBefore(this).bind('mousedown', Drupal.paragraphs_features.deleteConfirmHandler());
        // Hide original Button
        $(this).wrap('<div class="visually-hidden"></div>');
      });
    },
  };
}(jQuery, Drupal));
