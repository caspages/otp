<?php

namespace Drupal\editoria11y\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Editoria11ySettings.
 */
class Editoria11ySettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editoria11y_form_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'editoria11y.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('editoria11y.settings');
    $form['text'] = [
      '#markup' => '<h2>Getting started</h2><ol><li>Make sure <a href="/admin/people/permissions">user roles that edit content</a> have the "View Editoria11y" permission.</li><li>If the checker does not appear: make sure a z-indexed or overflow-hidden element in your front-end theme is not hiding or covering the <code>#ed11y-main-toggle</code> button, make sure that any custom selectors in the "Disable the scanner if these elements are detected" field are not present, and make sure that no JavaScript errors are appearing in the <a href="https://developer.mozilla.org/en-US/docs/Tools/Browser_Console">browser console</a>.</li><li>If the checker is present but never reporting errors: check that your inclusions & exclusion settings below are not missing or ignoring all content.</li></ol><p><a href="https://www.drupal.org/project/editoria11y">Project overview</a> | <a href="https://itmaybejj.github.io/editoria11y/demo/">Demo</a> | <a href="https://www.drupal.org/project/issues/editoria11y?categories=All">Issue queue</a></p><h2>Basic Configuration</h2>',
    ];
    $form['setup'] = [
      '#type' => 'fieldset',
      '#title' => t('Inclusions &amp; exclusions'),
    ];
    $form['setup']['content_root'] = [
      '#title' => $this->t("Check content in this container"),
      '#type' => 'textfield',
      '#placeholder' => 'body',
      '#description' => $this->t('If all editorial content is in <strong>one</strong> element (e.g., <code>main</code> or <code>#content</code>), provide that single <a href="https://developer.mozilla.org/en-US/docs/Learn/CSS/Building_blocks/Selectors">selector</a>.'),
      '#default_value' => $config->get('content_root'),
    ];
    $form['setup']['ignore_containers'] = [
      '#title' => $this->t("Skip over these elements"),
      '#type' => 'textarea',
      '#placeholder' => '#toolbar-administration, .contextual-region > nav, .search-results',
      '#description' => $this->t('Provide a comma-separated list of selectors for elements to ignore, such as <code>.my-embedded-social-media-feed, #sidebar-menu</code>.'),
      '#default_value' => $config->get('ignore_containers'),
    ];
    $form['setup']['no_load'] = [
      '#title' => $this->t("Disable the scanner if these elements are detected"),
      '#type' => 'textarea',
      '#placeholder' => '#quickedit-entity-toolbar, #layout-builder',
      '#description' => $this->t('Provided a comma-separated list of selectors unique to pages or states that should not be scanned; e.g, during inline editing  (<code>#inline-editor-open</code>) or on pages without user-editable content (<code>.node-261, .front</code>).'),
      '#default_value' => $config->get('no_load'),
    ];
    $form['subhead2'] = [
      '#markup' => '<h2>Customization</h2>',
    ];
    $form['results'] = [
      '#type' => 'fieldset',
      '#title' => t('Tests'),
    ];
    $form['results']['assertiveness'] = [
      '#title' => $this->t("Open the issue details panel automatically when new issues are detected"),
      '#type' => 'radios',
      '#options' => [
        'smart' => $this->t('When nodes are created or changed'),
        'assertive' => $this->t('Always'),
        'polite' => $this->t('Never'),
      ],
      '#description' => $this->t('"Always" is not recommended for sites with multiple editors.'),
      '#default_value' => $config->get('assertiveness'),
    ];
    $form['results']['download_links'] = [
      '#title' => $this->t("Warn that these links need manual review"),
      '#type' => 'textarea',
      '#placeholder' => "a[href$='.pdf'], a[href*='.pdf?'], a[href$='.doc'], a[href$='.docx'], a[href*='.doc?'], a[href*='.docx?'], a[href$='.ppt'], a[href$='.pptx'], a[href*='.ppt?'], a[href*='.pptx?'], a[href^='https://docs.google']",
      '#description' => $this->t('Provide a comma-separated list of selectors for links that should have a "this file needs a manual check" warning, e.g.: <code>[href^="/download"], .a[data-entity-substitution^="media"]</code>.'),
      '#default_value' => $config->get('download_links'),
    ];
    $form['results']['ignore_link_strings'] = [
      '#title' => $this->t("Remove these strings before testing link text"),
      '#type' => 'textarea',
      '#placeholder' => "\(link is external\)|\(link sends email\)",
      '#description' => $this->t('Provide a Regex of strings your modules programmatically add to links that break the "link has no text" and "link text is only click here" type tests. Escape Regex characters; e.g., <code>\(parenthesis\)</code>'),
      '#default_value' => $config->get('ignore_link_strings'),
    ];
    $form['results']['embedded_content_warning'] = [
      '#title' => $this->t("Warn that these embedded elements need manual review"),
      '#type' => 'textarea',
      '#description' => $this->t('Provide a comma-separated list of selectors for elements with potentially complex issues, e.g.: <code>.my-embedded-feed, #my-social-link-block</code>.<br> Note that this <strong>ignores the ignore list</strong>: you are specifying that you want his element flagged.'),
      '#default_value' => $config->get('embedded_content_warning'),
    ];
    $form['theme'] = [
      '#type' => 'fieldset',
      '#title' => t('Theme compatibility'),
    ];
    $form['theme']['allow_overflow'] = [
      '#title' => $this->t("Force these containers to allow overflow when tips are open"),
      '#type' => 'textarea',
      '#placeholder' => 'automatic',
      '#description' => $this->t('Editoria11y detects elements themed with overflow:hidden and automatically forces them to allow overflow when there is an open tip inside them. If this comma-separated list of selectors is not empty, only elements on this list will receive this override.'),
      '#default_value' => $config->get('allow_overflow'),
    ];
    $form['theme']['hidden_handlers'] = [
      '#title' => $this->t("Theme JS will handle revealing hidden tooltips inside these containers"),
      '#type' => 'textarea',
      '#description' => $this->t('Editoria11y detects hidden tooltips and warns the user when they try to jump to them from the panel. For elements on this list, Editoria11y will <a href="https://itmaybejj.github.io/editoria11y/#dealing-with-alerts-on-hidden-or-size-constrained-content">dispatch a JS event</a> instead of a warning, so custom JS in your theme can first reveal the hidden tip (e.g., open an accordion or tab panel).'),
      '#default_value' => $config->get('hidden_handlers'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('editoria11y.settings')
      ->set('ignore_containers', $form_state->getValue('ignore_containers'))
      ->set('assertiveness', $form_state->getValue('assertiveness'))
      ->set('no_load', $form_state->getValue('no_load'))
      ->set('content_root', $form_state->getValue('content_root'))
      ->set('allow_overflow', $form_state->getValue('allow_overflow'))
      ->set('download_links', $form_state->getValue('download_links'))
      ->set('embedded_content_warning', $form_state->getValue('embedded_content_warning'))
      ->set('hidden_handlers', $form_state->getValue('hidden_handlers'))
      ->set('ignore_link_strings', $form_state->getValue('ignore_link_strings'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
