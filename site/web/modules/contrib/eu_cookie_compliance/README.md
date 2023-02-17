CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Working with the events
 * Maintainers


INTRODUCTION
------------

This module intends to deal with the EU Directive on Privacy and
Electronic Communications that comes into effect on 26th May 2012.
From that date, if you are not compliant or visibly working towards
compliance, you run the risk of enforcement action, which can include a
fine of up to half a million pounds for a serious breach.


FEATURES
------------

If you want to conditionally set cookies in your module, there is a
javascript function provided that returns TRUE if the current user has
given his consent:

Drupal.eu_cookie_compliance.hasAgreed()

Prevent "Consent by clicking" on some links
--------------------------------------------

The module offers a feature to accept consent by clicking. It may be
relevant to prevent this for certain links. In such cases, the link(s)
can be wrapped in an element with the class "popup-content".


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

1. Unzip the files to the "sites/all/modules" OR "modules" directory and enable
   the module.

2. If desired, give the 'administer EU Cookie Compliance banner' permissions
   that allow users of certain roles access the administration page. You can
   do so on the admin/people/permissions page.

  - there is also a 'display EU cookie compliance banner' permission that helps
    you show the banner to the roles you desire.

3. You may want to create a page that would explain how your site uses cookies.
   Alternatively, if you have a privacy policy, you can link the banner to that
   page (see next step).

4. Go to the admin/config/system/eu-cookie-compliance/settings page to
   configure and enable the banner.

5. If you want to customize the banner background and text color, either type
   in the hex values or simply install
   http://drupal.org/project/coloris.

6. If you want to theme your banner, override the templates in your theme.

7. If you want to show the message in EU countries only, install the Smart IP
   module: http://drupal.org/project/smart_ip or the GeoIP
   module: http://drupal.org/project/geoip and enable the option "Only
   display banner in EU countries" on the admin page. There is a JavaScript
   based option available for sites that use Varnish (or other caching
   strategies). The JavaScript based variant also works for visitors that bypass
   Varnish.


CONFIGURATION
--------------

A fully customizable banner which is used to gather consent for storing
cookies on the visitor's computer.

Configurable Information
--------------------------------------------
- Permissions
- Consent for processing of personal information
- Disable JavaScripts
- Cookie handling
- Store record of consent
- Cookie information banner
- Withdraw consent
- Thank you banner
- Privacy policy
- Appearance

WORKING WITH THE EVENTS
-----------------------

This module now exposes a JS API which other modules can use to hook into the
events fired by this module. Eg. read the cookie preferences when they are saved
or do something based on the user's response. For example, the JS in the
[EU Cookie Compliance GTM module](https://www.drupal.org/project/eu_cookie_compliance_gtm)
uses the Events to do things with GTM.

### Inside the scripts
#### Main file
Our main script (`eu_cookie_compliance.js`) loads via `defer`, which means it
is executed while parsing the page + just a few moments BEFORE the
DOMContentLoaded event gets fired.
When that happens, a namespace is created within the Drupal object, which will
house our Events.
Inside this namespace, a queue houses instances when the Events hooks are
called, ready to be executed.
```
Drupal.eu_cookie_compliance = Drupal.eu_cookie_compliance || function () {
   (Drupal.eu_cookie_compliance.queue = Drupal.eu_cookie_compliance.queue || [])
   .push(arguments)
};
```
This ensures that a script from another module (which either should not use
`defer`, or should be placed AFTER the main script in the HTML) will be able
to access the Events from the main script and perform actions.
_Note: This is very similar to how GA and GTM work with their events and
methods._ Besides a queue, there are special functions set up called
'Observers', which are used to observe and execute the functions for each Event.
**Events: (Event name on the left)**
Status is retrieved (internal use):
- preStatusLoad: Executed BEFORE cookie acceptance status is loaded
- postStatusLoad: Executed AFTER cookie acceptance status is loaded
- preStatusSave: Executed BEFORE cookie acceptance status is saved
- postStatusSave Executed AFTER cookie acceptance status is saved

Cookie is accepted by the user (public use):
- prePreferencesSave: Executed BEFORE the cookie acceptance preferences
  are saved (to a cookie).
- postPreferencesSave: Executed AFTER the cookie acceptance preferences
  are saved (to a cookie).
- prePreferencesLoad: Executed BEFORE the cookie acceptance preferences
  are loaded (from a cookie)
- postPreferencesLoad: Executed AFTER the cookie acceptance preferences
  are loaded (from a cookie)
#### Secondary file
**How to hook into the events:**
Use this method in your JS,
where `MY_EVENT` should be replaced by one of the Events mentioned above
and `MY_HANDLER` is a function that reads the data provided by the Method
`Drupal.eu_cookie_compliance(MY_EVENT, MY_HANDLER);`
Example: reading the cookie preferences after submission + save it
somewhere else for later use
```
var postPreferencesLoadHandler = function(response) {
   console.log(response);
   window.cookieResponse = response;
};
Drupal.eu_cookie_compliance('postPreferencesLoad', postPreferencesLoadHandler);
```

The `response` variable is an object with the following structure:

```
{
   currentStatus: null (not yet agreed/withdrawn), 0 (declined), 1 (accepted,
   show thank you banner) or 2 (accepted), currentCategories: array of accepted
   categories (will be empty when not using category based compliance)
}
```

MAINTAINERS
-----------

 * Neslee Canil Pinto - https://www.drupal.org/u/neslee-canil-pinto
 * Sven Berg Ryen - https://www.drupal.org/u/svenryen
 * Marcin Pajdzik - https://www.drupal.org/u/marcin-pajdzik
