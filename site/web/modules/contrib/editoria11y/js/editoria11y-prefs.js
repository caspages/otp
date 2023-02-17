/* User preferences */

// Base container(s) for tests.
let ed11yCheckRoot = "";

// Alert modes.
// "polite" never automatically pops open the panel.
// "assertive" pops open the panel if there are errors the user has not seen.
// CMS integrations can pick mode on load based on context.
let ed11yAlertMode = "";

// If any elements match these selectors, Ed11y will not start.
let ed11yNoRun = "#layout-builder, #quickedit-entity-toolbar, [data-drupal-selector^='node-'][data-drupal-selector$='-edit-form']";

// Ignore elements in these containers.
// Nav and toolbar can move into link and header ignore to help performance.
let ed11yContainerIgnore = ".search-results, #comment-form, .text-format-wrapper";

// Flag these elements with a warning in Full Check.
let ed11yEmbeddedContentWarning = "";

// Headers to ignore in the page outline.
// Todo add to Drupal configuration page
let ed11yOutlineIgnore = "#toolbar-administration *, .contextual-region > nav *, .block-local-tasks-block *";

// Additional selectors to ignore in specific tests.
// E.g., to just ignore images in a social media feed, add
// ".my-feed-container img" to imageIgnore.
let ed11yImageIgnore = "";
let ed11yHeaderIgnore = "#toolbar-administration *, .contextual-region > nav *";
let ed11yLinkIgnore = "#toolbar-administration a, .contextual-region > nav a, .mailto, .block-local-tasks-block a, .contextual-links a";

// Programmatically generated strings to remove from link text before testing.
// Provide pipe-separated strings: opens in new window|opens in new tab.
let ed11yIgnoreLinkStrings = /\(link is external\)|\(link sends email\)/g;

let ed11yAllowOverflow = "";

let ed11yHiddenHandlers = "";

let ed11yDownloadLinks = "a[href$='.pdf'], a[href*='.pdf?'], a[href$='.doc'], a[href$='.docx'], a[href*='.doc?'], a[href*='.docx?'], a[href$='.ppt'], a[href$='.pptx'], a[href*='.ppt?'], a[href*='.pptx?'], a[href^='https://docs.google']";


// Outline is ignoring hidden containers.
// These tests are not enabled yet.
// let ed11yFormsIgnore = "";
// let ed11yTableIgnore = "";
// Patterns for links to development environments
// const ed11yDevEnvironment = ""