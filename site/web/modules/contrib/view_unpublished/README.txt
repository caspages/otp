View Unpublished
----------------
This small module adds the missing permissions "view any unpublished content"
and "view unpublished $content_type content" to Drupal 8.

This module also integrates with the core Content overview screen at
/admin/content. If you choose the "not published" filter, Drupal will show you
unpublished content you're allowed to see.

Using view_unpublished with Views
---------------------------------
Use the "Published status or admin user" filter, NOT "published = yes".
Views will then respect your custom permissions. Thanks to hanoii (6.x) and
pcambra (7.x) for this feature.

Common issues
-------------
* If for some reason this module seems not to work, try rebuilding your node
permissions: admin/reports/status/rebuild. Note that this can take significant
time on larger installs and it is HIGHLY recommended that you back up your site
first.
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The View Unpublished module allows the user to grant access for specific user
roles to view unpublished nodes of a specific type. Access control is quite
granular in this regard.

Additionally, using this module does not require any modifications to the
existing URL structure.

 * For a full description of the module visit:
   https://www.drupal.org/project/view_unpublished

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/view_unpublished


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


RECOMMENDED MODULES
-------------------

To give specific roles the ability to publish/unpublish certain node types
without giving those roles administrative access to all nodes.

 * Override node options - https://www.drupal.org/project/override_node_options


INSTALLATION
------------

 * Install the View Unpublished module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > People > Permissions and assign the
       appropriate permissions to the roles you wish to be able to view
       unpublished nodes.

This module also integrates with the core Content overview screen at
Administration > Content. If you choose the "not published" filter, Drupal will
show the user unpublished content they're allowed to see.

Using View Unpublished with Views:
Use the "Published status or admin user" filter, NOT "published = yes".
Views will then respect the custom permissions.

Common issues:
If for some reason this module seems to not work, try rebuilding the node
permissions: Administration > Reports > Status > Rebuild. Note that this
can take significant time on larger installs and it is HIGHLY recommended
that you back up the site first.


MAINTAINERS
-----------

 * Agnes Chisholm (amaria) - https://www.drupal.org/u/amaria
 * Domenic Santangelo (entendu) - https://www.drupal.org/u/entendu

Supporting organization:

 * Bright Bacon web services - http://brightbacon.com/

Additional credits:

 * Brad Bowman/beeradb - Aten Design Group
 * Domenic Santangelo/dsantangelo - WorkHabit

Additional credits:

 * Brad Bowman/beeradb - Aten Design Group
 * Domenic Santangelo/dsantangelo - WorkHabit
   (7.x) for this feature.
