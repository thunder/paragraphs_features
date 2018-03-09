<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

use Behat\Mink\Element\DocumentElement;
use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests the paragraph split module integration.
 *
 * @group paragraphs_features
 */
class ParagraphsFeaturesSplitTextTest extends ParagraphsFeaturesJavascriptTestBase {

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
    'ckeditor',
    'paragraphs',
    'paragraphs_test',
    'paragraphs_features',
    'paragraphs_features_test',
  ];

  /**
   * Create CKEditor for integration test.
   */
  protected function createEditor() {
    // Create a text format and associate CKEditor.
    $filtered_html_format = FilterFormat::create([
      'format' => 'filtered_html',
      'name' => 'Filtered HTML',
      'weight' => 0,
    ]);
    $filtered_html_format->save();

    Editor::create([
      'format' => 'filtered_html',
      'editor' => 'ckeditor',
    ])->save();

    // After createTestConfiguration, $this->admin_user will be created by
    // LoginAdminTrait used in base class.
    $this->admin_user->addRole($this->createRole(['use text format filtered_html']));
    $this->admin_user->save();
  }

  /**
   * Click on split text button for paragraphs text field.
   *
   * @param int $ck_editor_index
   *   Index of CKEditor field in paragraphs.
   */
  protected function clickParagraphSplitButton($ck_editor_index) {
    $this->getSession()->executeScript("jQuery('.cke_button__splittext')[$ck_editor_index].click();");
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Get CKEditor ID, that can be used to get CKEditor objects in JavaScript.
   *
   * @param \Behat\Mink\Element\DocumentElement $page
   *   Site page object.
   * @param int $paragraph_index
   *   Text paragraph index.
   *
   * @return string
   *   Returns Id for CKEditor.
   */
  protected function getCkEditorId(DocumentElement $page, $paragraph_index) {
    return $page->find('xpath', '//*[@data-drupal-selector="edit-field-paragraphs-' . $paragraph_index . '"]//textarea')->getAttribute('id');
  }

  /**
   * Test split of paragraph before a selection.
   */
  public function testParagraphSplitBefore() {
    // Create paragraph types and content types with required configuration for
    // testing of add in between feature.
    $content_type = 'test_modal_delta';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);
    $this->createEditor();

    // Test that 3rd party option is available only when modal mode is enabled.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $page->selectFieldOption('fields[field_paragraphs][type]', 'extended_test_paragraphs');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // By default a non modal add mode should be selected.
    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__split-text__option:visible').length === 0");
    $this->assertEquals(TRUE, $is_option_visible, 'By default "add in between" option should not be visible.');

    // Check that add in between option is available for modal add mode.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__split-text__option:visible').length === 1");
    $this->assertEquals(TRUE, $is_option_visible, 'After modal add mode is selected, "add in between" option should be available.');
    $page->checkField('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][split_text]');
    $is_checked = $session->evaluateScript("jQuery('.paragraphs-features__split-text__option').is(':checked')");
    $this->assertEquals(TRUE, $is_checked, 'Checkbox should be checked.');

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->drupalPostForm(NULL, [], t('Save'));

    // Check that add in between functionality is used.
    $this->drupalGet("node/add/$content_type");

    // Check first add in between button.
    $page->find('xpath', '(//*[contains(@class, "paragraph-type-add-modal-button")])[1]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Fill text field with text.
    $paragraph_content_0 = '<p>Content that will be in the first paragraph after the split.</p>';
    $paragraph_content_1 = '<p>Content that will be in the second paragraph after the split.</p>';

    $text = $paragraph_content_0 . $paragraph_content_1;
    $ck_editor_id = $this->getCkEditorId($page, 0);
    $driver->executeScript("CKEDITOR.instances['$ck_editor_id'].insertHtml('$text');");
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id'].getSelection(); selection.selectElement(selection.root.getChild(1));");
    $this->clickParagraphSplitButton(0);

    // Validate split results.
    $ck_editor_id_0 = $this->getCkEditorId($page, 0);
    $ck_editor_id_1 = $this->getCkEditorId($page, 1);
    $ck_editor_content_0 = $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_0'].getData();");
    $ck_editor_content_1 = $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_1'].getData();");

    static::assertEquals($paragraph_content_0 . PHP_EOL . PHP_EOL . '<p>&nbsp;</p>' . PHP_EOL, $ck_editor_content_0);
    static::assertEquals($paragraph_content_1 . PHP_EOL, $ck_editor_content_1);
  }

}
