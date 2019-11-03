<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../../../') . '/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
define ('MMDB', DOKU_PLUGIN .'quickstats/GEOIP/vendor/GeoLite2-City/GeoLite2-City.mmdb');
require_once(DOKU_INC.'inc/init.php');
require_once(DOKU_INC.'inc/io.php');
if(!defined('NOSESSION')) define('NOSESSION',true);

class qs_geoliteCity {
    private $tempdir;
	private $helper;
    function __construct () {
            global $conf;
            $this->tempdir = $conf['tmpdir'];
            if(!file_exists($this->tempdir . '/tmp')) {
                echo "creating tmp directory: " . $this->tempdir . '/tmp' . "\n";
                mkdir($this->tempdir . '/tmp');
            }
            else {
                  echo "Checking for clean working directory\n";
                  $this->process_gcity(true);
                  $this->cleanup(true);
            }
			$this->helper = plugin_load('helper', 'quickstats');   
       //     exit;
    }
    
    function get_GeoLiteCity() {
        @set_time_limit(120);     
       
         $url = "https://geolite.maxmind.com/download/geoip/database/GeoLite2-City.tar.gz";
     //   $url = "http://epicurus.bz/devel/_media/geolite2-city_20191015.tar.gz";  
        $url = "http://epicurus.bz/GeoLite2-City.tar.gz";	 
        $gzfile = $this->tempdir  .  '/GeoLite2-City.tar.gz';    
        
        $http = new DokuHTTPClient();
        $http->max_bodysize = 32777216;
        $http->timeout = 120; 
        $http->keep_alive = false; 

        $data = $http->get($url);
        if(!$data) { 
            $this->qs_say($this->helper->getLang('download_fail'),  $gzfile);
            return;
          }  

         $fp = @fopen($gzfile,'wb');
          if($fp === false) { 
               $this->qs_say($this->helper->getLang('write_fail'),  $gzfile);
               return;
          }
          if(!fwrite($fp,$data)) {
             $this->qs_say($this->helper->getLang('write_fail'),  $gzfile);    
             return;
          }
          fclose($fp); 
         $this->qs_say($this->helper->getLang('file_saved'),  $gzfile);          

        $gz = gzopen($gzfile, "rb");  
        $data= gzread($gz, 32777216);
         gzclose($gz);     
    }


        
     function qs_unpack() {
        $ro = ini_get('phar.readonly');       
        if($ro) ini_set('phar.readonly','0');
        $files = scandir($this->tempdir);
      
        foreach ($files as $file) {          
            if(preg_match("#GeoLite2-City.tar.gz#", $file,$matches)) { 
               $file = $this->tempdir."/$file";
               $p = new PharData($file);
			   $tar = str_replace('.gz', "", $file);
			   echo "$tar\n";
			   if(file_exists($tar)){
				 $this->qs_say($this->helper->getLang('file_exists'),"\n$file\n$tar\n");
				 exit;
			   }	   
			        	   
               $p->decompress(); // creates /path/to/my.tar
                    
            
                try {
                     $phar = new PharData($tar);
                     $phar->extractTo($this->tempdir.'/tmp'); // extract all files
                } catch (Exception $e) {
                    echo $e->getMessage() . "\n";
                }
            }
       }
    }
     
  function process_gcity($ini = null) {   
   $tmpdir_files = scandir($this->tempdir . '/' . 'tmp'); 
    foreach ($tmpdir_files as $tmpfile) {
         $current_file = $this->tempdir . '/' . "tmp/${tmpfile}";
          if(preg_match("#(?i)GeoLite2-City_\d+#",$tmpfile) ) {           
              if(is_dir($this->tempdir . '/' . 'tmp/'. $tmpfile)) {                  
                  $geo_dir_name =  $this->tempdir . '/' . 'tmp/'. $tmpfile;            
                  $geo_dir = scandir($this->tempdir . '/' . 'tmp/'. $tmpfile);                
                   foreach($geo_dir as $gfile) {
                      if(!is_dir($this->tempdir . '/' . 'tmp/'. $gfile)) {                          
                          if(preg_match("/\.mmdb$/",$gfile) && !$ini) {                           
                              echo "renaming " . $geo_dir_name. "/$gfile" ."\nTo: " . MMDB ."\n";
                              rename($geo_dir_name. "/$gfile",MMDB);
                             continue;
                          }    
                           $discard = "$geo_dir_name/$gfile";
                           if($ini) {
                               echo "Unlinking $discard\n";    
                           }                
                         if(is_writable($discard)) 
                           unlink ($discard);
                      }                     
                  }               
              }
              else {
                  echo $this->tempdir . '/' . 'tmp/'. $tmpfile . " is NOT a directory\n";                                
              }
          }      
      }
       if(file_exists($geo_dir_name)) {   
          rmdir($geo_dir_name);
       }
       else echo "Please check your data/temp directory and remove data/tmp/tmp any Geocity files found in it\n";
        
    }   
    function cleanup() {
        $to_cleanup = scandir($this->tempdir);
        //print_r($to_cleanup);
        foreach($to_cleanup as $file) {
            $del = $this->tempdir . '/' . $file;
            if(!is_dir($del) && preg_match("/GeoLite2-City/i",$file)) {
              unlink($del);    
            }
            else if($file == 'tmp') {
                rmdir($this->tempdir . '/' .'tmp');
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
$geoLite->qs_unpack() ;
$geoLite->process_gcity();
$geoLite->cleanup(true);    

