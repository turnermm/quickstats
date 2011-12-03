<?php

function misc_data() {
   $misc_data =read_file('misc_data.ser');
   $countries = $misc_data['country'];
   $browsers = $misc_data['browser'];
   $platform = $misc_data['platform'];
   $version = $misc_data['version'];
   uasort($countries, 'cmp');
   uasort($browsers, 'cmp');
   uasort($platform, 'cmp');
   uasort($version, 'cmp');
   print_r($countries);
  
   print_r($platform);
 
   
   foreach($browsers as $browser=>$val) {
       echo "$browser=>$val\n";
	   $v = get_subversions($browser,$version); 
       print_r($v);
   }

}

function pages() {
	$pages = read_file('pages.ser');

	uasort($pages['page'], 'cmp');

	foreach($pages['page'] as $page=>$count) {
	  echo "$count\t$page\n";
	}
	echo "Total: " . $pages['site_total'] . "\n";
}

function read_file($file) {
		return unserialize(file_get_contents($file));				
}

function get_subversions($a,$b) {
	$tmp = array();
	
	foreach($b as $key=>$val) {	
	     if(strpos($key,$a) !== false) {
		     $tmp[$key] = $val;
		 }
	}
	uasort($tmp,'cmp');
	return  $tmp;
    
}

function cmp($a, $b) {
    if ($a == $b) {
        return 0;
    }
    return ($a > $b) ? -1 : 1;
}
misc_data();
//pages();
?>