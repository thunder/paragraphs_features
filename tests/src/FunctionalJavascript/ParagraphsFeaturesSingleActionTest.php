<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

/**
 * Tests the display of single actions according to the dropdown_to_button setting.
 *
 * @group paragraphs_features
 */
class ParagraphsFeaturesSingleActionTest extends ParagraphsFeaturesJavascriptTestBase {

  /**
   * Test display of action.
   */
  public function testSingleActionOption() {
    // Create content type with paragrapjs field.
    $content_type = 'test_single_action';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);

    // 1) Enable setting to show single action as button.
    $this->enableSingleActionAsButton();

    // Setup content type settings, this will disable all paragraph actions.
    $this->setupParagraphSettings($content_type);

    // 1a) Check that the remove action is displayed, but no dropdown toggle.
    $this->drupalGet("node/add/$content_type");
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_remove"]');
    $this->assertSession()->elementNotExists('xpath', '//input[@name="field_paragraphs_0_duplicate"]');
    $this->assertSession()->elementNotExists('xpath', '//div[contains(@class, "paragraphs-dropdown")]');

    // 1b) Enable the duplicate action, it should be shown in a dropdown together
    // with the delete action.
    $this->enableDuplicateAction($content_type);

    // Check that dropdown toggle and remove action are displayed.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_remove"]');
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_duplicate"]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "paragraphs-dropdown")]');

    // 2) Disable setting to show single action as button, actions should
    // always be shown as dropdown now.
    $this->disableSingleActionAsButton();

    // 2a) We still have two actions enabled, dropdown should be shown anyway.
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_remove"]');
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_duplicate"]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "paragraphs-dropdown")]');

    // 2b) Disable the duplicate action, it should still be shown in a dropdown.
    $this->disableDuplicateAction($content_type);
    $this->assertSession()->elementExists('xpath', '//input[@name="field_paragraphs_0_remove"]');
    $this->assertSession()->elementNotExists('xpath', '//input[@name="field_paragraphs_0_duplicate"]');
    $this->assertSession()->elementExists('xpath', '//div[contains(@class, "paragraphs-dropdown")]');
  }

  /**
   * Enables the duplicate action.
   *
   * @param string $content_type
   *   The content type containing a paragraphs field.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function enableDuplicateAction($content_type) {
    $currentUrl = $this->getSession()->getCurrentUrl();
    $page = $this->getSession()->getPage();

    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->checkField('fields[field_paragraphs][settings_edit_form][settings][features][duplicate]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));

    $this->drupalGet($currentUrl);
  }

  /**
   * Disables the duplicate action.
   *
   * @param string $content_type
   *   The content type containing a paragraphs field.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function disableDuplicateAction($content_type) {
    $currentUrl = $this->getSession()->getCurrentUrl();
    $page = $this->getSession()->getPage();

    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->uncheckField('fields[field_paragraphs][settings_edit_form][settings][features][duplicate]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));

    $this->drupalGet($currentUrl);
  }

  /**
   * Setup paragraphs field for a content type.
   *
   * @param string $content_type
   *   The content type containing a paragraphs field.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  protected function setupParagraphSettings($content_type) {
    $currentUrl = $this->getSession()->getCurrentUrl();
    $page = $this->getSession()->getPage();

    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][default_paragraph_type]', 'test_1');

    $page->uncheckField('fields[field_paragraphs][settings_edit_form][settings][features][duplicate]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->uncheckField('fields[field_paragraphs][settings_edit_form][settings][features][add_above]');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));

    $this->drupalGet($currentUrl);
  }

  /**
   * Enable dropdown to button setting.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function enableSingleActionAsButton() {
    $currentUrl = $this->getSession()->getCurrentUrl();
    $page = $this->getSession()->getPage();

    $this->drupalGet("admin/config/content/paragraphs_features");
    $page->checkField('dropdown_to_button');
    $page->pressButton('edit-submit');
    $this->assertSession()->checkboxChecked('dropdown_to_button');

    $this->drupalGet($currentUrl);
  }

  /**
   * Disable dropdown to button setting.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  protected function disableSingleActionAsButton() {
    $currentUrl = $this->getSession()->getCurrentUrl();
    $page = $this->getSession()->getPage();

    $this->drupalGet("admin/config/content/paragraphs_features");
    $page->uncheckField('dropdown_to_button');
    $page->pressButton('edit-submit');
    $this->assertSession()->checkboxNotChecked('dropdown_to_button');

    $this->drupalGet($currentUrl);
  }

}
