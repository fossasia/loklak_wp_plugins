<?php 
/**
 * Plugin Module Interface
 *
 * @author Mike Ems
 * @package Mvied
 *
 */

interface Mvied_Plugin_Module_Interface {

	/**
	 * Initializes the module
	 *
	 * @param none
	 * @return void
	 */
	public function init();

	/**
	 * Set Plugin
	 * 
	 * @param Mvied_Plugin $plugin
	 * @return Mvied_Plugin_Module
	 * @uses Mvied_Plugin
	 */
	public function setPlugin( Mvied_Plugin $plugin );

	/**
	 * Get Plugin
	 * 
	 * @param none
	 * @return Mvied_Plugin
	 */
	public function getPlugin();
}