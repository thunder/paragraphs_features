<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

use Drupal\editor\Entity\Editor;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests the paragraph text split feature.
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
   * Create CKEditor for testing of CKEditor integration.
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
   * Get CKEditor ID, that can be used to get CKEditor objects in JavaScript.
   *
   * @param int $paragraph_index
   *   Text paragraph index.
   *
   * @return string
   *   Returns Id for CKEditor.
   */
  protected function getCkEditorId($paragraph_index) {
    return $this->getSession()->getPage()->find('xpath', '//*[@data-drupal-selector="edit-field-paragraphs-' . $paragraph_index . '"]//textarea')->getAttribute('id');
  }

  /**
   * Create new text paragraph to end of paragraphs list.
   *
   * @param int $index
   *   Index of new paragraph.
   * @param string $text
   *   Text that will be filled to text field with CKEditor.
   *
   * @return string
   *   Returns CKEditor ID.
   *
   * @throws \Behat\Mink\Exception\DriverException
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   */
  protected function createNewTextParagraph($index, $text) {
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    $page->find('xpath', '(//*[contains(@class, "paragraph-type-add-modal-button")])[1]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    $ck_editor_id = $this->getCkEditorId($index);
    $driver->executeScript("CKEDITOR.instances['$ck_editor_id'].insertHtml('$text');");

    return $ck_editor_id;
  }

  /**
   * Click on split text button for paragraphs text field.
   *
   * @param int $ck_editor_index
   *   Index of CKEditor field in paragraphs.
   */
  protected function clickParagraphSplitButton($ck_editor_index) {
    $this->getSession()->executeScript("jQuery('.cke_button__splittext:nth($ck_editor_index)').trigger('click');");
    $this->assertSession()->assertWaitOnAjaxRequest();
  }

  /**
   * Test split text feature.
   */
  public function testSplitTextFeature() {
    // Create paragraph types and content types with required configuration for
    // testing of split text feature.
    $content_type = 'test_split_text';

    // Create nested paragraph with addition of one text test paragraph.
    $this->createTestConfiguration($content_type, 1);
    $this->createEditor();

    // Test that 3rd party option is available only when modal mode is enabled.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $session = $this->getSession();
    $page = $session->getPage();
    $driver = $session->getDriver();

    // Edit form display settings.
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // By default a non modal add mode should be selected.
    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__split-text__option:visible').length === 0");
    $this->assertEquals(TRUE, $is_option_visible, 'By default "split text" option should not be visible.');

    // Check that split text option is available for modal add mode.
    $page->selectFieldOption('fields[field_paragraphs][settings_edit_form][settings][add_mode]', 'modal');
    $session->executeScript("jQuery('[name=\"fields[field_paragraphs][settings_edit_form][settings][add_mode]\"]').trigger('change');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    $is_option_visible = $session->evaluateScript("jQuery('.paragraphs-features__split-text__option:visible').length === 1");
    $this->assertEquals(TRUE, $is_option_visible, 'After modal add mode is selected, "split text" option should be available.');
    $page->checkField('fields[field_paragraphs][settings_edit_form][third_party_settings][paragraphs_features][split_text]');
    $is_checked = $session->evaluateScript("jQuery('.paragraphs-features__split-text__option').is(':checked')");
    $this->assertEquals(TRUE, $is_checked, 'Checkbox should be checked.');

    $this->drupalPostForm(NULL, [], 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->drupalPostForm(NULL, [], t('Save'));

    // Case 1 - simple text split.
    $paragraph_content_0 = '<p>Content that will be in the first paragraph after the split.</p>';
    $paragraph_content_1 = '<p>Content that will be in the second paragraph after the split.</p>';

    // Check that split text functionality is used.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content_0 . $paragraph_content_1);

    // Make split of created text paragraph.
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id'].getSelection(); selection.selectElement(selection.root.getChild(1));");
    $this->clickParagraphSplitButton(0);

    // Validate split results.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    static::assertEquals(
      $paragraph_content_0 . PHP_EOL . PHP_EOL . '<p>&nbsp;</p>' . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_0'].getData();")
    );
    static::assertEquals(
      $paragraph_content_1 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_1'].getData();")
    );

    // Case 2 - split text works after removal of paragraph.
    $this->drupalGet("node/add/$content_type");
    $this->createNewTextParagraph(0, '');

    // Remove the paragraph.
    $driver->executeScript("jQuery('[name=\"field_paragraphs_0_remove\"]').trigger('mousedown');");
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Create new text paragraph.
    $ck_editor_id = $this->createNewTextParagraph(1, $paragraph_content_0 . $paragraph_content_1);

    // Make split of text paragraph.
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id'].getSelection(); selection.selectElement(selection.root.getChild(1));");
    $this->clickParagraphSplitButton(0);

    // Validate split results.
    $ck_editor_id_0 = $this->getCkEditorId(1);
    $ck_editor_id_1 = $this->getCkEditorId(2);
    static::assertEquals(
      $paragraph_content_0 . PHP_EOL . PHP_EOL . '<p>&nbsp;</p>' . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_0'].getData();")
    );
    static::assertEquals(
      $paragraph_content_1 . PHP_EOL,
      $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_1'].getData();")
    );

    // Case 3 - add of new paragraph after text split.
    $this->drupalGet("node/add/$content_type");
    $ck_editor_id = $this->createNewTextParagraph(0, $paragraph_content_0 . $paragraph_content_1);

    // Make split of text paragraph.
    $driver->executeScript("var selection = CKEDITOR.instances['$ck_editor_id'].getSelection(); selection.selectElement(selection.root.getChild(1));");
    $this->clickParagraphSplitButton(0);

    // Set new data to both split paragraphs.
    $paragraph_content_0_new = '<p>Content that will be placed into the first paragraph after split.</p>';
    $paragraph_content_1_new = '<p>Content that will be placed into the second paragraph after split.</p>';
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    $driver->executeScript("CKEDITOR.instances[\"$ck_editor_id_0\"].setData(\"$paragraph_content_0_new\");");
    $driver->executeScript("CKEDITOR.instances[\"$ck_editor_id_1\"].setData(\"$paragraph_content_1_new\");");

    // Add new text paragraph.
    $this->createNewTextParagraph(2, '');

    // Test if all texts are in the correct paragraph.
    $ck_editor_id_0 = $this->getCkEditorId(0);
    $ck_editor_id_1 = $this->getCkEditorId(1);
    $ck_editor_id_2 = $this->getCkEditorId(2);
    $ck_editor_content_0 = $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_0'].getData();");
    $ck_editor_content_1 = $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_1'].getData();");
    $ck_editor_content_2 = $driver->evaluateScript("CKEDITOR.instances['$ck_editor_id_2'].getData();");

    static::assertEquals($paragraph_content_0_new . PHP_EOL, $ck_editor_content_0);
    static::assertEquals($paragraph_content_1_new . PHP_EOL, $ck_editor_content_1);
    static::assertEquals('', $ck_editor_content_2);
  }

}
