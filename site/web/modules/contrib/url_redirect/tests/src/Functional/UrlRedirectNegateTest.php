<?php

namespace Drupal\Tests\url_redirect\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the negation option for user and role redirect condition.
 *
 * @group url_redirect
 */
class UrlRedirectNegateTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'url_redirect', 'user'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin user that can create content.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $adminUser;

  /**
   * First test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $userOne;

  /**
   * Second test user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $userTwo;

  /**
   * First test role.
   *
   * @var string
   */
  protected $ridOne;

  /**
   * Second test role.
   *
   * @var string
   */
  protected $ridTwo;

  /**
   * Path to visit on site where redirection is expected.
   *
   * @var string
   */
  protected $sourcePath;

  /**
   * Destination path after redirection.
   *
   * @var string
   */
  protected $destPath;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create roles.
    $this->ridOne = $this->drupalCreateRole([], 'custom_role_1', 'custom_role_1');
    $this->ridTwo = $this->drupalCreateRole([], 'custom_role_2', 'custom_role_2');

    // Create user with role one.
    $this->userOne = $this->drupalCreateUser();
    $this->userOne->addRole($this->ridOne);
    $this->userOne->save();

    $this->userTwo = $this->drupalCreateUser();
    $this->userTwo->addRole($this->ridTwo);
    $this->userTwo->save();

    // Create a node for source path and one for redirection path.
    $this->drupalCreateContentType(['type' => 'page', 'name' => 'Basic page']);
    $permissions = [
      'create page content',
      'access content',
      'access url redirect settings page',
      'access url redirect edit page',
    ];
    $this->adminUser = $this->drupalCreateUser($permissions);
    $sourceNode = $this->drupalCreateNode([
      'type' => 'page',
      'uid' => $this->adminUser->id(),
    ]);
    $destNode = $this->drupalCreateNode([
      'type' => 'page',
      'uid' => $this->adminUser->id(),
    ]);

    $this->sourcePath = "/node/" . $sourceNode->id();
    $this->destPath = "/node/" . $destNode->id();

  }

  /**
   * {@inheritdoc}
   */
  public function testRoleNegation() {
    // Add Role redirect with negation.
    $this->drupalLogin($this->adminUser);
    $this->addUrlRedirect($this->sourcePath, $this->destPath, 'Role', [$this->ridOne], '1', 'No', '1');

    // Confirm user with role one is not redirected.
    $this->drupalLogin($this->userOne);
    $this->drupalGet($this->sourcePath);
    $this->assertSession()->addressNotEquals($this->destPath);

    // Confirm user with role two is redirected.
    $this->drupalLogin($this->userTwo);
    $this->drupalGet($this->sourcePath);
    $this->assertSession()->addressNotEquals($this->destPath);
  }

  /**
   * {@inheritdoc}
   */
  public function testUserNegation() {
    // Add User redirect with negation.
    $this->drupalLogin($this->adminUser);
    $this->addUrlRedirect($this->sourcePath, $this->destPath, 'User', [$this->userOne->getAccountName()], '1', 'No', '1');

    // Confirm user one is not redirected.
    $this->drupalLogin($this->userOne);
    $this->drupalGet($this->sourcePath);
    $this->assertSession()->addressNotEquals($this->destPath);

    // Confirm user two is redirected.
    $this->drupalLogin($this->userTwo);
    $this->drupalGet($this->sourcePath);
    $this->assertSession()->addressNotEquals($this->destPath);
  }

  /**
   * Create a redirect.
   */
  private function addUrlRedirect($path, $redirect_path, $check_for, $check_value, $negate, $message, $status) {
    $formValues = [];
    $formValues['path'] = $path;
    $formValues['redirect_path'] = $redirect_path;
    $formValues['redirect_for'] = $check_for;
    $formValues['negate'] = $negate;
    $formValues['message'] = $message;
    $formValues['status'] = $status;
    if ($check_for === 'User') {
      $formValues['user'] = implode(',', $check_value);
    }
    else {
      $formValues['roles[]'] = $check_value;
    }
    $this->drupalPostForm('admin/config/system/url_redirect/add', $formValues, t('Save'));
  }

}
