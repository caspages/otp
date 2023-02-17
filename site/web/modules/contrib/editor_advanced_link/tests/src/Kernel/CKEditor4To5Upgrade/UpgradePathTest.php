<?php

declare(strict_types = 1);

namespace Drupal\Tests\editor_advanced_link\Kernel\CKEditor4To5Upgrade;

use Drupal\editor\Entity\Editor;
use Drupal\editor_advanced_link\Plugin\CKEditor5Plugin\AdvancedLink;
use Drupal\filter\Entity\FilterFormat;
use Drupal\Tests\ckeditor5\Kernel\SmartDefaultSettingsTest;

/**
 * @covers \Drupal\linkit\Plugin\CKEditor4To5Upgrade\AdvancedLink
 * @group editor_advanced_link
 * @group ckeditor5
 * @requires module ckeditor5
 * @internal
 */
class UpgradePathTest extends SmartDefaultSettingsTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'editor_advanced_link',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create test FilterFormat config entities: one per option to test in
    // isolation, plus one to test with the default configuration (no attributes
    // enabled), plus one with ALL attributes enabled but with additional
    // attributes not supported by AdvancedLink.
    $options = AdvancedLink::SUPPORTED_ATTRIBUTES;
    $get_filter_config = function(string $allowed_html_addition): array {
      return [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a href> ' . $allowed_html_addition,
          ],
        ],
      ];
    };
    foreach (array_keys($options) as $option) {
      $string_representation = AdvancedLink::getAllowedHtmlForSupportedAttribute($option);
      FilterFormat::create([
        'format' => "advanced_link__$option",
        'name' => $string_representation,
        'filters' => $get_filter_config($string_representation),
      ])->setSyncing(TRUE)->save();
    }
    FilterFormat::create([
      'format' => 'advanced_link__none',
      'name' => 'None, just plain link',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a href>',
          ],
        ],
      ],
    ])->setSyncing(TRUE)->save();
    FilterFormat::create([
      'format' => 'advanced_link__all_and_more',
      'name' => '<a aria-label FOO title class id BAR="baz" target="_blank" rel>',
      'filters' => [
        'filter_html' => [
          'status' => 1,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a aria-label href FOO title class id BAR="baz" target="_blank" rel>',
          ],
        ],
      ],
    ])->setSyncing(TRUE)->save();

    // Create matching Text Editors.
    $cke4_settings = [
      'toolbar' => [
        'rows' => [
          0 => [
            [
              'name' => 'Basic Formatting',
              'items' => [
                'Bold',
                'Format',
                'DrupalLink'
              ],
            ],
          ],
        ],
      ],
      'plugins' => [],
    ];
    foreach (array_keys($options) as $option) {
      Editor::create([
        'format' => "advanced_link__$option",
        'editor' => 'ckeditor',
        'settings' => $cke4_settings,
      ])->setSyncing(TRUE)->save();
    }
    Editor::create([
      'format' => 'advanced_link__none',
      'editor' => 'ckeditor',
      'settings' => $cke4_settings,
    ])->setSyncing(TRUE)->save();
    Editor::create([
      'format' => 'advanced_link__all_and_more',
      'editor' => 'ckeditor',
      'settings' => $cke4_settings,
    ])->setSyncing(TRUE)->save();
  }

  /**
   * {@inheritdoc}
   */
  public function provider() {
    $expected_ckeditor5_toolbar = [
      'items' => [
        'bold',
        'link',
      ],
    ];

    yield '<a aria-label> + CKEditor 4 DrupalLink' => [
      'format_id' => 'advanced_link__aria-label',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [
          'editor_advanced_link_link' => [
            'enabled_attributes' => [
              'aria-label',
            ],
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];

    yield '<a title> + CKEditor 4 DrupalLink' => [
      'format_id' => 'advanced_link__title',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [
          'editor_advanced_link_link' => [
            'enabled_attributes' => [
              'title',
            ],
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];

    yield '<a class> + CKEditor 4 DrupalLink' => [
      'format_id' => 'advanced_link__class',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [
          'editor_advanced_link_link' => [
            'enabled_attributes' => [
              'class',
            ],
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];

    yield '<a id> + CKEditor 4 DrupalLink' => [
      'format_id' => 'advanced_link__id',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [
          'editor_advanced_link_link' => [
            'enabled_attributes' => [
              'id',
            ],
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];

    yield '<a target="_blank"> + CKEditor 4 DrupalLink' => [
      'format_id' => 'advanced_link__target',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [
          'editor_advanced_link_link' => [
            'enabled_attributes' => [
              'target',
            ],
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];

    yield '<a rel> + CKEditor 4 DrupalLink' => [
      'format_id' => 'advanced_link__rel',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => $expected_ckeditor5_toolbar,
        'plugins' => [
          'editor_advanced_link_link' => [
            'enabled_attributes' => [
              'rel',
            ],
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];

    yield 'None, just plain link + CKEditor 4 DrupalLink' => [
      'format_id' => 'advanced_link__none',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            'bold',
            'link',
          ],
        ],
        'plugins' => [
          'editor_advanced_link_link' => AdvancedLink::DEFAULT_CONFIGURATION,
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [],
      'expected_messages' => [],
    ];

    yield '<a aria-label FOO title class id BAR="baz" target="_blank" rel> + CKEditor 4 DrupalLink' => [
      'format_id' => 'advanced_link__all_and_more',
      'filters_to_drop' => [],
      'expected_ckeditor5_settings' => [
        'toolbar' => [
          'items' => [
            'bold',
            'link',
            'sourceEditing',
          ],
        ],
        'plugins' => [
          'ckeditor5_sourceEditing' => [
            'allowed_tags' => [
              '<a foo bar="baz">',
            ],
          ],
          'editor_advanced_link_link' => [
            'enabled_attributes' => [
              'aria-label',
              'class',
              'id',
              'rel',
              'target',
              'title',
            ],
          ],
        ],
      ],
      'expected_superset' => '',
      'expected_fundamental_compatibility_violations' => [],
      'expected_db_logs' => [
        'status' => [
          'As part of migrating to CKEditor 5, it was found that the <em class="placeholder">&lt;a aria-label FOO title class id BAR=&quot;baz&quot; target=&quot;_blank&quot; rel&gt;</em> text format\'s HTML filters includes plugins that support the following tags, but not some of their attributes. To ensure these attributes remain supported, the following were added to the Source Editing plugin\'s <em>Manually editable HTML tags</em>: &lt;a foo bar=&quot;baz&quot;&gt;. The text format must be saved to make these changes active.',
        ],
      ],
      'expected_messages' => [
        'status' => [
          'To maintain the capabilities of this text format, <a target="_blank" href="/admin/help/ckeditor5#migration-settings">the CKEditor 5 migration</a> did the following:  Added these tags/attributes to the Source Editing Plugin\'s <a target="_blank" href="/admin/help/ckeditor5#source-editing">Manually editable HTML tags</a> setting: &lt;a foo bar=&quot;baz&quot;&gt;. Additional details are available in your logs.',
        ],
      ],
    ];

    // Verify that none of the core test cases are broken; especially important
    // for AdvancedLink since it extends the behavior of Drupal core.
    $formats_not_supporting_links = [
      'cke4_stylescombo_span',
      'cke4_plugins_with_settings',
      'cke4_contrib_plugins_now_in_core',
    ];
    $full_html_configuration = [
      'enabled_attributes' => array_keys(AdvancedLink::SUPPORTED_ATTRIBUTES),
    ];
    sort($full_html_configuration['enabled_attributes']);
    foreach (parent::provider() as $label => $case) {
      if (!in_array($case['format_id'], $formats_not_supporting_links, TRUE)) {
        // The `editor_advanced_link_link` plugin settings will appear for every
        // upgraded text editor while editor_advanced_link is installed, as long
        // as it has the `DrupalLink` button enabled in CKEditor 4.
        $case['expected_ckeditor5_settings']['plugins']['editor_advanced_link_link'] = $case['format_id'] === 'full_html'
          ? $full_html_configuration
          : AdvancedLink::DEFAULT_CONFIGURATION;
        ksort($case['expected_ckeditor5_settings']['plugins']);
      }

      yield $label => $case;
    }
  }

}
