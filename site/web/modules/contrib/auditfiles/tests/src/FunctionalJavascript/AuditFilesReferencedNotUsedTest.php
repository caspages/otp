<?php

namespace Drupal\Tests\auditfiles\FunctionalJavascript;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\user\RoleInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;

/**
 * Tests that the "Referenced not used" report is reachable with no errors.
 *
 * @group auditfiles
 * @group auditfilesfunctionaljs
 */
class AuditFilesReferencedNotUsedTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'field', 'file', 'user', 'auditfiles'];

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
  protected function setUp() {
    parent::setUp();
    // Create user with permissions to manage site configuration and access
    // audit files reports.
    $this->user = $this->drupalCreateUser(['access audit files reports']);
    $all_rids = $this->user->getRoles();
    unset($all_rids[array_search(RoleInterface::AUTHENTICATED_ID, $all_rids)]);
    // Save role IDs.
    $this->rid = reset($all_rids);

    // Create node based content type with image field.
    $bundle = 'article';
    $fieldName = 'field_image';

    // Create the content type.
    $content_type = NodeType::create([
      'type' => $bundle,
      'name' => 'Test Article',
    ]);
    $content_type->save();

    // Define the field storage.
    $fieldStorage = FieldStorageConfig::create([
      'field_name' => $fieldName,
      'entity_type' => 'node',
      'type' => 'file',
      'settings' => [
        'uri_scheme' => 'public',
        'target_type' => 'file',
      ],
      'cardinality' => 1,
      'indexes' => [
        'target_id' => [
          'target_id',
        ],
      ],
    ]);
    $fieldStorage->save();

    // Create the field instance.
    $field = FieldConfig::create([
      'field_storage' => $fieldStorage,
      'bundle' => $bundle,
      'settings' => [
        'file_directory' => 'test_images',
        'file_extensions' => 'png gif jpg jpeg',
      ],
      'handler' => 'default:file',
    ]);
    $field->save();

    // Array of data for file_usage, files_managed, and entity node creation.
    $values = [
      ['file', 'node', 1, 1],
      ['file', 'node', 2, 1],
      ['file', 'node', 3, 1],
    ];

    foreach ($values as $key => $value) {
      // Create file_usage entry.
      \Drupal::database()->insert('file_usage')->fields([
        'fid' => $key + 1,
        'module' => $value[0],
        'type' => $value[1],
        'id' => $value[2],
        'count' => $value[3],
      ])->execute();

      // Create file_managed entry.
      $fileno = $key + 1;
      $path = "public://example_$fileno.png";
      $image = File::create([
        'uri' => $path,
        'status' => TRUE,
      ]);
      $image->save();

      $node = Node::create([
        'type'        => 'article',
        'title'       => 'Sample Node',
        'field_image' => [
          'target_id' => $key + 1,
          'alt' => 'Sample',
          'title' => 'Sample File',
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
    $path = URL::fromRoute('auditfiles.audit_files_referencednotused');
    // Establish session.
    $session = $this->assertSession();
    // Visit page as anonymous user, should get Access Denied message.
    $this->drupalGet($path);
    $session->pageTextContains('Access denied');
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Test that report page returns the report page.
    $this->drupalGet($path);
    $session->pageTextContains('Referenced not used');
  }

  /**
   * Tests that orphan file entity can be added to the file_usage table.
   *
   * An "orphan" file entity is one with an entry in the file_managed
   * and a refererence in existence in a field, but has no corresponding
   * file in the file_usage table.
   */
  public function testFileEntityCanBeAddedToFileUsageTable() {
    // Delete file_usage entry.
    \Drupal::database()->query("DELETE FROM {file_usage} WHERE type='node' AND fid='1'")->execute();
    // Form to test.
    $path = URL::fromRoute('auditfiles.audit_files_referencednotused');
    // Establish session.
    $session = $this->assertSession();
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Load the report page.
    $this->drupalGet($path);
    // Check for the report title.
    $session->pageTextContains("Referenced not used");
    $session->elementExists('css', '#audit-files-referenced-not-used');
    $session->elementExists('xpath', '//*[@id="edit-files"]/tbody/tr[1]/td[1]/div/input');
    $edit = [
      'edit-files-node-field-imagefield-image-target-id1node1' => TRUE,
    ];
    $this->submitForm($edit, 'Add selected items to the file_usage table');
    // Check for correct confirmation page and submit.
    $session->pageTextContains("Add these files to the database?");
    $session->pageTextContains("File ID 1 will be added to the file_usage table.");
    $edit = [];
    $this->submitForm($edit, 'Confirm');
    $session->waitForId('#audit-files-referenced-not-used');
    // Check that target file is no longer listed.
    $session->elementNotExists('css', '#edit-files-node-field-imagefield-image-target-id1node1');
  }

  /**
   * Tests that orphan file entity can be deleted from file_usage.
   *
   * An "orphan" file entity is one with an entry in the file_managed
   * and a refererence in existence in a field, but has no corresponding
   * file in the file_usage table.
   */
  public function testFileEntityCanBeDeletedFromFileUsageTable() {
    // Delete file_usage entry.
    \Drupal::database()->query("DELETE FROM {file_usage} WHERE type='node' AND fid='1'")->execute();

    // Form to test.
    $path = URL::fromRoute('auditfiles.audit_files_referencednotused');
    // Establish session.
    $session = $this->assertSession();
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Load the report page.
    $this->drupalGet($path);
    // Check for the report title.
    $session->pageTextContains("Referenced not used");
    $session->elementExists('css', '#audit-files-referenced-not-used');
    $session->elementExists('xpath', '//*[@id="edit-files"]/tbody/tr[1]/td[1]/div/input');
    $edit = [
      'edit-files-node-field-imagefield-image-target-id1node1' => TRUE,
    ];
    $this->submitForm($edit, 'Delete selected references');
    // Check for correct confirmation page and submit.
    $session->pageTextContains("Delete these files from the server?");
    $session->pageTextContains("File ID 1 will be deleted from the content.");
    $edit = [];
    $this->submitForm($edit, 'Confirm');
    // Check that target file is no longer listed.
    $session->waitForElementVisible('css', '#audit-files-referenced-not-used');
    $session->pageTextContains("Referenced not used");
    $session->elementNotExists('css', '#edit-files-node-field-imagefield-image-target-id1node1');
    $session->pageTextContains("file ID 1 deleted successfully.");
    $session->pageTextContains("No items found.");
  }

}
