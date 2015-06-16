<?php 
/**
 * Logger Interface
 *
 * @author Mike Ems
 * @package Mvied
 *
 */

interface Mvied_Logger_Interface {

	/**
	 * Get singleton instance
	 *
	 * @param none
	 * @return object
	 */
	public static function getInstance();

	/**
	 * Get Log
	 *
	 * @param none
	 * @return array
	 */
	public function getLog();
	
	/**
	 * Adds a string to an array of log entries
	 *
	 * @param string $string
	 * @return $this
	 */
	public function log( $string );
	
}