<?php
/**
 * Plugin Skeleton: Displays "Hello World!"
 *
 * 
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author    Myron Turner <turnermm02@shaw.ca>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');
define ('QUICK_STATS',DOKU_PLUGIN . 'quickstats/');
require_once('GEOIP/ccArraysDat.php');
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

	function __construct() {

		$this->page_file =  QUICK_STATS . 'db/pages.ser';
		$this->ip_file =  QUICK_STATS . 'db/ip.ser';
		$this->misc_data_file =  QUICK_STATS . 'db/misc_data.ser';
		
//		$this->pages = unserialize(io_readFile($this->page_file,false));
	//	if(!$this->pages) $this->pages = array();
		
		//$this->ips = unserialize(io_readFile($this->ip_file,false));
		//if(!$this->ips) $this->ips = array();
		
		$this->misc_data = unserialize(io_readFile($this->misc_data_file,false));
		if(!$this->misc_data) $this->misc_data = array();
		
		//$this->cc_arrays = new ccArraysDat();
		
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
		    $match =   $match = trim(substr($match,13,-2));
			  $match = 'pages';
             return array(strtolower($match));
             break;
        }
        return array();
    }

   /**
    * Handle the actual output creation.    
    */
    function render($mode, &$renderer, $data) {
	 
        if($mode == 'xhtml'){
		    switch ($data[0]) {
			   case 'pages':
				$this->pages_xhtml($renderer);
			}
            //$renderer->doc .= "<p>$match</p>" ;          
            return true;
        }
        return false;
    }
	
   function sort(&$array) {
  	    function cmp($a, $b) {
			if ($a == $b) {
				return 0;
			}
			return ($a > $b) ? -1 : 1;
		 }
    	uasort($array, 'cmp');
    }   

	function pages_xhtml(&$renderer) {
		 
			$this->pages = unserialize(io_readFile($this->page_file,false));
		    if(!$this->pages) return array();             
	       //$this->pages['page'] =
			$this->sort($this->pages['page']);
			$renderer->doc .= '<ol>';
		   foreach($this->pages['page'] as $page=>$count) {
		      $renderer->doc .="<li><span  style='color: black; font-weight:normal;'>$page:$count</span></li>";
		  }
		  $renderer->doc .= '</ol>';
		  $renderer->doc .=  "Total: " . $this->pages['site_total'] . "<br />";
	}
}


?>