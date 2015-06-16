<?php
/**
 * Base class for a WordPress plugin.
 *
 * @author Mike Ems
 * @package Mvied
 */
class Mvied_Plugin {

	/**
	 * Base directory
	 *
	 * @var string
	 */
	protected $_directory;
	
	/**
	 * Module directory
	 *
	 * @var string
	 */
	protected $_module_directory;

	/**
	 * Loaded Modules
	 *
	 * @var array
	 */
	protected $_modules = array();

	/**
	 * Logger
	 *
	 * @var Mvied_Logger_Interface
	 */
	protected $_logger;
	
	/**
	 * Plugin URL
	 *
	 * @var string
	 */
	protected $_plugin_url;
	
	/**
	 * Plugin Settings
	 *
	 * @var array
	 */
	protected $_settings = array();

	/**
	 * Plugin Slug
	 *
	 * Used as a unqiue identifier for the plugin.
	 *
	 * @var string
	 */
	protected $_slug;
	
	/**
	 * Plugin Version
	 *
	 * @var string
	 */
	protected $_version;
	
	/**
	 * Set Directory
	 * 
	 * @param string $directory
	 * @return object $this
	 */
	public function setDirectory( $directory ) {
		$this->_directory = $directory;
		return $this;
	}
	
	/**
	 * Get Directory
	 * 
	 * @param none
	 * @return string
	 */
	public function getDirectory() {
		return $this->_directory;
	}
	
	/**
	 * Set Module Directory
	 * 
	 * @param string $module_directory
	 * @return object $this
	 */
	public function setModuleDirectory( $module_directory ) {
		$this->_module_directory = $module_directory;
		return $this;
	}
	
	/**
	 * Get Module Directory
	 * 
	 * @param none
	 * @return string
	 */
	public function getModuleDirectory() {
		return $this->_module_directory;
	}
	
	/**
	 * Get Available Modules
	 *
	 * @param none
	 * @return array $modules
	 */
	public function getAvailableModules() {
		$modules = array();
		if ( is_dir($this->getModuleDirectory()) && $module_directory = opendir($this->getModuleDirectory()) ) {
			while ( false !== ($entry = readdir($module_directory)) ) {
				if ( strpos($entry, '.') !== 0 && strpos($entry, '.php') !== false ) {
					$module = str_replace('.php', '', $entry);
					if ( $module != 'Interface' ) {
						$modules[] = $module;
						if ( is_dir($this->getModuleDirectory() . $module) && $sub_module_directory = opendir($this->getModuleDirectory() . $module) ) {
							while ( false !== ($entry = readdir($sub_module_directory)) ) {
								if ( $entry != '.' && $entry != '..' ) {
									$sub_module = str_replace('.php', '', $entry);
									$modules[] = $module . '\\' . $sub_module;
								}
							}
						}
					}
				}
			}
		}
		return $modules;
	}

	/**
	 * Get Module
	 *
	 * @param string $module
	 * @return object
	 */
	public function getModule( $module ) {
		$module = 'Module\\' . $module;
		if ( isset($module) ) {
			if ( isset($this->_modules[$module]) ) {
				return $this->_modules[$module];
			}
		}
		
		die('Module not found: \'' . $module . '\'.');
	}

	/**
	 * Get Modules
	 * 
	 * Returns an array of all loaded modules
	 *
	 * @param none
	 * @return array $modules
	 */
	public function getModules() {
		$modules = array();
		if ( isset($this->_modules) ) {
			$modules = $this->_modules;
		}
		return $modules;
	}
	
	/**
	 * Set Module
	 *
	 * @param string $module
	 * @param object $object
	 * @return $this
	 */
	public function setModule( $module, $object ) {
		$this->_modules[$module] = $object;
		return $this;
	}

	/**
	 * Set Logger
	 * 
	 * @param object $logger
	 * @return object $this
	 */
	public function setLogger( Mvied_Logger_Interface $logger ) {
		$this->_logger = $logger;	
		return $this;
	}
	
	/**
	 * Get Logger
	 * 
	 * @param none
	 * @return object
	 */
	public function getLogger() {
		if ( ! isset($this->_logger) ) {
			die(__CLASS__ . ' missing Logger dependency.');
		}
		
		return $this->_logger->getInstance();
	}
	
	/**
	 * Set Plugin Url
	 * 
	 * @param string $plugin_url
	 * @return object $this
	 */
	public function setPluginUrl( $plugin_url ) {
		$this->_plugin_url = $plugin_url;
		return $this;
	}
	
	/**
	 * Get Plugin Url
	 * 
	 * @param none
	 * @return string
	 */
	public function getPluginUrl() {
		return $this->_plugin_url;
	}
	
	/**
	 * Get Plugin Setting
	 *
	 * @param string $setting
	 * @param int $setting_blog_id
	 * @return mixed
	 */
	public function getSetting( $setting, $blog_id = 0 ) {
		$setting_full = $this->getSlug() . '_' . $setting;
		if ( $blog_id > 0 ) {
			$value = get_blog_option($blog_id, $setting_full);
		} else {
			$value = get_option($setting_full);
		}

		// Load default option
		if ( $value === false && array_key_exists($setting, $this->_settings) ) {
			$value = $this->_settings[$setting];
		}
		// Convert 1's and 0's to boolean
		switch( $value ) {
			case "1":
				$value = true;
			break;
			case "0":
				$value = false;
			break;
		}
		return $value;
	}

	/**
	 * Get Plugin Settings
	 *
	 * @param none
	 * @return array
	 */
	public function getSettings() {
		return $this->_settings;
	}

	/**
	 * Set Plugin Setting
	 *
	 * @param string $setting
	 * @param mixed $value
	 * @param int $blog_id
	 * @return $this
	 */
	public function setSetting( $setting, $value, $blog_id = 0 ) {
		$setting_full = $this->getSlug() . '_' . $setting;
		if ( $blog_id > 0 ) {
			update_blog_option($blog_id, $setting_full, $value);
		} else {
			update_option($setting_full, $value);
		}
		return $this;
	}

	/**
	 * Set Slug
	 * 
	 * @param string $slug
	 * @return object $this
	 */
	public function setSlug( $slug ) {
		$this->_slug = $slug;
		return $this;
	}
	
	/**
	 * Get Slug
	 * 
	 * @param none
	 * @return string
	 */
	public function getSlug() {
		return $this->_slug;
	}
	
	/**
	 * Set Version
	 * 
	 * @param string $version
	 * @return object $this
	 */
	public function setVersion( $version ) {
		$this->_version = $version;
		return $this;
	}
	
	/**
	 * Get Version
	 * 
	 * @param none
	 * @return string
	 */
	public function getVersion() {
		return $this->_version;
	}
	
	/**
	 * Init
	 * 
	 * Initializes all of the modules.
	 *
	 * @param none
	 * @return $this
	 */
	public function init() {
		$modules = $this->getModules();
		foreach( $modules as $module ) {
			$module->init();
		}
		if ( isset($this->_slug) ) {
			do_action($this->_slug . '_init');
		}
		return $this;
	}

	/**
	 * Is Module Loaded?
	 *
	 * @param string $module
	 * @return boolean
	 */
	public function isModuleLoaded( $module ) {
		if ( is_object($this->getModule($module)) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Load Module
	 *
	 * @param string $module
	 * @return $this
	 */
	public function loadModule( $module ) {
		if ( strpos(get_class($this), '_') !== false ) {
			$base_class = substr(get_class($this), 0, strpos(get_class($this), '_'));
		} else {
			$base_class = get_class($this);
		}
		$module_full = 'Module\\' . $module;
		$filename = str_replace('\\', '/', $module);
		$filename = $filename . '.php';
		
		require_once($this->getModuleDirectory() . $filename);

		$class = $base_class . '_' . str_replace('\\', '_', $module_full);
		if ( ! isset($this->_modules[$class]) || ! is_object($this->_modules[$class]) || get_class($this->_modules[$class]) != $class ) {
			try {
				$object = new $class;
				$this->setModule($module_full, $object);
				$this->getModule($module)->setPlugin($this);
			} catch ( Exception $e ) {
				die('Unable to load module: \'' . $module . '\'. ' . $e->getMessage());
			}
		}

		return $this;
	}
	
	/**
	 * Load Modules
	 * 
	 * Load specified modules. If no modules are specified, all modules are loaded.
	 *
	 * @param array $modules
	 * @return $this
	 */
	public function loadModules( $modules = array() ) {
		if ( sizeof($modules) == 0 ) {
			$modules = $this->getAvailableModules();
		}

		foreach( $modules as $module ) {
			$this->loadModule( $module );
		}
		return $this;
	}

	/**
	 * Unload Module
	 *
	 * @param string $module
	 * @return $this
	 */
	public function unloadModule( $module ) {
		if ( strpos(get_class($this), '_') !== false ) {
			$base_class = substr(get_class($this), 0, strpos(get_class($this), '_'));
		} else {
			$base_class = get_class($this);
		}
		$module = 'Module\\' . $module;

		$modules = $this->getModules();
		
		unset($modules[$module]);
		
		$this->_modules = $modules;

		return $this;
	}

}