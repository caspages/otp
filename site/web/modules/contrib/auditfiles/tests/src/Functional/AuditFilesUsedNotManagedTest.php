<?php

namespace Drupal\Tests\auditfiles\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;
use Drupal\Core\Url;
use Drupal\Tests\TestFileCreationTrait;

/**
 * Tests that the "Used not managed" report is reachable with no errors.
 *
 * @group auditfiles
 * @group auditfilesfunctional
 */
class AuditFilesUsedNotManagedTest extends BrowserTestBase {

  use TestFileCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'file', 'user', 'auditfiles'];

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
    // Create user with permissions to manage site configuration and access
    // audit files reports.
    $this->user = $this->drupalCreateUser(['access audit files reports']);
    $all_rids = $this->user->getRoles();
    unset($all_rids[array_search(RoleInterface::AUTHENTICATED_ID, $all_rids)]);
    // Save role IDs.
    $this->rid = reset($all_rids);

    // Create File Entities.
    $values = [
      [1, 'file', 'media', 1, 1],
      [2, 'file', 'media', 3, 1],
      [3, 'file', 'media', 5, 1],
    ];
    foreach ($values as $value) {
      \Drupal::database()->insert('file_usage')->fields([
        'fid' => $value[0],
        'module' => $value[1],
        'type' => $value[2],
        'id' => $value[3],
        'count' => $value[4],
      ])->execute();
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
    $path = URL::fromRoute('auditfiles.audit_files_usednotmanaged');
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
    $session->pageTextContains('Used not managed');
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
    // Form to test.
    $path = URL::fromRoute('auditfiles.audit_files_usednotmanaged');
    // Establish session.
    $session = $this->assertSession();
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Load the report page.
    $this->drupalGet($path);
    // Check for the report title.
    $session->pageTextContains("Used not managed");
    $session->pageTextContains("Found at least 3 files in the file_usage table that are not in the file_managed table.");
    $session->elementExists('css', '#audit-files-used-not-managed');
    $session->elementExists('css', '#edit-files-1');
    // Check box for file ID to delete from database, and delete.
    $edit = [
      'edit-files-1' => TRUE,
    ];
    $this->submitForm($edit, 'Delete selected items from the file_usage table');
    // Check for correct confirmation page and submit.
    $session->pageTextContains("Delete these items from the file_usage table?");
    $session->pageTextContains("File ID 1 will be deleted from the file_usage table.");
    $edit = [];
    $this->submitForm($edit, 'Confirm');
    // Check that target file is no longer listed.
    $session->pageTextContains("Used not managed");
    $session->pageTextContains("Sucessfully deleted File ID : 1 from the file_usages table.");
    $session->pageTextContains("Found at least 2 files in the file_usage table that are not in the file_managed table.");
    $session->elementNotExists('css', '#edit-files-1');
  }

}
