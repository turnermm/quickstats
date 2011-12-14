<?php
/**
 * 
 * 
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author    Myron Turner <turnermm02@shaw.ca> 
 */

 
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
  
     function __construct() {
       $this->helper = $this->loadHelper('quickstats', true);    
       $this->cache = $this->helper->getCache();          
     }
    /**
     * handle user request
     */
    function handle() {
    
      if (!isset($_REQUEST['cmd'])) return;   // first time - nothing to do

      $this->output = 'invalid';
      $this->deletions = array();
      if (!checkSecurityToken()) return;
      if (!is_array($_REQUEST['cmd'])) return;     
    
      switch (key($_REQUEST['cmd'])) {
        case 'delete' :          
		    $this->deletions = $_REQUEST['del'];	
			$this->to_confirm = implode(',',array_keys($this->deletions));
		//	$this->output = print_r($_REQUEST,true);
		//	$this->output  .= "\n to confirm: " .  $this->to_confirm;
			 break;
        case 'confirm' :
	      $this->output = print_r($_REQUEST,true);
		   $this->cache=$this->helper->pruneCache($_REQUEST['confirm'],$_REQUEST['del']);
		   break;
      }      
     
   
      
    }
 
    /**
     * output appropriate html
     */
    function html() {
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
      
      ptln('</form>');
      
      // $this->debug(); 	  

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
}
