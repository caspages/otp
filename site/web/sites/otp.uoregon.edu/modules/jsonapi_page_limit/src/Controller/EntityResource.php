<?php

namespace Drupal\jsonapi_page_limit\Controller;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Path\PathMatcherInterface;
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

  /**
   * The path matcher service, for comparing paths.
   *
   * @var \Drupal\Core\Path\PathMatcherInterface
   */
  private $pathMatcher;

  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $field_manager, ResourceTypeRepositoryInterface $resource_type_repository, RendererInterface $renderer, EntityRepositoryInterface $entity_repository, IncludeResolver $include_resolver, EntityAccessChecker $entity_access_checker, FieldResolver $field_resolver, SerializerInterface $serializer, TimeInterface $time, AccountInterface $user, RequestContext $request_context, PathMatcherInterface $path_matcher, array $size_max) {
    parent::__construct($entity_type_manager, $field_manager, $resource_type_repository, $renderer, $entity_repository, $include_resolver, $entity_access_checker, $field_resolver, $serializer, $time, $user);
    $this->sizeMax = $size_max;
    $this->requestContext = $request_context;
    $this->pathMatcher = $path_matcher;
  }

  /**
   * {@inheritdoc}
   */
  protected function getJsonApiParams(Request $request, ResourceType $resource_type) {
    $params = parent::getJsonApiParams($request, $resource_type);
    $page_params = $request->query->get(OffsetPage::KEY_NAME);

    // Only handle requests where a ?page[limit] has been specified.
    if (is_array($page_params) && isset($page_params[OffsetPage::SIZE_KEY])) {
      $offset = $page_params[OffsetPage::OFFSET_KEY] ?? OffsetPage::DEFAULT_OFFSET;
      $params[OffsetPage::KEY_NAME] = new OffsetPage(
        $offset,
        $this->getMax($page_params)
      );
    }
    return $params;
  }

  /**
   * Lookup max item count by path, and fallback to 50 if not customized.
   *
   * @param array $page_params
   *   The page parameters from the url query.
   *
   * @return int
   *   Max number of items.
   */
  private function getMax(array $page_params) {
    $path = $this->requestContext->getPathInfo();
    $matches = array_filter($this->sizeMax, function($key) use ($path) {
      return $this->pathMatcher->matchPath($path, $key);
    }, ARRAY_FILTER_USE_KEY);
    // In case of multiple matches, use the first match.
    $size_max = reset($matches) ?? OffsetPage::SIZE_MAX;
    return min($page_params['limit'], $size_max);
  }
}
