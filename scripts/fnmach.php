<?php

$ro = ini_get('phar.readonly');
echo $ro . "\n";
if($ro) ini_set('phar.readonly','0');

$files = scandir('.');
foreach ($files as $file) {
    if (preg_match("#(GeoLite2-City_\d+)\.tar.gz#", $file,$matches)) { 
        $p = new PharData($file);
       $p->decompress(); // creates /path/to/my.tar
        $tar = str_replace('.gz', "", $file); 
        try {
             $phar = new PharData($tar);
             $phar->extractTo('./tmp'); // extract all files
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
        }
        if($matches [1]) {
            $dir = './tmp/' . $matches [1] . '/';
            $geodir = scandir('./tmp/' . $matches [1] );
            foreach($geodir  as $entry) {
                if(preg_match("#\.mmdb$#",$entry)) {
                   echo $dir . $entry . "\n";
                }
            }
        }
    }
}
?>
