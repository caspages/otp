<?php

namespace Drupal\Tests\auditfiles\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;
use Drupal\Core\Url;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\file\Functional\FileFieldCreationTrait;

/**
 * Tests that the "Managed not used" report is reachable with no errors.
 *
 * @group auditfiles
 * @group auditfilesfunctional
 */
class AuditFilesUsedNotReferencedTest extends BrowserTestBase {

  use TestFileCreationTrait;
  use FileFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'file',
    'image',
    'user',
    'auditfiles',
  ];

  /**
   * User with admin privileges.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * User's role ID.
   *
   * @var string
   */
  protected $rid;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create user with permissions to access audit files reports.
    $this->user = $this->drupalCreateUser(['access audit files reports']);
    $all_rids = $this->user->getRoles();
    unset($all_rids[array_search(RoleInterface::AUTHENTICATED_ID, $all_rids)]);
    // Save role IDs.
    $this->rid = reset($all_rids);

    // Creating the content type.
    // Create node based content type with image field.
    $bundle = 'article';
    $fieldName = 'field_image';

    // Create the content type.
    $content_type = NodeType::create([
      'type' => $bundle,
      'name' => 'Test Article',
    ]);
    $content_type->save();

    // Replaces call to $this->createFileField from FileFieldCreationTrait.
    // Can't use FileFieldCreationTrait method because it has "type" hardcoded
    // as "file", and we need type "image".
    $settings = [
      'cardinality' => 1,
      'file_directory' => 'test_images',
      'file_extensions' => 'png gif jpg jpeg txt',
    ];
    $field_storage = FieldStorageConfig::create([
      'entity_type' => 'node',
      'field_name' => $fieldName,
      'type' => 'image',
      'settings' => $settings,
      'cardinality' => $settings['cardinality'],
    ]);
    $field_storage
      ->save();
    $this
      ->attachFileField($fieldName, 'node', $bundle, $settings, []);
    // End of $this->createFileField substitute call
    // End of Create the content type.
    // Next setup step...
    // Create files & nodes.
    $files = $this->getTestFiles('image');
    $counter = 0;
    foreach ($files as $file) {
      $file->filesize = filesize($file->uri);
      $file->status = TRUE;
      $file->filename = $file->uri;
      $newfile = File::create((array) $file);
      $newfile->save();
      $counter++;
      $node = Node::create([
        'type'        => 'article',
        'title'       => 'Sample Node ' . $counter,
        'field_image' => [
          'target_id' => $counter,
          'alt' => 'Sample ' . $counter,
        ],
      ]);
      $node->save();
    }

  }

  /**
   * Tests report page returns correct HTTP response code.
   *
   * 403 for anonymous users (also for users without permission).
   * 200 for authenticated user with 'access audit files reports' perm.
   */
  public function testReportPage() {
    // Form to test.
    $path = URL::fromRoute('auditfiles.audit_files_usednotreferenced');
    // Establish session.
    $session = $this->assertSession();
    // Visit page as anonymous user, should receive a 403.
    $this->drupalGet($path);
    $session->pageTextContains('Access denied');
    $session->statusCodeEquals(403);
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Test that report page returns a 200 response code.
    $this->drupalGet($path);
    $session->pageTextContains("Used not referenced");
    $session->statusCodeEquals(200);
  }

  /**
   * Tests that an orphan file can be deleted.
   *
   * An "orphan" file entity is one with an entry in the
   * file_managed table that has no corresponding file in the
   * file_usage table.
   */
  public function testFileEntityCanBeDeleted() {
    // Delete file_usage entry.
    \Drupal::database()->query("DELETE FROM {node__field_image} WHERE field_image_target_id='1'")->execute();
    \Drupal::database()->query("DELETE FROM {node_revision__field_image} WHERE field_image_target_id='1'")->execute();
    // Form to test.
    $path = URL::fromRoute('auditfiles.audit_files_usednotreferenced');
    // Establish session.
    $session = $this->assertSession();
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Load the report page.
    $this->drupalGet($path);
    // Check for the report title.
    $session->pageTextContains("Used not referenced");
    $session->elementExists('css', '#audit-files-used-not-referenced');
    $session->elementExists('css', '#edit-files-1');
    // Check boxes for file IDs to delete from database, and delete.
    $edit = [
      'edit-files-1' => TRUE,
    ];
    $this->submitForm($edit, 'Delete selected items from the file_usage table');
    // Check for correct confirmation page and submit.
    $session->pageTextContains("Delete these items from the file_usage table?");
    $edit = [];
    $this->submitForm($edit, 'Confirm');
    // Check that target file is no longer listed.
    $session->pageTextContains("Used not referenced");
    $session->pageTextContains("Sucessfully deleted File ID : 1 from the file_usage table.");
    $session->elementNotExists('css', '#edit-files-1');
    $session->pageTextContains("No items found.");
  }

}
