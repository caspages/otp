# Editoria11y

## Contents

* Introduction

## Requirements

This module requires no modules outside of Drupal core.

## Introduction

Editoria11y (editorial accessibility ally) is a user-friendly accessibility
checker that addresses three critical needs for content authors:

1. It runs automatically. Modern spellcheck works so well because it is always
   running; put spellcheck behind a button and few users remember to run it!
1. It focuses exclusively on straightforward issues a content author can easily
   understand and easily fix. Comprehensive testing should be a key part of site
   creation, but if a tool is going to run automatically, it will drive an
   author bonkers if it is constantly alerting on code they do not understand
   and cannot fix.
1. It runs in context. Views, Layout Builder, Paragraphs and all the other
   modules Drupal uses to assemble a page means that tools that run inside
   CKEditor cannot "see" many of the issues on a typical page.

[Demo](https://itmaybejj.github.io/editoria11y/demo/)
| [Project Page](https://www.drupal.org/project/editoria11y)
| [Issue Queue](https://www.drupal.org/project/issues/editoria11y?categories=All)

### The authoring experience

* On each page load Editoria11y places a small toggle button at the bottom right
  of the screen with an issue count. Users can press the button to view details
  of any alerts or access additional tools ("full check"), including visualizers
  for the document outline and image alt attributes.
* Depending on configuration settings, the panel may open automatically if new
  issues are detected.

## Installation

* [Follow standard installation for a contrib module](https://www.drupal.org/documentation/install/modules-themes/modules-8)
  .
* If you are installing from the command line, do note the "eleventy" when
  spelling the module's name!

## Configuration

* Configure user permissions: on install, Editoria11y assigns the "View
  Editoria11y Checker" to user roles with common content-editing permissions.
  This is an inexact science; check Administration » People » Permissions to
  make sure all editorial roles have been assigned this position, and custom
  non-editorial roles (e.g., custom advanced webform users)
  have not. If a logged in site editor does not see Editoria11y, they are likely
  missing this permission.
* Configure elements to scan and ignore (Configuration » Content Authoring »
  Editoria11y):
    * By default, Editorially scans the HTML "body" element. Check to see if
      that makes sense for your site, and override if necessary on the module's
      configuration page. For many sites, something like "main" or "#main" is a
      better setting.
    * Some content just does not play nice with this type of tool; embedded
      social media feeds, for example. Add selectors for page elements you want
      the scanner to skip over. Optionally also flag these elements' containers
      as needing manual review.
* Check that popups are visible.
    * If the theme CSS sets `overflow: hidden` on containers, popups may be
      truncated. Selectors for these containers can be set on the Editoria11y
      configuration page, and this CSS will be temporarily overridden when a
      popup is open.
    * If the theme has components that show and hide content (carousels, tabs,
      accordions), you may wish to use the JS events listed below to reveal
      hidden content with errors when the user tries to jump to the hidden
      error.
* Check that Editoria11y is not running on content that is currently being
  edited.
    * By default, Editorially does not run on administrative paths, or if it
      detects that in-place editing is happening (e.g., Layout Builder). If you
      have inline editing enabled, check to see if Editoria11y is
      detecting/ignoring content that is actively being edited. If not, add a
      selector for elements present during editing to the module's configuration
      page under "Disable the scanner if these elements are detected." And do
      tell us about the conflict! If it is a common module we will add it to the
      default ignore list.

### JS Events for Themers

#### The main panel has opened

Useful for preparing content for a user who is scrolling around looking for
errors -- e.g., opening accordions if their content has an error, or disabling a
sticky menu that covers part of the content.

```js
document.addEventListener("ed11yPanelOpened", function (event) {
  // jQuery('.example').addClass('editoria11y-active');
});
```

#### A tooltip has opened

This event also returns the unique ID of the tip, in case you want to react to
the event by modifying the tooltip or your content..

If you want to react by switching to a tab/slide/accordion containing the error,
use the next event instead.

```js
document.addEventListener("ed11yPop", function (event) {
  let myID = '#' + event.detail.id;
  // jQuery(myID).parents('.example').addClass('has-open-tip');
});
 ```

#### A tooltip is about to open in a container you asked to be alerted about

Thrown for elements listed in the "Theme JS will handle revealing hidden
tooltips" configuration option, when the panel's "jump to the next issue" link
tries to open a matching tooltip.

*After* throwing this event, Editoria11y pauses for half a second, giving your
theme a moment to open an accordion or tab panel (etc.) before Editoria11y tries
to transfer focus to the hidden tip:

```js
document.addEventListener("ed11yHiddenHandler", function (event) {
  let myID = '#' + event.detail.id;
  // if (jQuery(myID).parents('.example').length > 0) {
  //   jQuery(myID).parents('.example').prev('button').click();
  // }
});
```

## Troubleshooting FAQ

### The checker does not appear

* If it does not appear for *at all*
    * Make sure a selector has not been added to the "Disable the scanner if..."
      configuration setting that matches something on every page.
    * Make sure it's not just there-but-hidden. Use your browser inspector to
      inspect the page and see if an element with an ID of "ed11y-main-toggle"
      is present. If it is there, see if something in your theme is covering
      it (z-index) or clipping it (overflow: hidden). You may need to use a
      little CSS to increase the z-index of `#ed11y-panel` or translate it up or
      left to get it out from under another component.
    * Make sure there are no JS errors in your browser console. Typos in
      selectors on the config page will throw an error and block the checker
      from running.
    * Make sure something (anything) is set in the "Tests" preference for when
      the panel should open automatically. If this configuration option gets
      unset sometimes the panel never appears.
    * If you are running Advagg, don't check the button to force preprocess for all files. See issue "[Does not work with Advanced CSS/JS Aggregator + jSqueeze](https://www.drupal.org/project/editoria11y/issues/3230850)"
* If it does not appear for some *users*:
    * Make sure the user's role has permission granted to "View the Editoria11y
      Checker."
* If it does not appear on some *pages*
    * Make sure a selector has not been added to the "Disable the scanner if..."
      configuration setting that matches something on the page.
    * Check for JS errors in your browser console.

### The checker reports false positives on an element

* Add a selector for the element to the "containers to ignore" configuration
  setting. If the false positive is really a mistake, please do report an issue
  to the module maintainers.

### Editors find the panel opening automatically to be annoying.

* On the config page, change the setting for when the panel should auto-open
  from "Smart" or "Always" to "Never." Editoria11y will still *check* every page
  and insert an issue count on the toggle, but it will stop auto-highlighting
  errors.

### You don't like the default error messages

* Feel welcome to override localization.js by adding this to your theme's .info
  file, just note that you may need to update your file when installing future
  versions of Editoria11y:

```
libraries-override:
  'editoria11y/editoria11y':
    js:
      js/editoria11y-localization.js: js/MY-LOCAL-THEME-VERSION-OF-THE-SAME.js 
```

### The checker slowed down after configuration

* Editoria11y should finish scanning and painting tooltips in less than half a
  second, even on very long pages. If you find it is taking longer, the most
  common culprit is a long "skip over these elements" selector list on the
  configuration page. Selectors on this list get called twice against almost
  every element on the page, once alone (`.example`) and once as a
  parent (`.example *`). Even worse, attribute selectors (`[aria-hidden]`) are
  much, much slower than element type, class or ID selectors. So if you added
  more than a dozen elements to skip and included several attribute
  selectors...see if you can shorten the list and/or switch to different
  selector types.

## Maintainers

Editoria11y is maintained by [John Jameson](https://www.drupal.org/u/itmaybejj),
and is provided to the community thanks to
the [Digital Accessibility](https://accessibility.princeton.edu/) initiatives at
Princeton
University's [Office of Web Development Services](https://wds.princeton.edu/)

### Acknowledgements

Editoria11y's JavaScript began as a fork of
the [Sa11y](https://ryersondmp.github.io/sa11y/) accessibility checker, which
was created by Digital Media Projects, Computing and Communication Services (
CCS) at Ryerson University in Toronto, Canada:

- [Adam Chaboryk](https://github.com/adamchaboryk), IT accessibility specialist
- Benjamin Luong, web accessibility assistant
- Arshad Mohammed, web accessibility assistant
- Kyle Padernilla, web accessibility assistant

Sa11y itself is an adaptation
of [Tota11y by Khan Academy](https://github.com/Khan/tota11y), was built
with [FontAwesome icons](https://github.com/FortAwesome/Font-Awesome) and is
powered with jQuery.
