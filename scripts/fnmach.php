<?php
if(!defined('DOKU_INC')) define('DOKU_INC', realpath(dirname(__FILE__) .'/../../') . '/');
define ('TEMPDIR', DOKU_INC . 'data/tmp/');
echo DOKU_INC . "\n";

$ro = ini_get('phar.readonly');
echo $ro . "\n";
if($ro) ini_set('phar.readonly','0');

$files = scandir(TEMPDIR);
foreach ($files as $file) {
      echo $file ."\n";
      if (preg_match("#GeoLite2-City.tar.gz#", $file,$matches)) { 
       $p = new PharData($file);
       $p->decompress(); // creates /path/to/my.tar
       $tar = str_replace('.gz', "", $file);       
        echo $tar . "\n";
        try {
             $phar = new PharData($tar);
             $phar->extractTo('./tmp'); // extract all files
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
      
            $dir =TEMPDIR . 'tmp/';
            $geodir = scandir($dir );
            foreach($geodir  as $entry) {
                 echo $dir . $entry . "\n";
                if(preg_match("#\.mmdb$#",$entry)) {
                   echo $dir . $entry . "\n";
                }
            }
     
    }
}

function delete_gcity() {
    $tmpdir_files = scandir('./tmp');
//print_r($tmpdir_files ); 
foreach ($tmpdir_files as $tmpfile) {
      if(preg_match("#(?i)GeoLite2-City_\d+#",$tmpfile) ){
          echo $tmpfile . "\n";
          if(is_dir(TEMPDIR . 'tmp/'. $tmpfile)) {
              echo TEMPDIR . 'tmp/'. $tmpfile . " is a directory\n";
          }
          else echo TEMPDIR . 'tmp/'. $tmpfile . " is NOT a directory\n";
          break;
      }
}
 
}
?>
