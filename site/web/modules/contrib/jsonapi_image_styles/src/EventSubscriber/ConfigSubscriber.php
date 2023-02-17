<?php

namespace Drupal\jsonapi_image_styles\EventSubscriber;

use Drupal\Core\Cache\CacheableResponseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Associates config cache tag with all JSON:API responses.
 */
class ConfigSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    // Run before
    // \Drupal\jsonapi\EventSubscriber\ResourceResponseSubscriber::onResponse()
    // (priority 128) so config cache tag can be added.
    $events[KernelEvents::RESPONSE][] = ['onResponse', 129];
    return $events;
  }

  /**
   * Associates config cache tag with all JSON:API responses.
   *
   * @param \Symfony\Component\HttpKernel\Event\FilterResponseEvent $event
   *   Response event.
   */
  public function onResponse(FilterResponseEvent $event): void {
    if ($event->getRequest()->getRequestFormat() !== 'api_json') {
      return;
    }

    $response = $event->getResponse();
    if (!$response instanceof CacheableResponseInterface) {
      return;
    }

    $response->getCacheableMetadata()
      ->addCacheTags(['config:jsonapi_image_styles.settings']);
  }

}
