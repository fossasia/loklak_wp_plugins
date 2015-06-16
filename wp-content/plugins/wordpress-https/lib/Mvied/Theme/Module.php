<?php
/**
 * Theme Module
 * 
 * Each Module in the project will extend this base Module class.
 * Modules can be treated as independent plugins. Think of them as sub-plugins.
 * 
 * @author Mike Ems
 * @package Mvied
 */
class Mvied_Theme_Module implements Mvied_Theme_Module_Interface {

	/**
	 * Theme object that this module extends
	 *
	 * @var Mvied_Theme
	 */
	protected $_theme;

	/**
	 * 
	 * Initializes the module
	 * @param none
	 * @return void
	 */
	public function init() {
		throw new Exception('No init method in ' . get_class($this));
	}

	/**
	 * Set Theme
	 * 
	 * @param Mvied_Theme $theme
	 * @return object $this
	 * @uses Mvied_Theme
	 */
	public function setTheme( Mvied_Theme $theme ) {
		$this->_theme = $theme;
		return $this;
	}

	/**
	 * Get Theme
	 * 
	 * @param none
	 * @return Mvied_Theme
	 */
	public function getTheme() {
		if ( ! isset($this->_theme) ) {
			die('Module ' . __CLASS__ . ' missing Theme dependency.');
		}

		return $this->_theme;
	}

}