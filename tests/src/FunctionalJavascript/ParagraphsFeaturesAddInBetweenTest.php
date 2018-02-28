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
   * Create content type with paragraph field and additional paragraph types.
   *
   * Paragraph types are prefixed with "test_" and for text types index will be
   * used. (fe. "$num_of_test_paragraphs = 3" will provide following test
   * paragraphs: test_1, test_2, test_3.
   *
   * Nested paragraph has type ID: test_nested.
   *
   * @param string $content_type
   *   ID for new testing content type.
   * @param int $num_of_test_paragraphs
   *   Number of additional test paragraph types beside nested one.
   */
  protected function createTestConfiguration($content_type, $num_of_test_paragraphs = 1) {
    $this->addParagraphedContentType($content_type);
    $this->loginAsAdmin([
      "administer content types",
      "administer node form display",
      "edit any $content_type content",
      "create $content_type content",
    ]);

    // Add a paragraph types.
    for ($paragraph_type_index = 1; $paragraph_type_index <= $num_of_test_paragraphs; $paragraph_type_index++) {
      $this->addParagraphsType("test_$paragraph_type_index");
      static::fieldUIAddNewField("admin/structure/paragraphs_type/test_$paragraph_type_index", "text_$paragraph_type_index", 'Text', 'text_long', [], []);
    }

    // Create nested paragraph type.
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
  }

  /**
   * Tests the add widget button with modal form.
   */
  public function testAddInBetweenFeature() {
    // Create paragraph types and content types with required configuration for
    // testing of add in between feature.
    $content_type = 'test_modal_delta';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);

    // Test that 3rd party option is available only when modal mode is enabled.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // By default a non modal add mode should be selected.
    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option:visible').length === 0");
    $this->assertEquals(TRUE, $is_option_visible, 'By default "add in between" option should not be visible.');

    // Check that add in between option is available for modal add mode.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option:visible').length === 1");
    $this->assertEquals(TRUE, $is_option_visible, 'After modal add mode is selected, "add in between" option should be available.');
    $session->executeScript("jQuery('.paragraphs-features__add-in-between__option').prop('checked', true);");
    $is_checked = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option').is(':checked')");
    $this->assertEquals(TRUE, $is_checked, 'Checkbox should be checked.');

    // Check that add in between option is not available for non modal add mode.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'dropdown');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option:visible').length === 0");
    $this->assertEquals(TRUE, $is_option_visible, 'After add mode is change to non modal, "add in between" option should not be visible.');
    $is_checked = $session->evaluateScript("jQuery('.paragraphs-features__add-in-between__option').is(':checked')");
    $this->assertEquals(FALSE, $is_checked, 'After add mode is change to non modal, "add in between" option should be checked out.');

    // Set modal add mode without add in between option.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));

    // Check that default add mode functionality is used.
    $this->drupalGet("node/add/$content_type");
    $this->assertEquals(TRUE, $driver->isVisible('//*[@name="button_add_modal"]'), 'Default "Add Paragraph" button should be visible.');
    $this->assertSession()->elementNotExists('xpath', '//input[contains(@class, "paragraphs-features__add-in-between__button")]');

    // Set modal add mode with add in between option.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->checkField('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][add_in_between]');

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));

    // Check that add in between functionality is used.
    $this->drupalGet("node/add/$content_type");
    $this->assertEquals(FALSE, $driver->isVisible('//*[@name="button_add_modal"]'), 'Default "Add Paragraph" button should be hidden.');
    $this->assertEquals(TRUE, $driver->isVisible('//input[contains(@class, "paragraphs-features__add-in-between__button")]'), 'New add in between button should be visible.');

    // Add a nested paragraph and check that add in between is used only for
    // base paragraphs field, but not for the nested paragraph.
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
    $this->assertEquals(1, count($nested_default_button), "There should be a default button for nested paragraph.");

    // Check first add in between button.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[1]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[add_more][add_modal_form_area][add_more_delta]', '0');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check last add in between button.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[last()]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[add_more][add_modal_form_area][add_more_delta]', '2');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check add in between button between existing paragraphs.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[3]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[add_more][add_modal_form_area][add_more_delta]', '2');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $base_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(5, count($base_buttons), "There should be 5 add in between buttons for base paragraphs.");
    $base_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and not(ancestor::div[contains(@class, "paragraphs-nested")]) and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(0, count($base_default_button), "There should be no default button for base paragraphs.");

    $nested_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and ancestor::div[contains(@class, "paragraphs-nested")]]');
    $this->assertEquals(0, count($nested_buttons), "There should be no add in between buttons for nested paragraph.");
    $nested_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and ancestor::div[contains(@class, "paragraphs-nested")] and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(1, count($nested_default_button), "There should be a default button for nested paragraph.");

    // Set modal add mode without add in between option for base paragraphs.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->uncheckField('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][add_in_between]');
    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));

    // Set modal add mode with add in between option for nested paragraph.
    $this->drupalGet("admin/structure/paragraphs_type/test_nested/form-display");
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->checkField('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][add_in_between]');
    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));

    // Check that add in between functionality is not available for base
    // paragraphs and it's used for nested paragraph.
    $this->drupalGet("node/add/$content_type");

    $session->executeScript("jQuery('.paragraph-type-add-modal-button').trigger('click')");
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_nested")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $base_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(0, count($base_buttons), "There should be no add in between button for base paragraphs.");
    $base_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and not(ancestor::div[contains(@class, "paragraphs-nested")]) and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(1, count($base_default_button), "There should be a default button for base paragraphs.");

    $nested_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and ancestor::div[contains(@class, "paragraphs-nested")]]');
    $this->assertEquals(1, count($nested_buttons), "There should be an add in between button for nested paragraph.");
    $nested_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and ancestor::div[contains(@class, "paragraphs-nested")] and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(0, count($nested_default_button), "There should be no default button for nested paragraph.");

    // Check first add in between button.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[1]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[0][subform][field_paragraphs][add_more][add_modal_form_area][add_more_delta]', '0');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check last add in between button.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[last()]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[0][subform][field_paragraphs][add_more][add_modal_form_area][add_more_delta]', '1');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check add in between button between existing paragraphs.
    $page->find('xpath', '(//*[contains(@class, "paragraphs-features__add-in-between__button")])[2]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertSession()->hiddenFieldValueEquals('field_paragraphs[0][subform][field_paragraphs][add_more][add_modal_form_area][add_more_delta]', '1');
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $base_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(0, count($base_buttons), "There should be no add in between button for base paragraphs.");
    $base_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and not(ancestor::div[contains(@class, "paragraphs-nested")]) and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(1, count($base_default_button), "There should be a default button for base paragraphs.");

    $nested_buttons = $page->findAll('xpath', '//*[contains(@class, "paragraphs-features__add-in-between__button") and ancestor::div[contains(@class, "paragraphs-nested")]]');
    $this->assertEquals(4, count($nested_buttons), "There should be 4 add in between buttons for nested paragraph.");
    $nested_default_button = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-add-modal-button") and ancestor::div[contains(@class, "paragraphs-nested")] and not(ancestor::div[contains(@style,"display: none;")])]');
    $this->assertEquals(0, count($nested_default_button), "There should be no default button for nested paragraph.");
  }

}
