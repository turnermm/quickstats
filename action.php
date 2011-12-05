<?php
/**
 * Creates Simple statistics files based on incoming IP
 *
 * @author  Myron Turner <turnermm02@shaw.ca>
 */

if(!defined('DOKU_INC')) die();
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
define ('QUICK_STATS',DOKU_PLUGIN . 'quickstats/');
require_once DOKU_PLUGIN.'action.php';


class action_plugin_quickstats extends DokuWiki_Action_Plugin {
    private $page_file;
	private $ip_file;
	private $misc_data_file;
	private $pages;
	private $ips;
	private $misc_data;
	private $page_totals_file;
	private $is_edit_user=false;
	private $year_month;
	private $totals;
    private $NL = '/';	

	function __construct() {
	
		 $ip = $_SERVER['REMOTE_ADDR'];	  

         if($this->is_excluded($ip, true)){		  
		   exit("403: Not Available");
         }		 
		$today = getdate();
		
		$ns_prefix = "quickstats:";
		$ns =  $ns_prefix . $today['mon'] . '_'  . $today['year'] . ':'; 
		$this->page_file = metaFN($ns . 'pages' , '.ser');  
		$this->ip_file = metaFN($ns . 'ip' , '.ser');  
		$this->misc_data_file = metaFN($ns . 'misc_data' , '.ser');  
		$this->page_totals_file = metaFN($ns_prefix . 'page_totals' , '.ser');  
		$this->year_month = $today['mon'] . '_'  .$today['year'];
		
		if( preg_match('/WINNT/i',  PHP_OS) ) {    
					$this->NL='\\';				
		}
		
	}
	
    /**
     * Register its handlers with the DokuWiki's event controller
     */
    function register(&$controller) {
    
       $controller->register_hook('DOKUWIKI_STARTED', 'BEFORE', $this, 'set_cookies');
	
        $controller->register_hook('DOKUWIKI_DONE', 'BEFORE', $this,
                                   'add_data');
    }
	
	function set_cookies(&$event, $param) {	
	
	global $ACT;
	global $ID;
			
		if(is_array($ACT) || $ACT=='edit') {   		         
				 $expire = time()+3600;
                 setcookie('Quick_Stats','abc', $expire, '/'); 				 
				 $this->is_edit_user=true;
				 return;
		 }
		 
      if(isset($_COOKIE['Quick_Stats'])) {	         	        
                setcookie("Quick_Stats", 'abc', time()-7200, '/');
			    $this->is_edit_user=true;
     }

	   }
	
	function load_data() {
		
		$this->pages = unserialize(io_readFile($this->page_file,false));
		if(!$this->pages) $this->pages = array();
		
		$this->ips = unserialize(io_readFile($this->ip_file,false));
		if(!$this->ips) $this->ips = array();
		
		$this->misc_data = unserialize(io_readFile($this->misc_data_file,false));
		if(!$this->misc_data) $this->misc_data = array();
	
		$this->totals = unserialize(io_readFile($this->page_totals_file,false));
		if(!$this->totals) $this->totals = array();
	}

     function save_data() {
	     io_saveFile($this->ip_file,serialize($this->ips));
		 io_saveFile($this->page_file,serialize($this->pages));
		 io_saveFile($this->misc_data_file,serialize($this->misc_data));
		 $this->totals[$this->year_month] = $this->pages['site_total'] ;
		 io_saveFile($this->page_totals_file,serialize($this->totals));
	 }
	 
	 function is_excluded($ip,$abort=false) {	    
	   if(!$abort) {
		    $xcl = $this->getConf('excludes');
		}
		else  $xcl = $this->getConf('aborts');	
		$xcl=str_replace("\040", '',$xcl);
	
		if(!$xcl) return false;
		
		$xcludes=explode(',',$xcl);
		$regex = '#'. implode('|',$xcludes) . '#';

		if(preg_match($regex,$ip)){
			return true;
		}
		return false;
	 }
	 
    /**
     * adds new data to stats files  
     *
     * @author  Myron Turner <turnermm02@shaw.ca>
     */
    function add_data(&$event, $param) {
	global $ID;
    global $ACT;
	
	
    if($this->is_edit_user) return;
  	if($ACT != 'show') return;

        $this->load_data();

        require_once("GEOIP/geoipcity.inc");
        require_once('db/php-local-browscap.php');       

	    $ip = $_SERVER['REMOTE_ADDR'];	      
		
         if($this->is_excluded($ip)){		
		     return;
         }		 
       
	   
		$country = $this->get_country($ip);
		if($country) {
			if(!isset($this->misc_data['country'] [$country['code']])) { 
			   $this->misc_data['country'] [$country['code']] =1;			
			}
			else {
			    $this->misc_data['country'] [$country['code']] +=1;
			}
		  }
		  
		  $this->get_browser();
		  
		  $wiki_file = wikiFN($ID);
            if(file_exists($wiki_file)) {
			     if(!$this->pages) {			 
					$this->pages['site_total'] = 1;
					$this->pages['page'][$ID] = 1;
					$this->ips['uniq'] = 0;
			    }
			    else {
					$this->pages['site_total'] += 1;
					$this->pages['page'][$ID]  += 1;						
			    }
			}	
			
		    if(!array_key_exists($ip, $this->ips)) {
		         $this->ips[$ip] = 0;
				 $this->ips['uniq'] = (!isset($this->ips['uniq'])) ? 1 : $this->ips['uniq'] += 1;
		    }
		
		 $this->ips[$ip] += 1;
   	
      $this->save_data();
    }
	
   function get_browser() {
   
	$db= QUICK_STATS . 'db/php_browscap.ini';
    $browser=get_browser_local(null,true,$db);
	
	if(!isset($browser['browser'])) return;

	$this->set_browser_value($browser['browser']);	
		
    if(!isset($browser['platform'])) return;	
	$this->set_browser_value($browser['platform'],'platform');	
    	
	if(!isset($browser['version'])) return;
    $this->set_browser_value($browser['parent'],'version');	
	
  }   
 
    function set_browser_value($val, $which='browser') {
		if(!isset($this->misc_data[$which][$val])) { 
		   $this->misc_data[$which] [$val] =1;			
		}
		else {
			$this->misc_data[$which] [$val] +=1;
		}

   }	
   
	function get_country($ip=null) {
	    if(!$ip) return null;		
		
		if($this->getConf('geoip_local')) {
		     $giCity = geoip_open(QUICK_STATS. 'GEOIP/GeoLiteCity.dat',GEOIP_STANDARD);		
		}
		else {
		    $gcity_dir = $this->getConf('geoip_dir');	            
            $gcity_dat=rtrim($gcity_dir, "\040,/\\") . $this->NL  . 'GeoLiteCity.dat';						
		   //$giCity = geoip_open("/usr/local/share/GeoIP/GeoLiteCity.dat",GEOIP_STANDARD);
		   $giCity = geoip_open($gcity_dat,GEOIP_STANDARD);
		}
		$record = geoip_record_by_addr($giCity, $ip);        		
		return (array('code'=>$record->country_code,'name'=>$record->country_name));
	}
	

}