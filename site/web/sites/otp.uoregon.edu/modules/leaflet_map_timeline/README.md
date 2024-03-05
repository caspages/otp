# Introduction
This module provide a custom block which houses a leaflet map that has timeline controls that restrict the markers visible on the map to those whose start and end dates contain the year being displayed on the map.

# Description
The custom block is comprised of an empty container with id "*leaflet_map_timeline*".

The JavaScript library (`map.timeline.drupal.js`)
* Initializes a leaflet map inside of the block's container.
* Uses Ajax to call the site's API (provided by the core module **JSON API**) to get a list of theaters.
* Populates the leaflet map with markers representing the theaters.
    * Also registers the start and end dates with the leaflet timeline extention.

# Author
Built by [IS WCSD Team](mailto:webapps@uoregon.edu).
