<?php

if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../../../') . '/');
if(!defined('NOSESSION')) define('NOSESSION',true);
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/io.php');


function get_GeoLiteCity() {
    @set_time_limit(120);  
    $geoip_local = $_POST['geoip_local'];    
    
    $dnld_dir = DOKU_INC .  'lib/plugins/quickstats/GEOIP/';
    $url = 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity.dat.gz';
    $data_file = $dnld_dir . 'GeoLiteCity.dat';
    $gzfile = $data_file .'.gz';    
   
    $http = new DokuHTTPClient();
    $http->max_bodysize = 32777216;
    $http->timeout = 120; 
    $http->keep_alive = false; 

    $data = $http->get($url);
    if(!$data) { 
        echo "$gzfile failed to download\n";    
        return;
      }  

     $fp = @fopen($gzfile,'wb');
      if($fp === false) { 
           echo "Unable to write $gzfile. Please check your permissions and/or disk space.\n";
           return;
      }
      if(!fwrite($fp,$data)) {
         echo "Unable to write $gzfile. Please check your permissions and/or disk space.\n";
         return;
      }
      fclose($fp); 

          
    echo  "Saved: $gzfile\n";  
    $gz = gzopen($gzfile, "rb");  
    $data= gzread($gz, 32777216);
    gzclose($gz);                                            
    
     if( io_saveFile($data_file, $data)) {
           echo "Saved: $data_file \n";
     }
     else {
         echo "Unable to unpack $gzfile. You may be able to do this using a zip tool on your system.\n";
         return; 
     }
    if(!$geoip_local) {
        echo "When installing GeoLiteCity.dat in quickstats/GEOIP/, the 'geoip_local' option must be set to true.\n";
     }
    
}           

get_GeoLiteCity(); 
  
    

