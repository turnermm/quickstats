<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../../../') . '/');
echo DOKU_INC . "\n";
exit;
if(!defined('NOSESSION')) define('NOSESSION',true);
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/io.php');


function get_GeoLiteCity() {

    @set_time_limit(120);  
 
    $helper = plugin_load('helper', 'quickstats');       
    $dnld_dir = DOKU_INC .  'lib/plugins/quickstats/GEOIP/composer/vendor/';    
    echo $dnld_dir . "\n";
    $url = "https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz";
    $data_file = 'GeoLite2-City.tar';
    $gzfile = 'GeoLite2-City.tar.gz';    
    

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
}

  function qs_say(){
        $args = func_get_args();
        echo vsprintf(array_shift($args)."\n",$args);        
        ob_flush();
    }

get_GeoLiteCity();
  
    

