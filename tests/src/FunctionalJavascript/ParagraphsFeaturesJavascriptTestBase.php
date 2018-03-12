<?php

namespace Drupal\Tests\paragraphs_features\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;
use Drupal\paragraphs\Tests\Classic\ParagraphsCoreVersionUiTestTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\LoginAdminTrait;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Base class for Javascript tests for paragraphs features module.
 *
 * @package Drupal\Tests\paragraphs_features\FunctionalJavascript
 */
abstract class ParagraphsFeaturesJavascriptTestBase extends JavascriptTestBase {

  use LoginAdminTrait;
  use FieldUiTestTrait;
  use ParagraphsTestBaseTrait;
  use ParagraphsCoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'block',
    'field',
    'field_ui',
    'link',
    'node',
    'paragraphs',
    'paragraphs_test',
    'paragraphs_features',
    'paragraphs_features_test',
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
      'cardinality' => 'number',
      'cardinality_number' => '4',
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

}
