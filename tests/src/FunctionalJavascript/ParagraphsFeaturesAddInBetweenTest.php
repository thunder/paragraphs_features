<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\paragraphs\Tests\Classic\ParagraphsCoreVersionUiTestTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests the add in between paragraphs feature.
 *
 * @group paragraphs_features
 */
class ParagraphsFeaturesAddInBetweenTest extends JavascriptTestBase {

  use LoginAdminTrait;
  use FieldUiTestTrait;
  use ParagraphsTestBaseTrait;
  use ParagraphsCoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'block',
    'field',
    'field_ui',
    'link',
    'node',
    'paragraphs',
    'paragraphs_test',
    'paragraphs_features',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Place the breadcrumb, tested in fieldUIAddNewField().
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests the add widget button with modal form.
   */
  public function testWidgetSettings() {
    $content_type = 'test_modal_delta';

    $this->addParagraphedContentType($content_type);
    $this->loginAsAdmin([
      "administer content types",
      "administer node form display",
      "edit any $content_type content",
      "create $content_type content",
    ]);

    // Add a Paragraph types.
    $this->addParagraphsType('test_1');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/test_1', 'text_1', 'Text', 'text_long', [], []);

    // Create paragraph type Nested test.
    $this->addParagraphsType('test_nested');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/test_nested', 'paragraphs', 'Paragraphs', 'entity_reference_revisions', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);

    // Set the settings for the field in the nested paragraph.
    $component = [
      'type' => 'paragraphs',
      'region' => 'content',
      'settings' => [
        'edit_mode' => 'closed',
        'add_mode' => 'modal',
        'form_display_mode' => 'default',
      ],
    ];
    EntityFormDisplay::load('paragraph.test_nested.default')
      ->setComponent('field_paragraphs', $component)
      ->save();

    // Set the add mode on the content type to modal form widget.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option:visible').length === 0");
    $this->assertEquals(TRUE, $is_option_visible, 'By default "add in between" option should not be visible.');

    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option:visible').length === 1");
    $this->assertEquals(TRUE, $is_option_visible, 'After modal add mode is selected, "add in between" option should be available.');
    $session->executeScript("jQuery('.paragraphs-features__add-in-between__option').prop('checked', true);");
    $is_checked = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option').is(':checked')");
    $this->assertEquals(TRUE, $is_checked, 'Checkbox should be checked.');

    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'dropdown');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option:visible').length === 0");
    $this->assertEquals(TRUE, $is_option_visible, 'After add mode is change to non modal, "add in between" option should not be visible.');
    $is_checked = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option').is(':checked')");
    $this->assertEquals(FALSE, $is_checked, 'After add mode is change to non modal, "add in between" option should be checked out.');

    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));

    // Add a paragraphed test.
    $this->drupalGet("node/add/$content_type");

    $this->assertEquals(TRUE, $driver->isVisible('//*[@name="button_add_modal"]'), 'Default "Add Paragraph" button should be visible.');
    $this->assertSession()->elementNotExists('xpath', '//input[contains(@class, "paragraphs-features__add-in-between__button")]');

    // Set the add mode on the content type to modal form widget.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->checkField('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][add_in_between]');

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));

    // Add a paragraphed test.
    $this->drupalGet("node/add/$content_type");
    $page->fillField('title[0][value]', 'Test modal add widget delta');

    $this->assertEquals(FALSE, $driver->isVisible('//*[@name="button_add_modal"]'), 'Default "Add Paragraph" button should be hidden.');
    $this->assertEquals(TRUE, $driver->isVisible('//input[contains(@class, "paragraphs-features__add-in-between__button")]'), 'New add in between button should be visible.');

    // Add a nested paragraph with the add widget - use negative delta.
    $session->executeScript("jQuery('.paragraphs-features__add-in-between__button').trigger('click')");
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_nested")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $base_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(2, count($base_buttons), "There should be 2 add in between buttons for base paragraphs.");
    $base_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and not(ancestor::div[contains(@class, "paragraphs-nested")]) and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(0, count($base_default_button), "There should be no default button for base paragraphs.");

    $nested_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and ancestor::div[contains(@class, "paragraphs-nested")]]');
    $this->assertEquals(0, count($nested_buttons), "There should be no add in between buttons for nested paragraph.");
    $nested_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and ancestor::div[contains(@class, "paragraphs-nested")] and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(1, count($nested_default_button), "There should be a default button in nested paragraph.");

    // Check click on first add in between button.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[1]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[add_more][add_modal_form_area][add_more_delta]', '0');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check click on last add in between button.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[last()]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[add_more][add_modal_form_area][add_more_delta]', '2');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check click on some add in between button.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[2]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[add_more][add_modal_form_area][add_more_delta]', '1');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

}
