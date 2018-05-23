<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

/**
 * Test display delete confirmation.
 *
 * @group paragraphs_features
 */
class ParagraphsFeaturesDeleteConfirmationTest extends ParagraphsFeaturesJavascriptTestBase {

  /**
   * Test display of delete confirmation.
   */
  public function testDeleteConfirmation() {
    // Create content type with paragraphs field.
    $content_type = 'test_delete_confirm';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);
    $this->setupParagraphSettings($content_type);
    // 1) Enable setting to show confirmation on remove action.
    $this->enableDeleteConfirmation($content_type);

    $this->drupalGet("node/add/$content_type");
    $session = $this->getSession();
    $page = $session->getPage();

    // 1a) Check that custom remove button is present and the original remove
    // action is hidden.
    $is_element_hidden = $session->evaluateScript("jQuery('input[data-drupal-selector=\"field-paragraphs-0-remove\"]').parent('.visually-hidden').length === 1");
    $this->assertEquals(TRUE, $is_element_hidden, 'Original remove button should be hidden.');
    $this->assertSession()->elementExists('xpath', '//button[contains(@class, "paragraphs-features__delete-confirm")]');

    // 1b) Trigger delete confirmation message.
    $page->find('xpath', '//button[contains(@class, "paragraphs-dropdown-toggle")]')->click();
    $page->find('xpath', '//button[contains(@class, "paragraphs-features__delete-confirm")]')->click();
    // Message and buttons are shown, paragraphs inner form elements should be
    // hidden.
    $this->assertSession()->elementExists('css', 'div.paragraphs-features__delete-confirmation');
    $this->assertSession()->elementExists('css', 'button.paragraphs-features__delete-confirmation__remove-button');
    $this->assertSession()->elementExists('css', 'button.paragraphs-features__delete-confirmation__cancel-button');
    $is_element_hidden = $session->evaluateScript("jQuery('div[data-drupal-selector=\"edit-field-paragraphs-0\"]').parents('.visually-hidden').length === 1");
    $this->assertEquals(TRUE, $is_element_hidden, 'Inner form element should be hidden.');

    // 1c) Cancel remove paragraph.
    $page->find('xpath', '//button[contains(@class, "paragraphs-features__delete-confirmation__cancel-button")]')->click();
    // Confirmation message is removed, paragraphs inner form elements should be
    // shown.
    $this->assertSession()->elementNotExists('xpath', '//div[@class="paragraphs-features__delete-confirmation"]');
    $is_element_visible = $session->evaluateScript("jQuery('div[data-drupal-selector=\"edit-field-paragraphs-0\"]').parents('.visually-hidden').length === 0");
    $this->assertEquals(TRUE, $is_element_visible, 'Inner form elements should be visible.');

    // 1d) Trigger delete confirmation message, remove paragraph.
    $page->find('xpath', '//button[contains(@class, "paragraphs-dropdown-toggle")]')->click();
    $page->find('xpath', '//button[contains(@class, "paragraphs-features__delete-confirm")]')->click();
    $page->find('xpath', '//button[contains(@class, "paragraphs-features__delete-confirmation__remove-button")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Paragraph is gone.
    $this->assertSession()->elementNotExists('xpath', '//div[@id="field-paragraphs-0-item-wrapper"]');

    // 2) Setup nested.
    $this->setupNestedParagraphSettings();
    $this->drupalGet("node/add/$content_type");
    $session = $this->getSession();
    $page = $session->getPage();

    // 2a) Add nested paragraph.
    $page->find('xpath', '//input[@data-drupal-selector="field-paragraphs-test-nested-add-more"]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // 2b) Trigger confirmation.
    $session->evaluateScript("jQuery('div[data-drupal-selector=\"edit-field-paragraphs-1-subform-field-paragraphs-0-top\"]').find('.paragraphs-features__delete-confirm').trigger('mousedown')");

    $is_element_visible = $session->evaluateScript("jQuery('div[id^=\"field-paragraphs-1-subform-field-paragraphs-0-item-wrapper\"]').find('.paragraphs-features__delete-confirmation').length === 1");
    $this->assertEquals(TRUE, $is_element_visible, 'Confirmation form should be visible in subform.');

    // 2b) Make sure correct paragraph form is removed.
    $session->evaluateScript("jQuery('div[id^=\"field-paragraphs-1-subform-field-paragraphs-0-item-wrapper\"]').find('.paragraphs-features__delete-confirmation__remove-button').trigger('mousedown')");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $is_element_gone = $session->evaluateScript("jQuery('div[id^=\"field-paragraphs-1-subform-field-paragraphs-0-item-wrapper\"]').length === 0");
    $this->assertEquals(TRUE, $is_element_gone, 'Nested paragraph subform should be gone.');

    // 3) Disable setting to show confirmation on remove action.
    $this->disableDeleteConfirmation($content_type);

    $this->drupalGet("node/add/$content_type");
    $session = $this->getSession();
    $page = $session->getPage();

    // 3a) Custom remove button is not present.
    $this->assertSession()->elementNotExists('xpath', '//button[@class="paragraphs-features__delete-confirm"]');

    // 3b) Instant removal.
    $page->find('xpath', '//button[contains(@class, "paragraphs-dropdown-toggle")]')->click();
    $page->find('xpath', '//input[@data-drupal-selector="field-paragraphs-0-remove"]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Paragraph is gone.
    $this->assertSession()->elementNotExists('xpath', '//div[@id="field-paragraphs-0-item-wrapper"]');

  }

  /**
   * Setup paragraphs field for a content type.
   *
   * @param string $content_type
   *   The content type containing a paragraphs field.
   */
  protected function setupParagraphSettings($content_type) {
    $currentUrl = $this->getSession()->getCurrentUrl();

    // Have a default paragraph, it simplifies the clicking on the edit page.
    $this->config('core.entity_form_display.node.' . $content_type . '.default')
      ->set('content.field_paragraphs.settings.default_paragraph_type', 'test_1')
      ->set('content.field_paragraphs.settings.add_mode', 'button')
      ->save();

    $this->drupalGet($currentUrl);
  }

  /**
   * Setup paragraphs field for nested paragraph.
   *
   */
  protected function setupNestedParagraphSettings() {
    $currentUrl = $this->getSession()->getCurrentUrl();

    // Default paragraph and edit mode
    $this->config('core.entity_form_display.paragraph.test_nested.default')
      ->set('content.field_paragraphs.settings.default_paragraph_type', 'test_1')
      ->set('content.field_paragraphs.settings.edit_mode', 'open')
      ->save();

    $this->drupalGet($currentUrl);
  }

  /**
   * Enable the delete confirmation setting.
   */
  protected function enableDeleteConfirmation($content_type) {
    $currentUrl = $this->getSession()->getCurrentUrl();

    $this->toggleDeleteConfirmation($content_type, 'check');

    $this->drupalGet($currentUrl);
  }

  /**
   * Disable the delete confirmation setting.
   */
  protected function disableDeleteConfirmation($content_type) {
    $currentUrl = $this->getSession()->getCurrentUrl();

    $this->toggleDeleteConfirmation($content_type, 'uncheck');

    $this->drupalGet($currentUrl);
  }

  /**
   * Toggle delete confirmation setting.
   */
  protected function toggleDeleteConfirmation($content_type, $op = 'check') {
    // Test that 3rd party option is available only when modal mode is enabled.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $session = $this->getSession();
    $page = $session->getPage();

    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $action = "{$op}Field";
    $page->$action('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][delete_confirmation]');
    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));
  }

}
