<?php
/**  
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author    Myron Turner <turnermm02@shaw.ca>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
define ('QUICK_STATS',DOKU_PLUGIN . 'quickstats/');
require_once('GEOIP/ccArraysDat.php');
//error_reporting(E_ALL);
//ini_set('display_errors','1');
/**
 * All DokuWiki plugins to extend the parser/rendering mechanism
 * need to inherit from this class
 */
class syntax_plugin_quickstats extends DokuWiki_Syntax_Plugin {

  private $page_file;
	private $ip_file;
	private $misc_data_file;
	private $pages;
	private $ips;
	private $misc_data;	
	private $cc_arrays;
	private $use_titles;

	function __construct() {

		$this->cc_arrays = new ccArraysDat();
		
	}

   /**
    * Get an associative array with plugin info.    
    */
    function getInfo(){
        return array(
            'author' => 'Myron Turner',
            'email'  => 'turnermm02@shaw.ca',
            'date'   => '2011-11-2',
            'name'   => 'Quickstats Plugin',
            'desc'   => 'Output browser/user stats to wiki page',
            'url'    => 'http://www.dokuwiki.org/plugin:quickstats',
        );
    }

   /**
    * Get the type of syntax this plugin defines. 
    */
    function getType(){
        return 'substition';
    }
	
    /**
     * What kind of syntax do we allow (optional)
     */
//    function getAllowedTypes() {
//        return array();
//    }
   
   /**
    * Define how this plugin is handled regarding paragraphs.
    *   
    * normal:  The plugin can be used inside paragraphs.
    * block: Open paragraphs need to be closed before plugin output.
    * stack  (Special case): Plugin wraps other paragraphs.
    */
    function getPType(){
        return 'block';
    }

   /**
    * Where to sort in?
    *  
    */
    function getSort(){
        return 100;
    }


   /**
    * Connect lookup pattern to lexer.
    *
    * @param $aMode String The desired rendermode.
    * @return none
    * @public
    * @see render()
    */
    function connectTo($mode) {
      $this->Lexer->addSpecialPattern('~~QUICKSTATS:.*?~~',$mode,'plugin_quickstats');
    }
	
//    function postConnect() {
//      $this->Lexer->addExitPattern('</TEST>','plugin_quickstats');
//    }


   /**
    * Handler to prepare matched data for the rendering process.
    *
    */
    function handle($match, $state, $pos, &$handler){
	
        switch ($state) {
          case DOKU_LEXER_SPECIAL :		 			
		    $match =  trim(substr($match,13,-2));			
			if($match) {
			     $date = "";
			     if(strpos($match,'&') !== false) {		
				 
				         /* 
						     catch syntax errors 			
						     assumes single parameter with trailing or prepended & 
						 */
					     if($match[strlen($match)-1] == '&'  || $match[0]  == '&') { 
						     $match = trim($match,'&');
							  if($this->is_date_string($match)) {
							       return array('basics',$match);			       
							  }
						     return array('basics',"");			       
						 }
						 
						 /* process valid paramter string */
						 list($m1,$m2) = explode('&',$match,2);
						 if($this->is_date_string($m1)) {
						     $date=$m1;
							 $match = $m2;
						 }
						 else {
						 	$date=$m2;
							$match = $m1;
						 }
				 }
				 else  if($this->is_date_string($match)) {
					 $date = $match;
				     $match = 'basics';					 
				 }
			}
			else {
			       return array('basics',"");			       
            }			
			 
             return array(strtolower($match),$date);
             break;
        }
        return array();
    }

	function is_date_string($str) {
	     return preg_match('/\d\d_\d\d\d/',$str);
    }	
	
   /**
    * Handle the actual output creation.    
    */
    function render($mode, &$renderer, $data) {
	 
        if($mode == 'xhtml'){
		
		   list($which, $date_str) = $data;
		   //msg($which);
		   $this->load_data($date_str,$which);
		   if($which == 'basics') {
				$renderer->doc .= "<div class='quickstats basics' style='margin: auto;width: 820px;'>" ;          		
		   }
		   else {
		        $class = "quickstats $which";
				$renderer->doc .= "<div class='$class'>";
		   }
		    switch ($which) {
			   case 'basics':			   
				$this->misc_data_xhtml($renderer);
				$this->pages_xhtml($renderer);
			    break;
			case 'ip':              
				$this->ip_xhtml($renderer);
			   	break;
			case 'pages':
				$this->pages_xhtml($renderer,true);
				break;
			case 'misc':
				$this->misc_data_xhtml($renderer,true,'misc');
				break;
			case 'countries':
				$this->misc_data_xhtml($renderer,true,'country');
				break;	
			}
            
	       $renderer->doc .= "</div>" ;          	
			 

            return true;
        }
        return false;
    }
	

    function sort(&$array) {
    	uasort($array, 'QuickStatsCmp');
    }   

    function row($name,$val,$num="&nbsp;") {	
	    return "<tr><td>$num&nbsp;&nbsp;</td><td>$name</td><td>&nbsp;&nbsp;&nbsp;&nbsp;$val</td></tr>\n";
       
    }	
	
	function thead($titles) {	
	   $name_title =$titles['name'];
	   $val_title=$titles['val'];
	   $num_title=isset($titles['val']) ? $titles['val'] : "&nbsp;";
	    return "<tr><th>$num_title</th><th>$name_title</td><th>$val_title</th></tr>\n";
       
    }	
	function load_data($date_str=null,$which) {
		$today = getdate();
		if($date_str) {
		   list($mon,$yr) = explode('_',$date_str);
		   $today['mon'] = $mon;
		   $today['year'] = $yr;
		}
    	$ns_prefix = "quickstats:";
		$ns =  $ns_prefix . $today['mon'] . '_'  . $today['year'] . ':'; 
		
		$this->page_file = metaFN($ns . 'pages' ,'.ser');  
		$this->ip_file = metaFN($ns . 'ip' , '.ser');  
		$this->misc_data_file = metaFN($ns . 'misc_data' , '.ser');  
	
		if($which == 'basics' || $which == 'pages') {
			$this->pages = unserialize(io_readFile($this->page_file,false));
			if(!$this->pages) $this->pages = array();
		}
		if($which == 'basics' || $which == 'ip') {
			$this->ips = unserialize(io_readFile($this->ip_file,false));
			if(!$this->ips) $this->ips = array();
		}
		if($which == 'basics' || $which == 'countries'  || $which == 'misc') {
			$this->misc_data = unserialize(io_readFile($this->misc_data_file,false));
			if(!$this->misc_data) $this->misc_data = array();
		}
		
		//$this->page_totals_file = metaFN($ns_prefix . 'page_totals' . '.ser'); 
	    //$this->totals = unserialize(io_readFile($this->page_totals_file,false));		

	
	}
	
	function table($data,&$renderer,$numbers=true) {
	   
	    if($numbers) 
		   $num = 0;
		 else  $num = "&nbsp;";
	 /*
		if($header) {
		    $this->thead($header);
		}
	*/
	   $ttl = 0;
	    $renderer->doc .= "<table cellspacing='4' >\n";
		foreach($data as $item=>$count) {		
		     if($numbers) $num++;
			 $ttl += $count;
			 $renderer->doc .= $this->row($item,$count,$num);
			
		}
	   $renderer->doc .= "</table>\n";
	   return $ttl;
	}
	
	function ip_xhtml(&$renderer) {
	   $uniq = $this->ips['uniq'];
	   unset($this->ips['uniq']);
	   $this->sort($this->ips);
	   
	 //  $renderer->doc .= '<div style="margin: 10px 250px; overflow:auto; padding: 8px; width: 300px;">';
	   //$renderer->doc .= '<div class="quickstats ip" style="clear:left; float:left; overflow:auto; padding: 8px; width: 300px;">';
	   $renderer->doc .= '<div class="quickstats ip">';
	   $renderer->doc .= '<span class="title">Unique IP Addresses</span>';
	   $total_acceses = $this->table($this->ips,$renderer);	
	   $renderer->doc .= "<span class='total'>Total accesses: $total_acceses</span></br>\n"; 
	   $renderer->doc .= "<span class='total'>Total unique ip addresses: $uniq</span></br>\n";  
	   $renderer->doc .= "</div>\n";
	}  
	
	function pages_xhtml(&$renderer, $no_align=false) {		 
		
		if(!$this->pages) return array();            
	    
			$this->sort($this->pages['page']);
	        if($no_align) {
					$renderer->doc .= '<div>';
			}
		    else {
				$renderer->doc .= '<div style="margin: 10px 250px; overflow:auto; padding: 8px; width: 300px;">';
				}
		    $renderer->doc .= '<span class="title">Page Accesses</span>';
            $this->table($this->pages['page'],$renderer);
		    $renderer->doc .=  "<span class='total'>Total accesses: " . $this->pages['site_total'] .'</span>';
		    $renderer->doc .= "</div>\n";
		
	}
    function misc_data_xhtml(&$renderer,$no_align=false,$which='all') {
    	

	   $renderer->doc .= "\n";
	
	   if($which == 'all' || $which == 'misc') {
	   
			$browsers = $this->misc_data['browser'];
			$platform = $this->misc_data['platform'];
			$version = $this->misc_data['version'];
			$this->sort($browsers);
			$this->sort($platform);
			$this->sort($version);   
			
              $renderer->doc .= "\n\n<!-- start misc -->\n";
			   if($no_align) {
					$renderer->doc .= '<div>';
			   }
			  else {
					$renderer->doc .= '<div style="float:left;width: 200px; margin-left:20px;">';
				}	
				$renderer->doc .="\n\n";
				$renderer->doc .= '<br /><span class="title">Browsers</span>';
				
				$num=0;
				$renderer->doc .= "<table border='0' >\n";
				foreach($browsers as $browser=>$val) {           				  
				   $num++;
				   $renderer->doc .= $this->row($browser, $val,$num);
				   $renderer->doc .= "<tr><td colspan='3' style='border-top: 1px solid black'>";
				   $v = $this->get_subversions($browser,$version); 		        		   
				   $this->table($v,$renderer, false,false);
				   $renderer->doc .= '</td></tr>';
				}
			   $renderer->doc .= "</table>\n\n";	   
        
		  
			$renderer->doc .= '<span class="title">Platforms</span>';		
			$this->table($platform,$renderer);				
			$renderer->doc .= "</div>\n<!--end misc -->\n\n";
	   }
	   
	    if($which == 'misc') return;
		
		$countries = $this->misc_data['country'];
		$this->sort($countries);
		
			if($no_align) {
					$renderer->doc .= '<div>';
			}
			else {
		           $renderer->doc .= "<div style='float: right; overflow: auto; width: 200px; margin-right: 1px;'>";
		    }
		   $renderer->doc .= '<span class="title">Countries</span>';
		   
				$renderer->doc .= "<table cellspacing='4'>\n";
				$num = 0;
				$total = 0;
				foreach($countries as $cc=>$count) {		
					if(!$cc) continue;
					 $num++;
					 $total+=$count;
					 $cntry=$this->cc_arrays->get_country_name($cc) ;
					 $renderer->doc .= $this->row($cntry,$count,$num);
				}
			  //$renderer->doc .= "<tr><td colspan='3'>Total accesses: $total</td><tr />";
			  $renderer->doc .= '</table>';		  
			  $renderer->doc .= "<span class='total'>Total accesses: $total</span></br>";
			  
		  
			 $renderer->doc .= "</div>\n";
	     
	    
    } 
	
    function get_subversions($a,$b) {
	    $tmp = array();
	
	     foreach($b as $key=>$val) {	
	        if(strpos($key,$a) !== false) {
		        $tmp[$key] = $val;
		    }
	    }
	   $this->sort($tmp);
	   return  $tmp;
    }
}

  	    function QuickStatsCmp($a, $b) {
			if ($a == $b) {
				return 0;
			}
			return ($a > $b) ? -1 : 1;
		 }

?>