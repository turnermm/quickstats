<?php
//Download: geoipcity.inc | geoipregionvars.inc | GeoLiteCity.dat
/**
 * Querying against GeoIP/Lite City
 * This will fetch country along with city information
 */
 
include("GEOIP/geoipcity.inc");
 
$giCity = geoip_open("/usr/local/share/GeoIP/GeoLiteCity.dat",GEOIP_STANDARD);
 
if($argc > 1) {
  $ip = $argv[1];
}
else $ip = "12.215.42.19";
$record = geoip_record_by_addr($giCity, $ip);
 
if(isset($GEOIP_REGION_NAME[$record->country_code][$record->region])){
  $region_name = $GEOIP_REGION_NAME[$record->country_code][$record->region] . "\n";
}
else $region_name = "\n";

echo "Country Code: " . $record->country_code .  "\n" .
     "Country Code3: " . $record->country_code . "\n" .
     "Country Name: " . $record->country_name . "\n" .
     "Region Code: " . $record->region . "\n" .
     "Region Name: " .   $region_name    .
     "City: " . $record->city . "\n" .
     "Postal Code: " . $record->postal_code . "\n" .
     "Latitude: " . $record->latitude . "\n" .
     "Longitude: " . $record->longitude . "\n" .
     "Metro Code: " . $record->metro_code . "\n" .
     "Area Code: " . $record->area_code . "\n" ; 
 
geoip_close($giCity);
?>
