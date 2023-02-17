Background Image Formatter
================


Introduction
------------


This module provides an image formatter that allows you to display the image
in a div as background image.
The module extends Drupal's images field. Because settings are attached
to the entity, it's very easy to setup and manage.



Features
--------

1. No module dependencies (Other that image).

2. Works with Drupal's field UI.

3. Works with Views.

4. Integrates with Drupal's image styles.

5. Offers 2 modes. (Inline Style & CSS Selector)



Inline Style
------------

Instead the generating the normal img markup we now generate
the div with a inline style.

This

<img src="PATH" />

is changed to

<div class="[YOUR CLASS]" style="background-image:
url('[ABSOLUTE PATH]')">&nbps;</div>



CSS Selector
------------

This option prevents the img tag from being printed to the dom and instead
generates a stylesheet in the dom.

The expected out with be something like this.

<style>
[YOUR SELECTOR] {background-image: url('[ABSOLUTE PATH]');}
</style>



Basic Installation
------------------

1. Download and enable the module.

2. Go to Administration >  Structure > Content Types (admin/structure/types).

3. Click on Manage Display for the relevant content type.
(admin/structure/types/manage/page/display)

4. Change the Format to Background Image for the relevant image field.

5. Click on the settings gear to define your settings.
