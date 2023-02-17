CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Reports
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------

The Audit Files module allows for comparing and correcting files and file
references in the "files" directory, in the database, and in content. It is
designed to help keep the files on your server in sync with those used by your
Drupal site.

This module avoids using the Drupal API when dealing with the files and their
references, so that more or different problems are not created when attempting
to fix the existing ones.

The module does use the Drupal API (as much as possible) to reduce the load on
the server, including (but not necessarily limited to) paging the reports and
using the Batch API to perform the various operations.

Seven reports are included, and they can be accessed at Administer > Reports >
Audit Files (admin/reports/auditfiles).

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/auditfiles

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/auditfiles


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

* Install the Audit Files module as you would normally install a contributed
  Drupal module. Visit https://www.drupal.org/node/1897420 for further
  information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > System > Audit Files for
       configuration.


REPORTS
-------

 * Not in database:

   This report lists the files that are on the server, but that is not in the
   file_managed database table. These may be orphan files whose parent node has
   been deleted, they may be the result of a module not tidying up after itself,
   or they may be the result of uploading files outside of Drupal (e.g., via
   FTP).

   From this report, you can mark files for deletion. Be careful with the delete
   feature on any report - the deletion is permanent - be sure the file is no
   longer needed before erasing it!

   You can also add one or more files to the file_managed table from this
   report.

 * Not on server:

   This report lists the files that are in the file_managed database table but
   do not exist on the server. These missing files may mean that nodes do not
   display as expected, for example, images may not display or downloads may not
   be available.

   You can also delete any items listed in the report from the database.

 * Managed not used:

   The files listed in this report are in the file_managed database table but
   not in the file_usage table. Usually, this is normal and acceptable. This
   report exists for completeness, so you may verify what is here is correct.

 * Used not managed:

   The files listed in this report are in the file_usage database table but not
   in the file_managed table. Files listed here have had their Drupal management
   removed, but are still being listed as used somewhere and may have content
   referencing them.

   You should verify the file's existence on the server and in the objects it is
   listed as being used in, and either delete the reference in this report, or
   add it to the file_managed table (which is a manual process, due to the fact
   that the necessary data is not available to this module).

 * Used not referenced:

   The files listed here are in the file_usage database table, but the content
   that has the file field no longer contains the file reference.

   The report lists both the file URI, so you can verify it still is a valid
   file, and the file's usages, so you can see where it was being used. Both of
   those can be used in determining what needs to happen with the reference.

 * Referenced not used:

   Listed here are the file references in file fields attached to entities which
   do not have a corresponding listing in the file_usage table.

   What is listed in this report is the data of references themselves. This can
   be used to determine what needs to happen with the reference.

   References listed here can either be deleted from the database or added to
   the file_usage table.

 * Merge file references:

   This report lists all files listed in the file_managed, along with their
   usages, grouped by file name. With it, you can merge duplicate file
   references into a single one. This reduces records in the database and saves
   space on the file system.


TROUBLESHOOTING
---------------

You receive the following error messages:

 * Warning: Unknown: POST Content-Length of [some number] bytes exceeds the
   limit of [some number] bytes in Unknown on line 0

 * Warning: Cannot modify header information - headers already sent in Unknown
   on line 0

 * (And a number of "Notice: Undefined index:..." messages.)
   Set the "Maximum records" and "Batch size" settings on the Audit Files
   administrative settings configuration page (admin/config/system/auditfiles),
   and then use the "Load all records" button on the report that is producing
   the error. See the "Limiting Features Explained" section above for more
   information.

You receive the following error messages:

 * Fatal error: Maximum execution time of [some number] seconds exceeded in
   [path to report file] on line [line number]
   Set the "Maximum records" and "Batch size" settings on the Audit Files
   administrative settings configuration page (admin/config/system/auditfiles),
   and then use the "Load all records" button on the report that is producing
   the error. See the Limiting Features Explained section above for more
   information.


MAINTAINERS
-----------

Current maintainers:

 * Lisa Ridley (lhridley) - https://www.drupal.org/u/lhridley

Previous maintainers:

 * Andrey Andreev (andyceo) - https://www.drupal.org/user/152512
 * Jason Flatt (oadaeh) - https://www.drupal.org/user/4649
 * keshav kumar (keshav.k) - https://www.drupal.org/u/keshavk
 * Stuart Greenfield (Stuart Greenfield) - https://www.drupal.org/user/54866

Supporting organization:

 gai Technologies Pvt Ltd - https://www.drupal.org/gai-technologies-pvt-ltd
