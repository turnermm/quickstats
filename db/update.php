<?php
   
    require_once('../GEOIP/geoipcity.inc');
	require_once('../GEOIP/ccArraysDat.php');
	//$giCity = geoip_open('../GEOIP/GeoLiteCity.dat',GEOIP_STANDARD);		
	$giCity = geoip_open("/usr/local/share/GeoIP/GeoLiteCity.dat",GEOIP_STANDARD);
	
	/* convert old count.ser to 2 arrays: page and $ip */
	function revise_data() {
	 
		$counter_file = 'count.ser';
		$counter = unserialize(file_get_contents($counter_file));
				
		$update = array();
		$update['site_total'] = $counter['site_total'];
		$update['page'] = $counter['page'];
        file_put_contents('pages_rev.ser', serialize($update));
		unset($counter['page']); 
		unset($counter['site_total']); 
        file_put_contents('ip_rev.ser', serialize($counter));
	}
	
	  /* create multi-dimensional hash of ip array:  ip=>Array ( ['count'],['ccode'] ) */	  
	function update_ip_array(&$temp) {
	        
			$ips = unserialize(file_get_contents('ip_rev.ser'));
			foreach($ips as $ip=>$num) {
			    $temp[$ip]['count'] = $num;
				if(!isset($temp[$ip]['ccode'])) $temp[$ip]['ccode'] = get_country_code($ip);
			}
	        $temp['uniq'] = $temp['uniq']['count'];		
	}

    function code_to_country_array(&$new) {	
	  $ccdat = new ccArraysDat();
	 $countries=array();
	 unset($new['uniq']);
	 foreach($new as $ip=>$ar) {
		 $c =  $ar['ccode'];		 
		 if(!isset($countries[$c])) {	
		   	$countries[$c] =  $ccdat->get_country_name($c);
			//$countries[$c] =  get_country_name($ip);
		 }
	 }
    }	
	
	/*create misc_data.ser */
	function create_misc_data(&$new,&$temp) {
	    $temp = array( 'country' => array( ), 'browser' => array(), 'platform' => array(), 'version' => array() );
		foreach($new as $ar) {
		     $cc = $ar['ccode'];
		     if(!isset( $temp['country'][$cc] )) {
			      $temp['country'][$cc] =  $ar['count'];
			}
			else {
			   $temp['country'][$cc] +=  $ar['count'];
			}
		}
		if(file_exists('misc_data.ser')) {
		     $misc = unserialize(file_get_contents('misc_data.ser'));
			 if(isset($misc['browser'])) {
			        $temp['browser'] = $misc['browser'];
			 }			  
			 $temp['platform'] = $misc['platform'];
			 $temp['version'] = $misc['version'];
		}
	   $new = $temp;
	}
	function get_country_code($ip=null) {
	    if(!$ip) return "";		
		global $giCity;
		$record = geoip_record_by_addr($giCity, $ip);			
		 return $record->country_code;
	}

	function get_country_name($ip=null) {
	    if(!$ip) return "";				
		global $giCity;
		$record = geoip_record_by_addr($giCity, $ip);	
		//return (array('code'=>$record->country_code,'name'=>$record->country_name));
		 return $record->country_name;
	}
	
	function ip_country_sort($a,$b) {
	   
	       if ($a['ccode'] == $b['ccode'] ) {
				return 0;
			}
			return ($a['ccode']  > $b['ccode'] ) ? 1 : -1;
		
	   }
	
	function ip_count_sort($a,$b) {
	   
	       if ($a['count'] == $b['count'] ) {
				return 0;
			}
			return ($a['count']  > $b['count'] ) ? -1 : 1;
		
	   }
	
	function do_task($which) {
	    global $argv;
	     $which = trim($which);
   		 if($which != 'revise' && $which !=  'multi') {
		        $new = unserialize(file_get_contents('ip_multi_array.ser'));
		 }
	     else {
		     $new = false;
		 }	 

	     switch( $which) {
		    case 'revise':
			   	revise_data();   // separate data from old count.ser into two separate arrays: page and ip
				break;
			case 'multi':
			   $temp = array();
               update_ip_array($temp); // make new ip array into hash with count and country keys 	
               file_put_contents('ip_multi_array.ser', serialize($temp));			   
			   break;   
			 case 'misc':
			   unset($new['uniq']);
			   create_misc_data($new,$temp);
			   file_put_contents('misc_data_update.ser', serialize($temp));			   
               break;			 
			case  'cc_country':			  
              code_to_country_array($new) ;			
			  break;
		   case 'cc_sort':		     
		     uasort($new,'ip_country_sort');
             break;			 
 		   case 'nsort':		    
		     uasort($new,'ip_count_sort');
             break;			
           default: 
              echo "$which not found\n";
              echo $argv[0] . "  revise, multi, cc_country, cc_sort, nsort\n";			  
		 }
	     if($new) {
		     print_r($new);
         }		 
	}
	
 
 
	 do_task($argv[1]);

	

?>