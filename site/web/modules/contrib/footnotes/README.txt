INTRODUCTION
------------------

The Footnotes module is used to easily create automatically numbered footnote references in an article or post (such as a reference to a URL).

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/footnotes

 * To submit bug reports and feature suggestions, or track changes:
   https://www.drupal.org/project/issues/footnotes

REQUIREMENTS
------------------

The Footnote module for Drupal 8 requires the following modules and plugins:

 * FakeObjects (https://www.drupal.org/project/fakeobjects)
 * CKEditor plugin (http://ckeditor.com/addon/fakeobjects)

INSTALLATION
----------------
* Before you can use the FakeObjects module, you need to download the plugin from http://ckeditor.com/addon/fakeobjects and place it in /libraries/fakeobjects.

* In all other steps, install the module as you would normally install a contributed Drupal module. Visit
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules for further information.

CONFIGURATION
-------------------
* To use the footnotes filter in some input formats, go to Configuration ->
   Text formats.

* For the Text formats you want to support footnotes markup, select configure and activate a suitable footnotes filter.

* In the place where you want to add a footnote enclose the footnote text within an fn tag:<code>[fn]like this[/fn]</code>. By default, footnotes are placed at the end of the text. You can also use a <code>[footnotes]</code> or <code>[footnotes /]</code> tag to position it anywhere you want.

* The filter will take the text within the tag and move it to a footnote at the
bottom of the page. In it's place it will place a number which is also a link to
the footnote. Footnotes supports both <code>[fn]square brackets[/fn]</code> and <code><fn>angle brackets</fn></code>.

* You can also use a "value" attribute to a) set the numbering to start from the given value, or b) to set an arbitrary text string as label.

Ex:

 [fn value="5"]This becomes footnote #5. Subsequent are #6, #7...[/fn]
 [fn value="*"]This footnote is assigned the label "*"[/fn]
 
Using value="" you can have multiple references to the same footnote in the text body.

 [fn value="5"]This becomes footnote #5.[/fn]
 [fn value="5"]This is a reference to the same footnote #5, this text itself is discarded.[/fn]

TROUBLESHOOTING & FAQ
------------------------------

Q: When trying to install the Footnotes module, I get the message: Before you can use the FakeObjects module, you need to download the plugin from ckeditor.com and place it in /libraries/fakeobjects."

A: To avoid this error message, please follow the guidelines in the Required modules and the Installation sections of this Readme file. Please mind that the Drupal 8.x-2.x branch of the Footnotes module only supports the CKEditor and does not support the TinyMCE.
