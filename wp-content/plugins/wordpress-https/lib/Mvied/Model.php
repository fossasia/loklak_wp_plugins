<?php
/**
 * Model class for a WordPress theme or plugin.
 *
 * @author Mike Ems
 * @package Mvied
 */
class Mvied_Model {

	protected $_post;

	public $ID;

	public $name;

	public function __construct( $id ) {
		if ( ! isset($id) ) {
			return $this;
		}

		$this->_post = get_post($id);
		$this->ID = $this->_post->ID;
		$this->name = $this->_post->post_title;

		$reflect = new ReflectionClass($this);
		$properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach($properties as $property) {
			$property = $property->getName();
			if ( !isset($this->$property) ) {
				$this->$property = get_post_meta($this->ID, $property, true);
			}
		}
	}

	public function __get( $property ) {
		return get_post_meta($this->ID, $property, true);
	}

	public function getPost() {
		return $this->_post;
	}

	public function load( $array = array() ) {
		foreach($array as $key => $value) {
			if ( property_exists($this, $key) ) {
				$this->$key = $value;
			}
		}
	}

	public function save() {
		$reflect = new ReflectionClass($this);
		$properties = $reflect->getProperties(ReflectionProperty::IS_PUBLIC);
		foreach($properties as $property) {
			$property = $property->getName();
			if ( strpos($property, '_') !== 0 ) {
				update_post_meta($this->_post->ID, $property, $this->$property);
			}
		}
	}

}