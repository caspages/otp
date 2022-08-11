<?php

namespace Drupal\jsonapi_page_limit\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Session\AccountInterface;
use Drupal\jsonapi\Access\EntityAccessChecker;
use Drupal\jsonapi\Context\FieldResolver;
use Drupal\jsonapi\IncludeResolver;
use Drupal\jsonapi\Query\OffsetPage;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;

class EntityResource extends \Drupal\jsonapi\Controller\EntityResource {

  /**
   * An array of paths and their maximum item count.
   *
   * @var array
   */
  private $sizeMax;

  /**
   * A request for determining the current path.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  private $requestContext;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, ResourceTypeRepositoryInterface $resource_type_repository, RendererInterface $renderer, EntityRepositoryInterface $entity_repository, IncludeResolver $include_resolver, EntityAccessChecker $entity_access_checker, FieldResolver $field_resolver, SerializerInterface $serializer, TimeInterface $time, AccountInterface $user, RequestContext $requestContext, array $size_max) {
    parent::__construct($entity_type_manager, $field_manager, $resource_type_repository, $renderer, $entity_repository, $include_resolver, $entity_access_checker, $field_resolver, $serializer, $time, $user);
    $this->sizeMax = $size_max;
    $this->requestContext = $requestContext;
  }

  protected function getJsonApiParams(Request $request, ResourceType $resource_type) {
    $params = parent::getJsonApiParams($request, $resource_type);
    if ($request->query->has('page')) {
      $params[OffsetPage::KEY_NAME] = new OffsetPage(OffsetPage::DEFAULT_OFFSET, $this->getMax($request->query->get('page')));
    }
    return $params;
  }

  /**
   * Lookup max item count by path, and fallback to 50 if not customized.
   *
   * @param $page
   *   The number of items the querystring.
   *
   * @return int
   *   Max number of items.
   */
  private function getMax($page) {
    $path = $this->requestContext->getPathInfo();
    return isset($this->sizeMax[$path]) ? min($page['limit'], $this->sizeMax[$path]) : OffsetPage::SIZE_MAX;
  }
}
