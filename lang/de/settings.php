<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * 
 * @author Thor Weinreich <thorweinreich@nefkom.net>
 * @author Padhie <develop@padhie.de>
 * @author Willi Lethert >willi@lethert.de>
 */
$lang['excludes']              = 'Liste von IPs, welche nicht in der Statistik gezählt werden sollen (durch Kommata getrennt)';
$lang['aborts']                = 'Liste von lästigen IPs, welche keinen Zugriff auf die Seite erhalten sollen (durch Kommata getrennt)';
$lang['geoip_local']           = 'Maxmind GeoLiteCity.dat wird sich im Quickstats-Ordner befinden: quickstats/GEOIP';
$lang['geoip_dir']             = 'Maxmind GeoLiteCity.dat Ordner; Quickstats wird hier nachsehen, falls geoip_local (d.h. quickstats/GEOIP) nicht ausgewählt wurde';
$lang['geoplugin']             = 'Web-basiertes geoPlugin für Geo-Lokalisierung von IPs verwenden (standardmäßig aktiviert); GeoLiteCity.dat wird nicht benötigt, wenn das hier aktiviert wurde';
$lang['long_names']            = 'Maximale Anzahl von Zeichen bei der Darstellung langer Namen (-1 für unendlich)';
$lang['show_date']             = 'Zeige Daten des letzten Seiten-Zugriffs, wenn sich die Maus darüber befindet';
$lang['show_country']          = 'Zeige zugehöriges Land einer IP in der IP-Tabelle an, wenn sich die Maus darüber befindet. Das funktioniert nur, wenn das "geoplugin" deaktiviert wurde';
$lang['sorttable_ns']          = 'Liste von ganzen / Teilen von Namensräumen und Dateinamen, welche vor der Ausgabe sortiert werden sollen (durch Kommata getrennt)';
$lang['xcl_name_val']          = 'Listeo von Seiten/Wert-Paaren die von der Statistik ausgeschlossen werden sollen (durch Komma getrennt)';
$lang['max_exec_time']         = 'Maximale Laufzeit in Sekunden für Abfragen im Adminpanel';
$lang['rebuild_uip']           = 'Wiederhergestellte eindeutige IP Liste';
$lang['hide_sidebar']          = "If the pages which display your statistics are partially cut off because of the space required for a side bar, and if the sidebar is set in the cofiguration manager, you may hide the sidebar. Place the pages which display the quickstats statistics in its own namespace; then enter the name of that  namespace here and the sidebar will be hidden while displaying your stats.  Avoid the namespace 'quickstats'.";
