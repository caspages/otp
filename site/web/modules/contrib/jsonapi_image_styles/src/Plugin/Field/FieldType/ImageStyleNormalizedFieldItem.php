<?php

namespace Drupal\jsonapi_image_styles\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'image_style_uri' field type.
 *
 * @FieldType(
 *   id = "image_style_uri",
 *   label = @Translation("Image style uri"),
 *   description = @Translation("Normalized image style paths"),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\jsonapi_image_styles\Plugin\Field\FieldType\ImageStyleNormalizedFieldItemList",
 * )
 */
class ImageStyleNormalizedFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['url'] = DataDefinition::create('any')
      ->setLabel(t('URI'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('url')->getValue();
    return $value === serialize([]);
  }

}
