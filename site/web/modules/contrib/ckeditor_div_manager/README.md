CKEditor Div Manager
====================

INTRODUCTION
------------

This module integrates the [Div Container Manager](
https://ckeditor.com/cke4/addon/div) CKEditor plugin for Drupal 8.

The plugin adds the ability to group content blocks under a div element as a
container, with styles and attributes optionally specified in a dialog.


REQUIREMENTS
------------

* CKEditor Module (Core)


INSTALLATION
------------

### Install via Composer (recommended)

If you use Composer to manage dependencies, edit composer.json as follows.

* Run `composer require --prefer-dist composer/installers` to ensure you have
the composer/installers package. This facilitates installation into directories
other than vendor using Composer.

* In composer.json, make sure the "installer-paths" section in "extras" has an
entry for `type:drupal-library`. For example:

```json
{
  "libraries/{$name}": ["type:drupal-library"]
}
```

* Add the following to the "repositories" section of composer.json:

```json
{
  "type": "package",
  "package": {
    "name": "ckeditor/div",
    "version": "4.10.1",
    "type": "drupal-library",
    "extra": {
      "installer-name": "ckeditor/plugins/div"
    },
    "dist": {
      "url": "https://download.ckeditor.com/div/releases/div_4.10.1.zip",
      "type": "zip"
    }
  }
}
```

* Run `composer require 'ckeditor/div:4.10.1'` to download the plugin.

* Run `composer require 'drupal/ckeditor_div_manager:^1.0.0'` to download the
CKEditor Div Manager module, and enable it [as per usual](
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).


### Install Manually

* Download the [Div Container Manager](https://ckeditor.com/cke4/addon/div)
CKEditor plugin.

* Extract and place the plugin contents in the following directory:
`/libraries/ckeditor/plugins/div/`.

* Install the CKEditor Div Manager module [as per usual](
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules).


CONFIGURATION
-------------

* Go to 'Text formats and editors' (`admin/config/content/formats`).

* Click 'Configure' for any text format using CKEditor as the text editor.

* Configure your CKEditor toolbar to include the Div Container Manager button.

* If 'Limit allowed HTML tags and correct faulty HTML' is enabled, the Allowed
HTML tags need to be configured. At a minimum, the `<div>` tag needs to be
allowed. Some fields in the plugin dialog require additional attributes to be
allowed or they will be hidden. The full set is `<div class id title>`. Inline
styles will not be allowed with this filter enabled.

* Any classes or sets of classes defined in the editor's Styles dropdown for
the div element will carry over to the Style dropdown in the plugin dialog.


TROUBLESHOOTING
---------------

* This project only handles the bridge between Div Container Manager and Drupal.
For support of the plugin itself, please use their [project page](
https://github.com/ckeditor/ckeditor-dev/tree/master/plugins/div).


MAINTAINERS
-----------
Current maintainers:

 * Corey Eiseman ([toegristle](https://www.drupal.org/u/toegristle))
