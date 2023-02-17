<?php

namespace Drupal\eu_cookie_compliance\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeExtensionList;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\domain\Entity\Domain;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Clear cache bins for the cookie banner.
 */
class EuCcClearCache {

  /**
   * The MIME type guesser.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The domain storage.
   *
   * @var \Drupal\domain\DomainStorage
   */
  protected $domainStorage;

  /**
   * The theme extension list.
   *
   * @var \Drupal\Core\Extension\ThemeExtensionList
   */
  protected $themeExtensionList;

  /**
   * The render cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheRender;

  /**
   * Creates a new VendorFileDownloadController instance.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The Module handler.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $domain_storage
   *   The domain storage.
   * @param \Drupal\Core\Extension\ThemeExtensionList $theme_extension_list
   *   The theme extension list.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_render
   *   The cache interface.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(ModuleHandlerInterface $module_handler, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $domain_storage, ThemeExtensionList $theme_extension_list, CacheBackendInterface
$cache_render) {
    $this->moduleHandler = $module_handler;
    $this->languageManager = $language_manager;
    if ($this->moduleHandler->moduleExists('domain')) {
      $this->domainStorage = $domain_storage->getStorage('domain');
    }
    else {
      $this->domainStorage = FALSE;
    }
    $this->themeExtensionList = $theme_extension_list;
    $this->cacheRender = $cache_render;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('module_handler'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('extension.list.theme'),
      $container->get('cache.render')
    );
  }

  /**
   * Clear cache.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function clearCache() {
    $languages = $this->languageManager->getLanguages();

    foreach ($languages as $language) {
      // Check if the domain should be added to the cache id.
      $moduleHandler = $this->moduleHandler;
      if ($moduleHandler->moduleExists('domain') && count($this->domainStorage->getQuery()->range(0, 1)->execute()) > 0) {
        $domains = Domain::loadMultiple();

        foreach ($domains as $domain) {
          $themes = $this->themeExtensionList->getList();

          foreach ($themes as $theme) {
            $cid = 'eu_cookie_compliance_data:' . $language->getId() . ':' . $domain->id() . ':' . $theme->getName();
            $this->moduleHandler->alter('eu_cookie_compliance_cid', $cid);
            $this->cacheRender->delete($cid);
          }
        }
      }
      else {
        $themes = $this->themeExtensionList->getList();
        foreach ($themes as $theme) {
          $cid = 'eu_cookie_compliance_data:' . $language->getId() . ':' . $theme->getName();
          $this->moduleHandler->alter('eu_cookie_compliance_cid', $cid);
          $this->cacheRender->delete($cid);
        }
      }
    }
  }

}
