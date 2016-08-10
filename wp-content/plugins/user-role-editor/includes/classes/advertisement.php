<?php

/*
 * User Role Editor plugin: advertisement showing class
 * Author: Vladimir Garagulya
 * email: vladimir@shinephp.com
 * site: http://shinephp.com
 * 
 */

class URE_Advertisement {
	
	private $slots = array(0=>'');
				
	function __construct() {
		
		$used = array(-1);
		//$index = $this->rand_unique( $used );		
  $index = 0;
		$this->slots[$index] = $this->admin_menu_editor();
		$used[] = $index;
    				
	}
	// end of __construct
	
	
	/**
	 * Returns random number not included into input array
	 * 
	 * @param array $used - array of numbers used already
	 * 
	 * @return int
	 */
	private function rand_unique( $used = array(-1) ) {
		$index = rand(0, 2);
		while (in_array($index, $used)) {
			$index = rand(0, 2);
		}
		
		return $index;
	}
	// return rand_unique()
	
	
	// content of Admin Menu Editor advertisement slot
	private function admin_menu_editor() {
	
		$output = '
			<div style="text-align: center;">
				<a href="http://w-shadow.com/admin-menu-editor-pro/?utm_source=UserRoleEditor&utm_medium=banner&utm_campaign=Plugins " target="_new" >
					<img src="'. URE_PLUGIN_URL . 'images/admin-menu-editor-pro.jpg' .'" alt="Admin Menu Editor Pro" title="Move, rename, hide, add admin menu items, restrict access"/>
				</a>
			</div>  
			';
		
		return $output;
	}
	// end of admin_menu_editor()
	  			
	
	/**
	 * Output all existed ads slots
	 */
	public function display() {
	
		foreach ($this->slots as $slot) {
			echo $slot."\n";
		}
		
	}
	// end of display()
	
}
// end of ure_Advertisement