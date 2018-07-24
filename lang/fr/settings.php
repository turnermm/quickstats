<?php

/**
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 *
 * @author Schplurtz le Déboulonné <schplurtz@laposte.net>
 */
$lang['excludes']              = 'Liste séparée par des virgules des adresses IP qui ne doivent pas être comptées dans les statistiques';
$lang['aborts']                = 'Liste séparée par des virgules des adresses IP nuisibles qui ne devraient pas avoir accès au site';
$lang['geoip_local']           = 'Le fichier Maxmind GeoLiteCity.dat se trouve dans le dossier quickstats:
quickstats/GEOIP';
$lang['geoip_dir']             = 'Dossier du fichier Maxmind GeoLiteCity.dat. Quickstats le recherchera dans ce dossier si geoip_local (i.e. quickstats/GEOIP) n\'est pas sélectionné.';
$lang['geoplugin']             = 'Utiliser l\'extension web geoPlugin pour géolocaliser les IP (actif par défaut). Quand ce réglage est actif, GeoLIteCity.dat est inutile.';
$lang['long_names']            = 'Longueur maximale pour l\'affichage des noms longs. Régler sur -1 pour ne pas limiter';
$lang['show_date']             = 'Afficher la date de dernier accès aux pages lors du survol par le curseur';
$lang['show_country']          = 'Dans le tableau IP, afficher le pays de l\'IP lors du survol par le curseur. Ceci ne fonctionne que lorsque "geoplugin" est désactivé';
$lang['sorttable_ns']          = 'Liste séparée par des virgules des noms partiels ou complets de catégories et noms de fichier qui nécessitent un affichage triable';
$lang['xcl_name_val']          = 'Liste séparée par des virgules des paires nom/valeur à l\'interieur des requêtes web qui devraient être exclues des statistiques';
$lang['max_exec_time']         = 'Durée maximum en secondes des requêtes dans le panneau d\'administration quickstats';
$lang['rebuild_uip']           = 'Reconstruire le tableau des IP uniques';
$lang['hide_sidebar']          = 'Si les pages qui affichent vos statistiques sont partiellement coupées à cause de l\'espace requis pour une barre latérale, et si la barre latérale est activée dans le gestionnaire de configuration, vous pouvez cacher la barre latérale. Places les pages de statistiques dans leur catégorie propre, puis entrez le nom de cette catégorie ici, et la barre latérale sera cachée lors de l\'affichage de vos statistiques. Évitez la catégorie «quickstats».';
$lang['ajax']                  = 'Reporter le traitement final après la fin du chargement des pages. Cela pourrait aboutir à un chargement plus souple et rapide des pages et modèles.';
