
### INSTALLATION & CONFIGURATION
Before you enable the Leaflet More Maps module, you need to install the Leaflet
module.

With both modules installed successfully, you should not see any errors in the
Status Report, admin/reports/status, and the section "Leaflet Library" should
report that you have over 20 maps available to choose from.

You select your favorite map when you format a single location field (e.g.
Geofield) as a map or when you format a View of multiple nodes or other
entities as a map.
Modules Leaflet Views (a submodule of Leaflet) and "IP Geolocation Views and
Maps" are particularly good for this.
OSM Thunderforest, mapbox and HERE maps require an API key or access token.
If you wish to use maps by any of these providers, sign up for an account
with them (usually free) and create the relevant key or token.
Enter that key or token on the Leaflet More Maps configuration page at
admin/config/system/leaflet-more-maps.
For a quick smoke test enable the Leaflet Demo submodule that comes with
Leaflet More Maps and visit the map showcase page to verify that your key
or token works, at admin/config/system/leaflet-more-maps/demo.

Apart from using any of the "off-the-shelf" maps, you can also assemble your
own map from the available layers at the Leaflet More Maps configuration page:
admin/config/system/leaflet-more-maps. A layer switcher will automatically appear
in the upper right-hand corner of the map.


### FOR PROGRAMMERS
You can add your own map by implementing `hook_leaflet_map_info()`.
See `leaflet_leaflet_map_info()` in file leaflet.module for an example.
You can alter the default settings of any Leaflet map on the system by
implementing `hook_leaflet_map_info_alter()`.
Example:
```
  function MYMODULE_leaflet_map_info_alter(&$map_info) {
    foreach ($map_info as $map_id => $info) {
      $map_info[$map_id]['settings']['zoom'] = 2;
      $map_info[$map_id]['label'] += ' ' . t('default zoom=2');
    }
  }
```

You can add or alter the default list of maps used as layers by implementing `hook_leaflet_more_maps_list_alter()`.
See `leaflet_more_maps.api.php` for more info.

#### References and licensing terms:

- https://leafletjs.com

- https://www.openstreetmap.org/copyright
- https://www.mapbox.com/legal/tos
- https://maps.stamen.com/#watercolor/12/37.7706/-122.3782
- https://thunderforest.com
- https://www.esri.com
- https://www.google.com/intl/en_au/help/terms_maps.html
- https://www.microsoft.com/maps/product/terms.html
- https://here.com
