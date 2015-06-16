<?php
/**
 * URL Class for the WordPress plugin WordPress HTTPS
 * 
 * This class and its properties are heavily based on parse_url()
 *
 * @author Mike Ems
 * @package WordPressHTTPS
 *
 */
class WordPressHTTPS_Url {

	/**
	 * The scheme of a network host; for example, http or https
	 *
	 * @var string
	 */
	protected $_scheme;

	/**
	 * The domain name of a network host, or an IPv4 address as a set of four decimal digit groups separated by literal periods; for example, www.php.net or babelfish.altavista.com
	 *
	 * @var string
	 */
	protected $_host;

	/**
	 * The base domain of a network host; for example, php.net or altavista.com
	 *
	 * @var string
	 */
	protected $_base_host;

	/**
	 * The port being accessed. In the URL http://www.some_host.com:443/, 443 is the port component.
	 *
	 * @var int
	 */
	protected $_port;

	/**
	 * The username being passed for authentication. In the URL ftp://some_user:some_password@ftp.host.com/, some_user would be the user component.
	 *
	 * @var string
	 */
	protected $_user;

	/**
	 * The password being passed for authentication. In the above example, some_password would be the pass component.
	 *
	 * @var string
	 */
	protected $_pass;

	/**
	 * The path component contains the location to the requested resource on the given host. In the URL http://www.foo.com/test/test.php, /test/test.php is the path component.
	 *
	 * @var string
	 */
	protected $_path;

	/**
	 * The filename, if available, is the specified resource being requested. In the URL http://www.foo.com/test/test.jpg, test.jpg is the filename.
	 *
	 * @var string
	 */
	protected $_filename;

	/**
	 * The file extension of the filename, if available. In the URL http://www.foo.com/test/test.jpg, .jpg is the file extension.
	 *
	 * @var string
	 */
	protected $_extension;

	/**
	 * The query string for the request. In the URL http://www.foo.com/?page=bar, page=bar is the query component.
	 *
	 * @var string
	 */
	protected $_query;

	/**
	 * The fragment string for the request. In the URL http://www.foo.com/?page=bar#test, #test is the fragment component.
	 *
	 * @var string
	 */
	protected $_fragment;

	/**
	 * The response body of the request.
	 *
	 * @var string
	 */
	protected $_content;
	
	/**
	 * Set Scheme
	 * 
	 * @param string $scheme
	 * @return object $this
	 */
	public function setScheme( $scheme ) {
		$this->_scheme = $scheme;
		return $this;
	}
	
	/**
	 * Get Scheme
	 * 
	 * @param none
	 * @return string
	 */
	public function getScheme() {
		return $this->_scheme;
	}
	
	/**
	 * Set Host
	 * 
	 * @param string $host
	 * @return object $this
	 */
	public function setHost( $host ) {
		$this->_host = $host;
		return $this;
	}
	
	/**
	 * Get Host
	 * 
	 * @param none
	 * @return string
	 */
	public function getHost() {
		return $this->_host;
	}
	
	/**
	 * Set Base Host
	 * 
	 * @param string $base_host
	 * @return object $this
	 */
	public function setBaseHost( $base_host ) {
		$this->_base_host = $base_host;
		return $this;
	}
	
	/**
	 * Gets the base host of the URL
	 *
	 * @param none
	 * @return string
	 */
	public function getBaseHost() {
		$return_url = clone $this;
		$test_url = clone $this;
		$test_url->setPath('');
		$host_parts = explode('.', $test_url->getHost());
		for ( $i = 0; $i <= sizeof($host_parts); $i++ ) {
			$test_url->setHost( str_replace($host_parts[$i] . '.', '', $test_url->getHost()) );
			if ( $test_url->isValid() ) {
				$return_url = clone $test_url;
			} else {
				break;
			}
		}
		return $return_url->getHost();
	}
	
	/**
	 * Set Port
	 * 
	 * @param string $port
	 * @return object $this
	 */
	public function setPort( $port ) {
		$this->_port = $port;
		return $this;
	}
	
	/**
	 * Get Port
	 * 
	 * @param none
	 * @return string
	 */
	public function getPort() {
		return $this->_port;
	}
	
	/**
	 * Set User
	 * 
	 * @param string $user
	 * @return object $this
	 */
	public function setUser( $user ) {
		$this->_user = $user;
		return $this;
	}
	
	/**
	 * Get User
	 * 
	 * @param none
	 * @return string
	 */
	public function getUser() {
		return $this->_user;
	}
	
	/**
	 * Set Pass
	 * 
	 * @param string $pass
	 * @return object $this
	 */
	public function setPass( $pass ) {
		$this->_pass = $pass;
		return $this;
	}
	
	/**
	 * Get Pass
	 * 
	 * @param none
	 * @return string
	 */
	public function getPass() {
		return $this->_pass;
	}
	
	/**
	 * Set Path
	 * 
	 * Ensures the path begins with a forward slash
	 *
	 * @param none
	 * @return string
	 */
	public function setPath( $path ) {
		$this->_path = ltrim($path, '/');
		$this->_path = '/' . $this->_path;
		$filename = basename($this->_path);
		$pathinfo = pathinfo($filename);
		if ( $pathinfo && isset($pathinfo['extension']) ) {
			$this->setExtension($pathinfo['extension']);
			$this->setFilename($filename);
		}

		return $this;
	}

	/**
	 * Get Path
	 * 
	 * Ensures the path begins with a forward slash
	 *
	 * @param none
	 * @return string
	 */
	public function getPath() {
		return $this->_path;
	}
	
	/**
	 * Set Filename
	 * 
	 * @param string $filename
	 * @return object $this
	 */
	public function setFilename( $filename ) {
		$this->_filename = $filename;
		return $this;
	}
	
	/**
	 * Get Filename
	 * 
	 * @param none
	 * @return string
	 */
	public function getFilename() {
		return $this->_filename;
	}
	
	/**
	 * Set Extension
	 * 
	 * @param string $extension
	 * @return object $this
	 */
	public function setExtension( $extension ) {
		$this->_extension = $extension;
		return $this;
	}
	
	/**
	 * Get Extension
	 * 
	 * @param none
	 * @return string
	 */
	public function getExtension() {
		return $this->_extension;
	}

	/**
	 * Set Query
	 * 
	 * @param string $query
	 * @return object $this
	 */
	public function setQuery( $query ) {
		$this->_query = $query;
		return $this;
	}
	
	/**
	 * Get Query
	 * 
	 * @param none
	 * @return string
	 */
	public function getQuery() {
		return $this->_query;
	}
	
	/**
	 * Set Fragment
	 * 
	 * @param string $fragment
	 * @return object $this
	 */
	public function setFragment( $fragment ) {
		$this->_fragment = $fragment;
		return $this;
	}
	
	/**
	 * Get Fragment
	 * 
	 * @param none
	 * @return string
	 */
	public function getFragment() {
		return $this->_fragment;
	}
	
	/**
	 * Set Content
	 * 
	 * @param string $content
	 * @return object $this
	 */
	public function setContent( $content ) {
		$this->_content = $content;
		return $this;
	}
	
	/**
	 * Get the contents of the URL
	 *
	 * @param boolean $verify_ssl
	 * @return boolean
	 */
	public function getContent( $verify_ssl = false ) {
		if ( $this->_content ) {
			return $this->_content;
		}
		
		$this->_content = false;
		
		if ( function_exists('curl_init') ) {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $this->toString());
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify_ssl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

			$content = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);

			if ( isset($info['http_code']) && !( $info['http_code'] == 0 || $info['http_code'] == 404 ) ) {
				$this->_content = $content;
			}
		}
		
		if ( !$this->_content && @ini_get('allow_url_fopen') ) {
			if ( ($content = @file_get_contents($this->toString())) !== false ) {
				$this->_content = $content;
			}
		}
		return $this->_content;
	}

	/**
	 * Validates the existence of the URL with cURL or file_get_contents()
	 *
	 * @param boolean $verify_ssl
	 * @return boolean
	 */
	public function isValid( $verify_ssl = false ) {
		if ( function_exists('curl_init') ) {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, $this->toString());
			curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER["HTTP_USER_AGENT"]);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verify_ssl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 5);
			@curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');

			$content = curl_exec($ch);
			$info = curl_getinfo($ch);
			curl_close($ch);
			
			if ( !$info['http_code'] || ( $info['http_code'] == 0 || $info['http_code'] == 404 ) ) {
				return false;
			} else {
				return true;
			}
		} else if ( @ini_get('allow_url_fopen') ) {
			if ( @file_get_contents($url) !== false ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Compares URL objects to determine if either of them are a subdomain of the other.
	 * 
	 * @param WordPressHTTPS_Url $url
	 * @return boolean
	 */
	public function isSubdomain( WordPressHTTPS_Url $url ) {
		$this_host = $this->getBaseHost();
		$other_host = $url->getBaseHost();
		if ( $this_host == $other_host ) {
			return true;
		}
		return false;
	}

	/**
	 * Factory object from an array provided by the parse_url function
	 * 
	 * Example of usage:
	 * $site_url = WordPressHTTPS_Url::fromArray( parse_url( site_url() ) );
	 *
	 * @param array $array
	 * @return $url WordPressHTTPS_Url
	 */
	public static function fromArray( $array = array() ) {
		if ( sizeof($array) <= 1 ) {
			return false;
		}

		$url = new WordPressHTTPS_Url;
		$url->setScheme(@$array['scheme']);
		$url->setUser(@$array['user']);
		$url->setPass(@$array['pass']);
		$url->setHost(@$array['host']);
		$url->setPort(@$array['port']);
		$url->setPath(@$array['path']);
		$url->setQuery(@$array['query']);
		$url->setFragment(@$array['fragment']);

		return $url;
	}

	/**
	 * Factory object from a string that contains a URL
	 * 
	 * Example of usage:
	 * $site_url = WordPressHTTPS_Url::fromString( site_url() );
	 *
	 * @param string $string
	 * @return $url WordPressHTTPS_Url
	 */
	public static function fromString( $string ) {
		$url = new WordPressHTTPS_Url;

		@preg_match_all('/((http|https):\/\/[^\'"]+)[\'"\)]?/i', $string, $url_parts);
		if ( isset($url_parts[1][0]) ) {
			if ( $url_parts = parse_url( $url_parts[1][0] ) ) {
				$url->setScheme(@$url_parts['scheme']);
				$url->setUser(@$url_parts['user']);
				$url->setPass(@$url_parts['pass']);
				$url->setHost(@$url_parts['host']);
				$url->setPort(@$url_parts['port']);
				$url->setPath(@$url_parts['path']);
				$url->setQuery(@$url_parts['query']);
				$url->setFragment(@$url_parts['fragment']);
				return $url;
			}
		} else {
			 return false;
		}

		return $url;
	}

	/**
	 * Returns an array of all URL properties
	 *
	 * @param none
	 * @return array parse_url 
	 */
	public function toArray() {
		return parse_url( $this->toString() );	
	}

	/**
	 * Formats the current URL object to a string
	 *
	 * @param none
	 * @return string
	 */
	public function toString() {
		$string = ( $this->getScheme() ? $this->getScheme() . '://' : '' ) . 
		( $this->getUser() ? $this->getUser() . ( $this->getPass() ? ':' . $this->getPass() : '' ) . '@' : '' ) . 
		$this->getHost() .
		( $this->getPort() && ( ( $this->getPort() != 80 && $this->getScheme() == 'http' ) || ( $this->getPort() != 443 && $this->getScheme() == 'https' ) )  ? ':' . $this->getPort() : '' ) . 
		$this->getPath() . 
		( $this->getQuery() ? '?' . $this->getQuery() : '' ) . 
		( $this->getFragment() ? '#' . $this->getFragment() : '' );
	
		return $string;
	}

	/**
	 * Magic __toString method that is called when the object is casted to a string
	 *
	 * @param none
	 * @return string
	 */
	public function __toString() {
		return $this->toString();
	}
	
}