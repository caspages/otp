# @FONT-YOUR-FACE

[![Build Status](https://travis-ci.org/fontyourface/fontyourface.svg?branch=8.x-3.x)](https://travis-ci.org/fontyourface/fontyourface)

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The @font-your-face module provides an administrative interface for browsing and
applying web fonts (using CSS @font-face, supported in all popular browsers)
from a variety of sources.

 * For a full description of the module visit:
   https://www.drupal.org/project/fontyourface

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/fontyourface


REQUIREMENTS
------------

This module requires the following outside of Drupal core:

  * The @font-your-face submodule Typekit requires a server that can securely
    (SSL) connect to Typekit.com.


INSTALLATION
------------

 * Install the @font-your-face module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module and one or more
       of the submodules.
    2. Navigate to Administration > Appearance > @font-your-face > settings and
       import the fonts.
    3. Navigate to Administration > Appearance > @font-your-face
       (admin/appearance/font) to enable some fonts.
    4. Select the 'enable font' for each font you want to use.
    5. You can add CSS selectors for each enabled font via  Administration >
       Appearance > @font-your-face > Font Display
       (admin/appearance/font/font_display).

Known issues:
 * Note that Internet Explorer has a limit of 32 CSS files, so using
   @font-your-face on CSS-heavy sites may require turning on CSS aggregation
   under Administer > Configuration > Development > Performance
   (admin/config/development/performance).
 * Note that not all modules from Drupal 7 have been ported (font reference,
   fontyourface wysiwyg). Help is much appreciated.
 * Fonts.com api has some quirks. You may have to use the fonts.com website for
   enabling all your fonts instead.
 * See https://drupal.org/project/fontyourface#support for support options on
   any issues not mentioned here.


MAINTAINERS
-----------

 * Neslee Canil Pinto - https://www.drupal.org/u/neslee-canil-pinto
 * Ashok Modi (BTMash) - https://www.drupal.org/u/btmash

@font-your-face was created by Scott Reynen of Sliced Bread Labs and Aten Design
Group, and developed by Scott and Baris Wanschers of LimoenGroen.
