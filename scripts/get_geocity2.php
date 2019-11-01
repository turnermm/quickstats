<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../../../') . '/');
if(!defined('NOSESSION')) define('NOSESSION',true);
 //define ('TEMPDIR', DOKU_INC . 'data/tmp/');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/io.php');
# use dokuwiki\HTTP\DokuHTTPClient;
class qs_geoliteCity {
    private $tempdir;
    function __construct () {
            global $conf;
            $this->tempdir = $conf['tmpdir'];
    }
    
    function get_GeoLiteCity() {
        @set_time_limit(120);  
     
        $helper = plugin_load('helper', 'quickstats');       
      //   $url = "https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz";
        $url = "http://epicurus.bz/devel/_media/geolite2-city_20191015.tar.gz";        
        $gzfile = $this->tempdir  .  '/GeoLite2-City.tar.gz';    
        
        $http = new DokuHTTPClient();
        $http->max_bodysize = 32777216;
        $http->timeout = 120; 
        $http->keep_alive = false; 

        $data = $http->get($url);
        if(!$data) { 
            $this->qs_say($helper->getLang('download_fail'),  $gzfile);
            return;
          }  

         $fp = @fopen($gzfile,'wb');
          if($fp === false) { 
               $this->qs_say($helper->getLang('write_fail'),  $gzfile);
               return;
          }
          if(!fwrite($fp,$data)) {
             $this->qs_say($helper->getLang('write_fail'),  $gzfile);    
             return;
          }
          fclose($fp); 
         $this->qs_say($helper->getLang('file_saved'),  $gzfile);          

        $gz = gzopen($gzfile, "rb");  
        $data= gzread($gz, 32777216);
         gzclose($gz);     
    }


        
     function qs_unpoack() {
        $ro = ini_get('phar.readonly');
        echo $ro . "\n";
        if($ro) ini_set('phar.readonly','0');
        $files = scandir($this->tempdir);
       // print_r($files); exit;
        foreach ($files as $file) {
              echo $this->tempdir."/$file\n";
              if (preg_match("#GeoLite2-City.tar.gz#", $file,$matches)) { 
              $file = $this->tempdir."/$file";
               $p = new PharData($file);
               $p->decompress(); // creates /path/to/my.tar
               $tar = str_replace('.gz', "", $file);       
                echo $tar . "\n";
                try {
                     $phar = new PharData($tar);
                     $phar->extractTo($this->tempdir.'/tmp'); // extract all files
                } catch (Exception $e) {
                    echo $e->getMessage() . "\n";
                }
            }
       }
    }
     
     function  qs_say(){
            $args = func_get_args();
            echo vsprintf(array_shift($args)."\n",$args);        
            ob_flush();
        }  
}

$geoLite = new qs_geoliteCity();
$geoLite->get_GeoLiteCity();
 $geoLite->qs_unpoack() ;
    

