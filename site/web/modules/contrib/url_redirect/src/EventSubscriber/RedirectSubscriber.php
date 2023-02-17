<?php

namespace Drupal\url_redirect\EventSubscriber;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Path\PathMatcher;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Messenger\MessengerInterface;

/**
 * A subscriber to redirect.
 */
class RedirectSubscriber implements EventSubscriberInterface {

  use StringTranslationTrait;

  /**
   * Current path stack service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPathStack;

  /**
   * Path matcher service.
   *
   * @var \Drupal\Core\Path\PathMatcher
   */
  protected $pathMatcher;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManagerInterface;

  /**
   * Current user service.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * RedirectSubscriber constructor.
   *
   * @param \Drupal\Core\Path\CurrentPathStack $currentPathStack
   *   Current path stack service.
   * @param \Drupal\Core\Path\PathMatcher $pathMatcher
   *   Path matcher service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManagerInterface
   *   Entity type manager service.
   * @param \Drupal\Core\Session\AccountProxy $currentUser
   *   Current user service.
   * @param \Drupal\Core\StringTranslation\TranslationManager $stringTranslation
   *   Translation manager service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger.
   */
  public function __construct(CurrentPathStack $currentPathStack, PathMatcher $pathMatcher, EntityTypeManagerInterface $entityTypeManagerInterface, AccountProxy $currentUser, TranslationManager $stringTranslation, MessengerInterface $messenger) {
    $this->currentPathStack = $currentPathStack;
    $this->pathMatcher = $pathMatcher;
    $this->entityTypeManagerInterface = $entityTypeManagerInterface;
    $this->currentUser = $currentUser;
    $this->stringTranslation = $stringTranslation;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // This needs to be executed before RouterListener::onKernelRequest()
    // which has 32 priority Otherwise,
    // that aborts the request if no matching route is found.
    $events[KernelEvents::REQUEST][] = ['requestRedirect', 33];
    $events[KernelEvents::EXCEPTION][] = ['exceptionRedirect', 1];
    return $events;
  }

  /**
   * Perform redirect for access denied exceptions.
   *
   * Without this callback, if a user has a custom page to.
   *
   * display on 403 (access denied) on admin/config/system/site-information.
   *
   * another redirection will take place before the redirection for.
   *
   * the KernelEvents::REQUEST event. It results in infinite redirection.
   *
   * and an error.
   *
   * @param \Symfony\Component\HttpKernel\Event\ExceptionEvent $event
   *   The event to process.
   */
  public function exceptionRedirect(ExceptionEvent $event) {
    $exception = $event->getException();
    if ($exception instanceof HttpExceptionInterface && $event->getException()
      ->getStatusCode() === 403) {
      $this->doRedirect($event);
    }
  }

  /**
   * Perform redirect for http request.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The Event to process.
   */
  public function requestRedirect(RequestEvent $event) {
    $this->doRedirect($event);
  }

  /**
   * Set response to redirection.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   The Event to process.
   */
  protected function doRedirect(RequestEvent $event) {
    global $base_url;
    $path_matches = FALSE;
    // Check URL path in url_redirect entity.
    $path = HTML::escape($event->getRequest()->getRequestUri());
    if ($path == '/') {
      $path = '<front>';
    }
    $wildcards = $this->getPatterns();
    foreach ($wildcards as $wildcard_path) {
      $wildcard_path_load = $this->entityTypeManagerInterface->getStorage('url_redirect')
        ->load($wildcard_path);
      $path_matches = $this->pathMatcher->matchPath($path, $wildcard_path_load->getPath());
      if ($path_matches) {
        $wildcard_path_key = $wildcard_path;
        break;
      }
    }
    $url_redirect = $this->getRedirect($path);
    if (!$url_redirect) {
      $url_redirect = $this->getRedirect(substr($path, 1));
    }
    if ($url_redirect || $path_matches) {
      $id = array_keys($url_redirect);
      if (!$id) {
        $id[0] = $wildcard_path_key;
      }
      $successful_redirect = FALSE;
      /** @var \Drupal\url_redirect\Entity\UrlRedirect $url_redirect_load */
      $url_redirect_load = $this->entityTypeManagerInterface->getStorage('url_redirect')
        ->load($id[0]);
      $check_for = $url_redirect_load->getCheckedFor();
      // Check for Role.
      if ($check_for == 'Role') {
        $role_check_array = $url_redirect_load->getRoles();
        $user_role_check_array = $this->currentUser->getRoles();
        $checked_result = array_intersect($role_check_array, $user_role_check_array);
        $checked_result = $url_redirect_load->get('negate') ?? $checked_result;
        if ($checked_result) {
          $successful_redirect = TRUE;
          if ($this->urlRedirectisExternal($url_redirect_load->getRedirectPath())) {
            $event->setResponse(new TrustedRedirectResponse($url_redirect_load->getRedirectPath(), 301));
          }
          else {
            if (empty($url_redirect_load->getRedirectPath()) || ($url_redirect_load->getRedirectPath() == '<front>')) {
              $event->setResponse(new TrustedRedirectResponse('<front>', 301));
            }
            else {
              $event->setResponse(new TrustedRedirectResponse($base_url . '/' . $url_redirect_load->getRedirectPath(), 301));
            }
          }
        }
      }
      // Check for User.
      elseif ($check_for == 'User') {
        $redirect_users = $url_redirect_load->getUsers();
        if ($redirect_users) {
          $uids = array_column($redirect_users, 'target_id', 'target_id');
          $uid_in_list = isset($uids[$this->currentUser->id()]);
          $redirect_user = ($url_redirect_load->get('negate')) ? $url_redirect_load->get('negate') : $uid_in_list;
          if ($redirect_user) {
            $successful_redirect = TRUE;
            if ($this->urlRedirectisExternal($url_redirect_load->getRedirectPath())) {
              $event->setResponse(new TrustedRedirectResponse($url_redirect_load->getRedirectPath(), 301));
            }
            else {
              if (empty($url_redirect_load->getRedirectPath()) || ($url_redirect_load->getRedirectPath() == '<front>')) {
                $event->setResponse(new TrustedRedirectResponse('<front>', 301));
              }
              else {
                $event->setResponse(new TrustedRedirectResponse($base_url . '/' . $url_redirect_load->getRedirectPath(), 301));
              }
            }
          }
        }
      }
      if ($successful_redirect) {
        $message = $url_redirect_load->getMessage();
        if ($message == $this->t('Yes')) {
          $this->messenger->addMessage($this->t("You have been redirected to '@link_path'.", ['@link_path' => $url_redirect_load->getRedirectPath()]));
        }
      }
    }
  }

  /**
   * Get redirection.
   */
  protected function getRedirect($path) {
    $storage = $this->entityTypeManagerInterface->getStorage('url_redirect');
    $queryResult = $storage->getQuery()
      ->condition('path', $path)
      ->condition('status', 1)
      ->execute();
    return $queryResult;
  }

  /**
   * Get patterns.
   */
  protected function getPatterns() {
    $storage = $this->entityTypeManagerInterface->getStorage('url_redirect');
    $queryResult = $storage->getQuery()
      ->condition("path", "*", "CONTAINS")
      ->condition('status', 1)
      ->execute();
    return $queryResult;
  }

  /**
   * Check for external URL.
   */
  public function urlRedirectisExternal($path) {
    // Check for http:// or https://.
    if (preg_match("`https?://`", $path)) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

}
