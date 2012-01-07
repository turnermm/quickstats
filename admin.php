<?php
/**
 * 
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author    Myron Turner <turnermm02@shaw.ca> 
 */

 if(!defined('DOKU_INC')) die();
 require_once(DOKU_PLUGIN.'admin.php');
/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
 
class admin_plugin_quickstats extends DokuWiki_Admin_Plugin {

    private $output = '';
    private $helper;
    private $cache;
    private $deletions;
	private $to_confirm;
    private $cc_arrays;
    private $countries;
    private $meta_path;
    private $page_totals; 
    private $uniqIPTotal;
    private $uniqIPCurrent;
    private $page_accessesTotal=0;
    private $page_accessesCurrent=0;
    
     function __construct() {
     
       $this->helper = $this->loadHelper('quickstats', true);    
       $this->cache = $this->helper->getCache(); 
       $this->cc_arrays = $this->helper->get_cc_arrays();
       $this->meta_path = $this->helper->metaFilePath(true) ;     
       $this->page_totals = unserialize(io_readFile($this->meta_path .  'page_totals.ser'));
       if(!$this->page_totals) $this->page_totals = array();
       if(!empty($this->page_totals)) {
           foreach($this->page_totals as $ttl) {
              $this->page_accessesTotal+=$ttl;
              $this->page_accessesCurrent=$ttl;
           }
           $this->countries_setup();
           $this->uniq_ip();
       }
    
     }

     /*
     *  Create a list of countries accessed during last 6 months, for countries Select
     */     
     function countries_setup() {
        
         $this->countries = array();
         $country_codes = array();
         $data_dirs = array_reverse(array_keys($this->page_totals));                
         if(count($data_dirs) > 6) {
            $data_dirs = array_slice($data_dirs,0,6);
         }

         $ns_prefix = "quickstats:"; 
         foreach($data_dirs as $dir) {
             $ns =  $ns_prefix .  $dir . ':'; 
             $misc_data_file = metaFN($ns . 'misc_data' , '.ser');  
             $misc_data = unserialize(io_readFile($misc_data_file,false));
             if(!empty($misc_data) &&   !empty($misc_data['country'])) {                
                 $country_codes = array_merge ($country_codes, array_keys($misc_data['country']));               
             }
         }
         foreach($country_codes as $cc) {
             if($cc) {
                $this->countries[$cc]=$this->cc_arrays->get_country_name($cc) ;
             }
         }
         asort($this->countries);

     }
     
     function uniq_ip() {
            $dirs = array_keys($this->page_totals);
            $current_dir = array_pop($dirs);
            $ns_prefix = "quickstats:"; 
            $uniq_data_file = metaFN($ns_prefix . 'uniq_ip' , '.ser');  
            if(file_exists($uniq_data_file)) {
                $uniq_data = unserialize(io_readFile($uniq_data_file,false));
            }
            else if(count($dirs) > 0) {
                $uniq_data = array();
                foreach($dirs as $dir) {
                    $ns =  $ns_prefix .  $dir . ':'; 
                    $ip_file = metaFN($ns . 'ip' , '.ser');  
                    $ip_data = unserialize(io_readFile($ip_file,false));
                    if(empty($ip_data)) { 
                       $ip_data = array();
                     }
                     else {
                         unset($ip_data['uniq']);
                    }                     
                    $ip_data = array_keys($ip_data);
                    $uniq_data = array_merge ($uniq_data , $ip_data);
                }
                unset($uniq_data['uniq']);
                unset($uniq_data['last']);
                $uniq_data = array_unique($uniq_data);
                $uniq_data['uniq'] = count($uniq_data);
                $uniq_data['last'] = $dir;
                io_saveFile($uniq_data_file,serialize($uniq_data)); 
            }
            else {
                $uniq_data = array();
            }
            
            $ns =  $ns_prefix .  $current_dir . ':'; 
            $ip_file = metaFN($ns . 'ip' , '.ser');  
            $ip_data = unserialize(io_readFile($ip_file,false));
            $this->uniqIPCurrent=$ip_data['uniq'];
         
            $uniq_data = array_unique(array_merge ($uniq_data , array_keys($ip_data)));
            $uniq_data['uniq'] = count($uniq_data);
            $this->uniqIPTotal = $uniq_data['uniq'];
            if($current_dir != $uniq_data['last'] ) {      
               $uniq_data['last'] = $current_dir;
               io_saveFile($uniq_data_file,serialize($uniq_data)); 
            }
            
            
       
     }
     
    /**
     * handle user request
     */
    function handle() {      
      if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do

      $this->output ="";
      
      $this->deletions = array();
      if (!checkSecurityToken()) return;
      if (!is_array($_REQUEST['cmd'])) return;     
    
      switch (key($_REQUEST['cmd'])) {
        case 'delete' :          
           if(isset($_REQUEST['del']) && is_array($_REQUEST['del']) && !empty($_REQUEST['del'])) {
		    $this->deletions = $_REQUEST['del'];	
			$this->to_confirm = implode(',',array_keys($this->deletions));
            }
            else {
               $this->deletions = array();
               $this->to_confirm = array();
            }
			 break;
        case 'confirm' :
		   $this->cache=$this->helper->pruneCache($_REQUEST['confirm'],$_REQUEST['del']);
		   break;
          
      }      
     
   
      
    }
 
    /**
     * output appropriate html
     */
    function html() {
      ptln('<div id="qs_general_intro">');
      ptln( $this->locale_xhtml(general_intro));   
      ptln('</div>');
      ptln('<button class="button" onclick=" toggle_panel(' . "'qs_cache_panel'" . ');">' . $this->getLang("btn_prune") . '</button>');
      ptln('&nbsp;&nbsp;<button class="button" onclick="toggle_panel(' . "'quick__stats'" . ');">' . $this->getLang("btn_queries") . '</button>');
      ptln('&nbsp;&nbsp;<button class="button" id="qs_query_info_button"  onclick="qs_open_info(' . "'qs_query_intro'" . ');">' . $this->getLang("btn_qinfo") . '</button>');
      
      /* Cache Pruning Panel */
      if(isset($this->deletions) || isset($this->to_confirm)) {
         $qs_display = ' style="display:block; "';
      }
      else  $qs_display = "";
     
      ptln('<div ' . $qs_display . ' id="qs_cache_panel">');
      
      ptln( $this->locale_xhtml(intro));   
      ptln('<form action="'.wl($ID).'" method="post">');
      
      // output hidden values to ensure dokuwiki will return back to this plugin
      ptln('  <input type="hidden" name="do"   value="admin" />');
      ptln('  <input type="hidden" name="page" value="'.$this->getPluginName().'" />');
	  ptln('  <input type="hidden" name="confirm" value="'.$this->to_confirm .'" />');
      formSecurityToken();
	  
      ptln('<table cellspacing = "4">'); 
      foreach($this->cache as $key=>$id) {
           $this->get_item($key,$id);
      }
      ptln('</table>'); 
	  
      ptln('  <input type="submit" name="cmd[delete]"  class="button" value="'.$this->getLang('btn_delete').'" />');
      ptln('  <input type="submit" name="cmd[restore]"  class="button" value="'.$this->getLang('btn_restore').'" />');
      ptln('  <input type="submit" name="cmd[confirm]"  class="button" value="'.$this->getLang('btn_confirm').'" />');
      
      ptln('</form></div>');
             
         /* Stats Panel */    
      $today = getdate();
      ptln('<div id="quick__stats" class="quick__stats">');
      ptln('<div class="qs_query_intro" id="qs_query_intro">' . $this->locale_xhtml(query));
      ptln('<button class="button" onclick="qs_close_panel(' . "'qs_query_intro'" . ');">Close info window</button>');
      ptln('</div>');   
       
      ptln('<p>&nbsp;</p><p><form action="javascript:void 0;">');
      ptln('<input type="hidden" name="meta_path" value="'.$this->meta_path.'" />');   
      
      ptln('<table  border="0"  STYLE="border: 1px solid black" cellspacing="0">');
      ptln('<tr><th class="thead">&nbsp;' . $this->getLang('label_qs_pages') .' &nbsp;</th><th class="thead" colspan="2">' . $this->getLang('label_date')  .'</th><td><!-- divider --></td><th class="thead" colspan="2">' . $this->getLang('label_search') . '</th></tr>');
      ptln('<tr><td rowspan="5" valign="top" class="padded"><select name="popups" id="popups" size="6" onchange="onChangeQS(this);">');
      $this->get_Options('popups');
      ptln('</select></td>'); 
     
      ptln('<td rowspan="5" valign="top" class="padded" nowrap>&nbsp;<select name="month" multiple id="month" size="6">');
      $this->get_Options('months',$today['mon']) ;
      ptln('</select></td><th class="padded" nowrap>&nbsp;' . $this->getLang('label_sel_months') . ' </th><td rowspan="6" class="divider"></td>');
      
      ptln('<td class="padded" nowrap>&nbsp;' . $this->getLang('label_ip') . ':&nbsp;<input type="text" name = "ip" id="ip" size="16" value=""' .NL .'</td>');

      ptln('<td rowspan="5" align="top" class="padded" nowrap>&nbsp;<select name="country_names" id="country_names" size="6">');
      $this->get_Options('country') ;
      ptln('</select></td>');
      ptln('</tr>'); 

      ptln('<tr><td></td><td class="padded" nowrap>&nbsp;' . $this->getLang('label_page') . ':&nbsp;<input type="text" name = "page" id="page" size="36" value=""</td></tr>');
      ptln('<tr><th valign="bottom" class="padded">&nbsp' . $this->getLang('label_sel_year')  .'</th>');
      ptln('<td class="padded  place_holder">Brief pages display: <input type="checkbox" id="qs_p_brief" name="qs_p_brief"></td></tr>'); // here
      ptln('<tr><td valign="top" class="padded">&nbsp;Year (4 digits):&nbsp;<input type="text" name = "year" id="year" size="4" value="' . $today['year'] . '">' .NL .'</td><td class="padded  place_holder">&nbsp;</td></tr>');
      ptln('<tr><td class="padded place_holder"></td><td class="padded place_holder"></td></tr><tr><td class="padded place_holder">&nbsp;</td>');
      ptln('<td class="padded place_holder">&nbsp;</td>');
      ptln('<td class="padded place_holder">&nbsp;</td>');
      ptln('<td class="padded place_holder">&nbsp;</td>');
      ptln('<td class="padded" style="padding-top:2px;"><a href="javascript:qs_country_search();" style="text-decoration:underline">Search:</a> <input type="text" value ="" id="cc_extra" name="cc_extra" size="24"></td>');
     
      ptln('</table>');
           
      ptln('<p><input type="submit" onclick="getExtendedData(this.form,\''. DOKU_INC . '\');"  class="button" value="'.$this->getLang('btn_submit_query').'" />');
      ptln('&nbsp;&nbsp;&nbsp;&nbsp;<span class="status">[ <b>' . $this->getLang('label_uniq_ip')  . '</b>&nbsp;&nbsp;' . $this->getLang('label_total') . ': ' .  $this->uniqIPTotal . '&nbsp;&nbsp;Current month: ' . $this->uniqIPCurrent .' ]');
      ptln('&nbsp;&nbsp;&nbsp;[ <b>' . $this->getLang('label_page_access') . '</b>&nbsp;&nbsp;' . $this->getLang('label_total') . ': ' . $this->page_accessesTotal. '&nbsp;&nbsp;' . $this->getLang('label_current_month') . ': ' . $this->page_accessesCurrent.  ' ]</span>');   
      ptln('</p></form></p>');
     
      ptln('<p>&nbsp;</p><div id="extended_data"></div>');
      ptln('</div>');
      $this->debug(); 	  

    }
	
	function debug() {
	    return;
	    ptln('<p><pre>');	  
        ptln(htmlspecialchars($this->output));
	
	   if($this->deletions && count($this->deletions)) {
	       $this->deletions_str = print_r($this->deletions,true);
     	   ptln($this->deletions_str);
		}
		  
        ptln('</pre></p>');
 
	}
	
    function get_item($key,$id) {
        $checked = "";
        $bg_color = "";
        if(isset($this->deletions) && array_key_exists($key,$this->deletions)) {
              $checked='checked';
              $bg_color = "style = 'background-color: #dddddd;'";
        }
     
       $key1 = $key . '_1';      
        ptln("<tr><td $bg_color id='$key1'>&nbsp;<input type='checkbox' name='del[$key]' value='$id' onclick='uncheck(\"$key\");' $checked>&nbsp;</td><td $bg_color id='$key'>&nbsp;$id&nbsp;</td></tr>");	         
    }
    
    function get_Options($which,$selected_month=1) {
        if($which == 'months') {
            $months = array('Jan'=>1, 'Feb'=>2, 'Mar'=>3, 'Apr'=>4, 'May'=>5, 'Jun'=>6, 'Jul'=>7, 'Aug'=>8, 'Sep'=>9, 'Oct'=>10, 'Nov'=>11, 'Dec'=>12);            
            ptln("<option value='0'> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; " . NL);
            foreach ($months as $month=>$value) {
                $selected = "";
                if($value == $selected_month) {
                    $selected = 'selected';
                }
                ptln("<option value='$value' $selected>  $month " . NL);
            }
        }
        else if($which == 'popups') {
            ptln("<option value='0' selected> &nbsp; Click to view file &nbsp;" . NL);
            foreach($this->cache as $id) {                
                 ptln("<option value='$id'> $id" . NL);
            }
       }
      else if($which == 'country') {
       
       
        ptln("<option value='0' selected> &nbsp; <b>Select Country</b> &nbsp;" . NL);        
        foreach($this->countries as $cc => $country) {
             ptln("<option value='$cc'> $country" . NL);
        }
      }
    }

}
