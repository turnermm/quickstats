<?php

if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../../../') . '/');
if(!defined('NOSESSION')) define('NOSESSION',true);
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/io.php');


function get_GeoLiteCity($db) {

    @set_time_limit(120);  
    $geoip_local = $_POST['geoip_local'];    
    $helper = plugin_load('helper', 'quickstats');       
    $dnld_dir = DOKU_INC .  'lib/plugins/quickstats/GEOIP/';
    $url = "http://geolite.maxmind.com/download/geoip/database/${db}.gz";
  
    $data_file = $dnld_dir . $db;
    $gzfile = $data_file .'.gz';    
   
    $http = new DokuHTTPClient();
    $http->max_bodysize = 32777216;
    $http->timeout = 120; 
    $http->keep_alive = false; 

    $data = $http->get($url);
    if(!$data) { 
        qs_say($helper->getLang('download_fail'),  $gzfile);
        return;
      }  

     $fp = @fopen($gzfile,'wb');
      if($fp === false) { 
           qs_say($helper->getLang('write_fail'),  $gzfile);
           return;
      }
      if(!fwrite($fp,$data)) {
         qs_say($helper->getLang('write_fail'),  $gzfile);    
         return;
      }
      fclose($fp); 
     qs_say($helper->getLang('file_saved'),  $gzfile);          

    $gz = gzopen($gzfile, "rb");  
    $data= gzread($gz, 32777216);
    gzclose($gz);                                            
    
     if( io_saveFile($data_file, $data)) {
           qs_say($helper->getLang('file_saved'),  $data_file);   
     }
     else {
        qs_say($helper->getLang('no_unpack'),  $gzfile);      
         return; 
     }
    if(!$geoip_local) {
        qs_say($helper->getLang('no_geoip_local'));      
     }
    
}           

  function qs_say(){
        $args = func_get_args();
        echo vsprintf(array_shift($args)."\n",$args);        
        ob_flush();
    }

get_GeoLiteCity('GeoLiteCity.dat'); 
get_GeoLiteCity('GeoIPv6.dat'); 
  
    

