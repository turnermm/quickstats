<?php
$lang['excludes'] = 'Comma separated list of ip addresses that should not be counted in statistics';
$lang['aborts']  = 'Comma separated list of nuisance ip addresses that should not be given access to the site';
$lang['geoip_local'] = 'Maxmind GeoLiteCity.dat will be found in the quickstats dir:  quickstats/GEOIP';
$lang['geoip_dir'] = 'Maxmind GeoLiteCity.dat directory; quickstats will look here if geoip_local (i.e. quickstats/GEOIP) is not selected';
$lang['geoplugin'] = 'Use the web-based geoPlugin for IP geo-locating (defaults to true); with this set to true GeoLIteCity.dat is not needed';
$lang['long_names'] = 'Maximum number of characters when displaying long names, set to -1 for unlimited';
$lang['show_date'] = 'Show date of last page access with mouseover';
$lang['show_country'] = 'In IP table, show country of IP address with mouseover.  This will work only when "geoplugin" is set to false';
$lang['sorttable_ns'] = 'Comma separated list of whole or partial namespaces and file names which require sortable output';
$lang['xcl_name_val'] = 'Comma separated list of query string name/value pairs which should be excluded from stats';
$lang['max_exec_time'] = 'Maximum number of seconds for which to run queries in quickstats admin panel';
$lang['rebuild_uip'] = 'Rebuild Unique IP array';
$lang['hide_sidebar'] = "If the pages which display your statistics are partially cut off because of the space required for a side bar, and if the sidebar is set in the cofiguration manager, you may hide the sidebar. Place the pages which display the quickstats statistics in its own namespace; then enter the name of that  namespace here and the sidebar will be hidden while displaying your stats.  Avoid the namespace 'quickstats'.";
$lang['ajax'] = "Delay final processing until after pages are loaded.  This may result in smoother, faster loading of pages and templates";
$lang['xcl_pages'] = "Comma separated list of pages and/or namespaces to be excluded from statistics. "
                   . " For pages use the format <code>ns:pagename</code>. For namespaces use the format <code>ns::&nbsp;</code>. "
                   . "In both cases  give the complete namespace path: <code>ns1:ns2:</code>, etc. Initial namespace ids and root page names do not have an initial colon: <code>ns::</code> "
                   . "and not <code>:ns::</code>.  Similarly, <code>pagename</code>, and not <code>:pagename</code>";