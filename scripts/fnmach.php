<?php
if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../') . '/');
define ('TEMPDIR', DOKU_INC . 'data/tmp/');
//echo DOKU_INC . "\n";

$ro = ini_get('phar.readonly');
echo $ro . "\n";
if($ro) ini_set('phar.readonly','0');
function unpack_geocity2() {
    $files = scandir(TEMPDIR);
    foreach ($files as $file) {
          echo TEMPDIR.$file ."\n";
          if (preg_match("#GeoLite2-City.tar.gz#", $file,$matches)) { 
           $p = new PharData(TEMPDIR.$file);
           $p->decompress(); // creates /path/to/my.tar
           $tar = str_replace('.gz', "", $file);       
            echo $tar . "\n";
            try {
                 $phar = new PharData(TEMPDIR.$tar);
                 $phar->extractTo(TEMPDIR.'tmp'); // extract all files
            } catch (Exception $e) {
                echo $e->getMessage() . "\n";
            }
        }
    }
}

function process_gcity() {   
   $tmpdir_files = scandir(TEMPDIR . 'tmp'); 
    foreach ($tmpdir_files as $tmpfile) {
         $current_file = TEMPDIR . "tmp/${tmpfile}";
          if(preg_match("#(?i)GeoLite2-City_\d+#",$tmpfile) ) {           
              if(is_dir(TEMPDIR . 'tmp/'. $tmpfile)) {                  
                  $geo_dir_name =  TEMPDIR . 'tmp/'. $tmpfile;
                  echo "$geo_dir_name is a geocity directory\n";
                  $geo_dir = scandir(TEMPDIR . 'tmp/'. $tmpfile);                
                  echo "Directory name: $geo_dir_name\n";              
                  foreach($geo_dir as $gfile) {
                      if(!is_dir(TEMPDIR . 'tmp/'. $gfile)) {                          
                          if(preg_match("/\.mmdb$/",$gfile)) {                           
                              echo "renaming " . $geo_dir_name. "/$gfile" ."\nTo: " . TEMPDIR . $gfile ."\n";
                             rename($geo_dir_name. "/$gfile" , TEMPDIR . $gfile ) ;
                             continue;
                          }    
                           echo "$gfile\n";
                      }
                     
                  }
               //   break;
              }
              else {
                  echo TEMPDIR . 'tmp/'. $tmpfile . " is NOT a directory\n";                                
              }
          }      
    }
 
}
process_gcity();
?>
