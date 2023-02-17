<?php

namespace Drupal\Tests\auditfiles\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\RoleInterface;
use Drupal\Core\Url;
use Drupal\Tests\TestFileCreationTrait;
use Drupal\file\Entity\File;

/**
 * Tests that the "Managed not used" report is reachable with no errors.
 *
 * @group auditfiles
 * @group auditfilesfunctional
 */
class AuditFilesManagedNotUsedTest extends BrowserTestBase {

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
    for ($i = 1; $i < 4; $i++) {
      $path = "public://example_$i.png";
      $image = File::create([
        'uri' => $path,
        'status' => TRUE,
      ]);
      $image->save();
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
    $path = URL::fromRoute('auditfiles.audit_files_managednotused');
    // Establish session.
    $session = $this->assertSession();
    // Visit page as anonymous user, should receive a 403.
    $this->drupalGet($path);
    $session->statusCodeEquals(403);
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Test that report page returns a 200 response code.
    $this->drupalGet($path);
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
    $path = URL::fromRoute('auditfiles.audit_files_managednotused');
    // Establish session.
    $session = $this->assertSession();
    // Log in as admin user.
    $this->drupalLogin($this->user);
    // Load the report page.
    $this->drupalGet($path);
    // Check for the report title.
    $session->pageTextContains("Managed not used");
    $session->elementExists('css', '#edit-files-1');
    // Check box for file ID to delete from database, and delete.
    $edit = [
      'edit-files-1' => TRUE,
    ];
    $this->submitForm($edit, 'Delete selected items from the file_managed table');
    // Check for correct confirmation page and submit.
    $session->pageTextContains("Delete these items from the file_managed table?");
    $session->pageTextContains('example_1.png');
    $edit = [];
    $this->submitForm($edit, 'Confirm');
    // Check that target file is no longer listed.
    $this->drupalGet($path);
    $session->pageTextContains("Managed not used");
    $session->pageTextNotContains('example_1.png');
    $session->elementNotExists('css', '#edit-files-1');
  }

}
