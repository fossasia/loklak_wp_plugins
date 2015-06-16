<?php
/**
 * HTML Parser Module
 *
 * @author Mike Ems
 * @package WordPressHTTPS
 *
 */

class WordPressHTTPS_Module_Parser extends Mvied_Plugin_Module {

	/**
	 * HTML
	 *
	 * @var string
	 */
	protected $_html;

	/**
	 * Initialize
	 *
	 * @param none
	 * @return void
	 */
	public function init() {
		// Start output buffering
		add_action('init', array(&$this, 'startOutputBuffering'));
	}

	/**
	 * Parse HTML
	 * 
	 * Parses the output buffer to fix HTML output
	 *
	 * @param string $buffer
	 * @return string $this->_html
	 */
	public function parseHtml( $buffer ) {
		$this->_html = $buffer;

		$this->normalizeElements();
		$this->fixLinksAndForms();
		$this->fixExtensions();
		$this->fixElements();
		$this->fixCssElements();
		$this->fixRelativeElements();

		// Output logger contents to browsers console if in Debug Mode
		if ( $this->getPlugin()->getSetting('debug') == true ) {
			$this->consoleLog();
		}

		return $this->_html;
	}

	/**
	 * Start output buffering
	 *
	 * @param none
	 * @return void
	 */
	public function startOutputBuffering() {
		ob_start(array(&$this, 'parseHtml'));
	}

	/**
	 * Secure element
	 *
	 * @param string $url
	 * @param string $type
	 * @return boolean
	 */
	public function secureElement( $url, $type = '' ) {
		$updated = false;
		$result = false;
		$upload_dir = wp_upload_dir();
		$upload_path = str_replace($this->getPlugin()->getHttpsUrl()->getPath(), $this->getPlugin()->getHttpUrl()->getPath(), parse_url($upload_dir['baseurl'], PHP_URL_PATH));

		if ( ! is_admin() || ( is_admin() && strpos($url, $upload_path) === false ) ) {
			$updated = $this->getPlugin()->makeUrlHttps($url);
			if ( $url != $updated ) {
				$this->_html = str_replace($url, $updated, $this->_html);
			} else {
				$updated = false;
			}
		}

		// Add log entry if this change hasn't been logged
		if ( $updated ) {
			$log = '[FIXED] Element: ' . ( $type != '' ? '<' . $type . '> ' : '' ) . $url . ' => ' . $updated;
			$result = true;
		} else if ( strpos($url, 'http://') === 0 ) {
			if ( $this->getPlugin()->getSetting('remove_unsecure') ) {
				$log = '[FIXED] Removed Unsecure Element: <' . $type . '> - ' . $url;
			} else {
				$log = '[WARNING] Unsecure Element: <' . $type . '> - ' . $url;
			}
		}
		if ( isset($log) && ! in_array($log, $this->getPlugin()->getLogger()->getLog()) ) {
			$this->getPlugin()->getLogger()->log($log);
		}

		return $result;
	}

	/**
	 * Unsecure element
	 *
	 * @param string $url
	 * @param string $type
	 * @return boolean
	 */
	public function unsecureElement( $url, $type = '' ) {
		$updated = false;
		$result = false;
		$upload_dir = wp_upload_dir();
		$upload_path = str_replace($this->getPlugin()->getHttpsUrl()->getPath(), $this->getPlugin()->getHttpUrl()->getPath(), parse_url($upload_dir['baseurl'], PHP_URL_PATH));

		if ( ! is_admin() || ( is_admin() && strpos($url, $upload_path) === false ) ) {
			$updated = $this->getPlugin()->makeUrlHttp($url);
			$this->_html = str_replace($url, $updated, $this->_html);
		}

		// Add log entry if this change hasn't been logged
		if ( $updated && $url != $updated ) {
			$log = '[FIXED] Element: ' . ( $type != '' ? '<' . $type . '> ' : '' ) . $url . ' => ' . $updated;
			$result = true;
		}
		if ( isset($log) && ! in_array($log, $this->getPlugin()->getLogger()->getLog()) ) {
			$this->getPlugin()->getLogger()->log($log);
		}

		return $result;
	}

	/**
	 * Normalize all local URL's to HTTP
	 *
	 * @param none
	 * @return void
	 */
	public function normalizeElements() {
		$httpMatches = array();
		$httpsMatches = array();
		if ( $this->getPlugin()->getSetting('ssl_host_diff') && !is_admin() ) {
			$url = clone $this->getPlugin()->getHttpsUrl();
			$url->setScheme('http');
			preg_match_all('/(' . str_replace('/', '\/', preg_quote($url->toString())) . '[^\'"]*)[\'"]?/im', $this->_html, $httpsMatches);

			$url = clone $this->getPlugin()->getHttpUrl();
			$url->setScheme('https');
			preg_match_all('/(' . str_replace('/', '\/', preg_quote($url->toString())) . '[^\'"]*)[\'"]?/im', $this->_html, $httpMatches);

			$matches = array_merge($httpMatches, $httpsMatches);
			for ($i = 0; $i < sizeof($matches[0]); $i++) {
				if ( isset($matches[1][$i]) ) {
					$url_parts = parse_url($matches[1][$i]);
					if ( $url_parts && strpos($url_parts['path'], 'wp-admin') === false && strpos($url_parts['path'], 'wp-login') === false ) {
						$this->_html = str_replace($url, $this->getPlugin()->makeUrlHttp($url), $this->_html);
					}
				}
			}
		}
	}

	/**
	 * Fixes schemes on DOM elements.
	 *
	 * @param none
	 * @return void
	 */
	public function fixElements() {
		if ( is_admin() ) {
			preg_match_all('/\<(script|link|img)[^>]+[\'"]((http|https):\/\/[^\'"]+)[\'"][^>]*>(<\/(script|link|img|input|embed|param|iframe)>\s*)?/im', $this->_html, $matches);
		} else {
			preg_match_all('/\<(script|link|img|input|embed|param|iframe)[^>]+[\'"]((http|https):\/\/[^\'"]+)[\'"][^>]*>(<\/(script|link|img|input|embed|param|iframe)>\s*)?/im', $this->_html, $matches);
		}

		for ($i = 0; $i < sizeof($matches[0]); $i++) {
			$html = $matches[0][$i];
			$type = $matches[1][$i];
			$url = $matches[2][$i];
			$scheme = $matches[3][$i];
			$updated = false;

			if	( $type == 'img' || $type == 'script' || $type == 'embed' || $type == 'iframe' ||
				( $type == 'link' && ( strpos($html, 'stylesheet') !== false || strpos($html, 'pingback') !== false ) ) ||
				( $type == 'form' && strpos($html, 'wp-pass.php') !== false ) ||
				( $type == 'form' && strpos($html, 'wp-login.php?action=postpass') !== false ) ||
				( $type == 'form' && strpos($html, 'commentform') !== false ) ||
				( $type == 'input' && strpos($html, 'image') !== false ) ||
				( $type == 'param' && strpos($html, 'movie') !== false )
			) {
				if ( $this->getPlugin()->isSsl() && ( $this->getPlugin()->getSetting('ssl_host_diff') || ( !$this->getPlugin()->getSetting('ssl_host_diff') && strpos($url, 'http://') === 0 ) ) ) {
					if ( !$this->secureElement($url, $type) && $this->getPlugin()->getSetting('remove_unsecure') ) {
						$this->_html = str_replace($html, '', $this->_html);
					}
				} else if ( !$this->getPlugin()->isSsl() && strpos($url, 'https://') === 0 ) {
					$this->unsecureElement($url, $type);
				}
			}
		}
	}

	/**
	 * Fix CSS background images or imports.
	 *
	 * @param none
	 * @return void
	 */
	public function fixCssElements() {
		preg_match_all('/(import|background)[:]?[^u]*url\([\'"]?(http:\/\/[^\'"\)]+)[\'"\)]?\)/im', $this->_html, $matches);
		for ($i = 0; $i < sizeof($matches[0]); $i++) {
			$css = $matches[0][$i];
			$url = $matches[2][$i];
			if ( $this->getPlugin()->isSsl() && ( $this->getPlugin()->getSetting('ssl_host_diff') || ( !$this->getPlugin()->getSetting('ssl_host_diff') && strpos($url, 'http://') === 0 ) ) ) {
				$this->secureElement($url, 'style');
			} else if ( !$this->getPlugin()->isSsl() && strpos($url, 'https://') === 0 ) {
				$this->unsecureElement($url, 'style');
			}
		}
	}

	/**
	 * Fix elements that are being referenced relatively.
	 *
	 * @param none
	 * @return void
	 */
	public function fixRelativeElements() {
		if ( $this->getPlugin()->isSsl() && $this->getPlugin()->getHttpUrl()->getPath() != $this->getPlugin()->getHttpsUrl()->getPath() ) {
			preg_match_all('/\<(script|link|img|input|form|embed|param)[^>]+(src|href|action|data|movie|image|value)=[\'"](\/[^\'"]*)[\'"][^>]*>/im', $this->_html, $matches);

			for ($i = 0; $i < sizeof($matches[0]); $i++) {
				$html = $matches[0][$i];
				$type = $matches[1][$i];
				$attr = $matches[2][$i];
				$url_path = $matches[3][$i];
				if (
					$type != 'input' ||
					( $type == 'input' && $attr == 'image' ) ||
					( $type == 'input' && strpos($html, '_wp_http_referer') !== false )
				) {
					if ( strpos($url_path, '//') !== 0 ) {
						$updated = clone $this->getPlugin()->getHttpsUrl();
						$updated->setPath($url_path);
						$this->_html = str_replace($html, str_replace($url_path, $updated, $html), $this->_html);
						$this->getPlugin()->getLogger()->log('[FIXED] Element: <' . $type . '> - ' . $url_path . ' => ' . $updated);
					}
				}
			}
		}
	}

	/**
	 * Fixes schemes on DOM elements with extensions specified in $this->_extensions
	 *
	 * @param none
	 * @return void
	 */
	public function fixExtensions() {
		@preg_match_all('/((http|https):\/\/[^\'"\)\s]+)[\'"\)]?/i', $this->_html, $matches);
		for ($i = 0; $i < sizeof($matches[1]); $i++) {
			$url = $matches[1][$i];
			$filename = basename($url);
			$scheme = $matches[2][$i];

			foreach( $this->getPlugin()->getFileExtensions() as $extension ) {
				if ( $extension == 'js' ) {
					$type = 'script';
				} else if ( $extension == 'css' ) {
					$type = 'style';
				} else if ( in_array($extension, array('jpg', 'jpeg', 'png', 'gif')) ) {
					$type = 'img';
				} else {
					continue;
				}

				if ( preg_match('/\.' . $extension . '(\?|$)/', $filename) ) {
					if ( $this->getPlugin()->isSsl() && ( $this->getPlugin()->getSetting('ssl_host_diff') || ( !$this->getPlugin()->getSetting('ssl_host_diff') && strpos($url, 'http://') === 0 ) ) ) {
						$this->secureElement($url, $type);
					} else if ( !$this->getPlugin()->isSsl() && strpos($url, 'https://') === 0 ) {
						$this->unsecureElement($url, $type);
					}
				}
			}
		}
	}

	/**
	 * Fix links and forms
	 *
	 * @param none
	 * @return void
	 */
	public function fixLinksAndForms() {
		global $wpdb;
		// Update anchor and form tags to appropriate URL's
		preg_match_all('/\<(a|form)[^>]+[\'"]((http|https):\/\/[^\'"]+)[\'"][^>]*>/im', $this->_html, $matches);

		for ($i = 0; $i < sizeof($matches[0]); $i++) {
			$html = $matches[0][$i];
			$type = $matches[1][$i];
			$url = $matches[2][$i];
			$scheme = $matches[3][$i];
			$updated = false;
			$post_id = null;
			$blog_id = null;
			$force_ssl = null;
			$url_path = '/';

			if ( !$this->getPlugin()->isUrlLocal($url) ) {
				continue;
			}

			if ( $url != '' && ($url_parts = parse_url($url)) && isset($url_parts['path']) ) {
				if ( $this->getPlugin()->getHttpsUrl()->getPath() != '/' ) {
					if ( $this->getPlugin()->getSetting('ssl_host_diff') ) {
						$url_parts['path'] = str_replace($this->getPlugin()->getHttpsUrl()->getPath(), '', $url_parts['path']);
					}
					if ( $this->getPlugin()->getHttpUrl()->getPath() != '/' ) {
						$url_parts['path'] = str_replace($this->getPlugin()->getHttpUrl()->getPath(), '', $url_parts['path']);
					}
				}

				// qTranslate integration - strips language from beginning of url path
				if ( defined('QTRANS_INIT') && constant('QTRANS_INIT') == true ) {
					global $q_config;
					if ( isset($q_config['enabled_languages']) ) {
						foreach($q_config['enabled_languages'] as $language) {
							$url_parts['path'] = preg_replace('/^\/' . $language . '\//', '/', $url_parts['path']);
						}
					}
				}

				if ( preg_match("/page_id=([\d]+)/", parse_url($url, PHP_URL_QUERY), $postID) ) {
					$post_id = $postID[1];
				} else if ( isset($url_parts['path']) && ( $url_parts['path'] == '' || $url_parts['path'] == '/' ) ) {
					if ( get_option('show_on_front') == 'page' ) {
						$post_id = get_option('page_on_front');
					}
				} else if ( isset($url_parts['path']) && ($post = get_page_by_path($url_parts['path'])) ) {
					$post_id = $post->ID;
				}

				if ( is_multisite() && isset($url_parts['host']) ) {
					if ( is_subdomain_install() ) {
						$blog_id = get_blog_id_from_url( $url_parts['host'], '/');
					} else {
						$url_path_segments = explode('/', $url_parts['path']);
						if ( sizeof($url_path_segments) > 1 ) {
							foreach( $url_path_segments as $url_path_segment ) {
								if ( is_null($blog_id) && $url_path_segment != '' ) {
									$url_path .= $url_path_segment . '/';
									if ( ($blog_id = get_blog_id_from_url( $url_parts['host'], $url_path)) > 0 ) {
										break;
									} else {
										$blog_id = null;
									}
								}
							}
						}
					}

					if ( !is_null($blog_id) && $blog_id != $wpdb->blogid ) {
						// URL Filters
						if ( sizeof((array)$this->getPlugin()->getSetting('secure_filter', $blog_id)) > 0 ) {
							foreach( $this->getPlugin()->getSetting('secure_filter', $blog_id) as $filter ) {
								if ( preg_match('/' . str_replace('/', '\/', $filter) . '/', $url) === 1 ) {
									$force_ssl = true;
								}
							}
						}
						if ( ( $this->getPlugin()->getSetting('ssl_admin', $blog_id) || defined('FORCE_SSL_ADMIN') && constant('FORCE_SSL_ADMIN') ) && strpos($url_parts['path'], 'wp-admin') !== false && ( ! $this->getPlugin()->getSetting('ssl_host_diff', $blog_id) || ( $this->getPlugin()->getSetting('ssl_host_diff', $blog_id) && function_exists('is_user_logged_in') && is_user_logged_in() ) ) ) {
							$force_ssl = true;
						} else if ( is_null($force_ssl) && $this->getPlugin()->getSetting('exclusive_https', $blog_id) ) {
							$force_ssl = false;
						} else if ( strpos($url, 'https://') === 0 ) {
							$force_ssl = true;
						}
					}
				}
			}

			// Only apply force_ssl filters for current blog
			if ( is_null($blog_id) ) {
				$force_ssl = apply_filters('force_ssl', null, ( isset($post_id) ? $post_id : 0 ), $url );
			}

			if ( $force_ssl == true ) {
				if ( is_null($blog_id) ) {
					$updated = $this->getPlugin()->makeUrlHttps($url);
				} else {
					if ( $this->getPlugin()->getSetting('ssl_host', $blog_id) ) {
						$ssl_host = $this->getPlugin()->getSetting('ssl_host', $blog_id);
					} else {
						$ssl_host = parse_url(get_home_url($blog_id, '/'), PHP_URL_HOST);
					}
					if ( is_subdomain_install() ) {
						$host = $url_parts['host'] . '/';
					} else {
						$host = $url_parts['host'] . '/' . $url_path;
					}
					$updated = str_replace($url_parts['scheme'] . '://' . $host, $ssl_host, $url);
				}
				$this->_html = str_replace($html, str_replace($url, $updated, $html), $this->_html);
			} else if ( !is_null($force_ssl) && !$force_ssl ) {
				if ( is_null($blog_id) ) {
					$updated = $this->getPlugin()->makeUrlHttp($url);
				} else {
					if ( is_subdomain_install() ) {
						$host = $url_parts['host'] . '/';
					} else {
						$host = $url_parts['host'] . '/' . $url_path;
					}
					$updated = str_replace($url_parts['scheme'] . '://' . $host, get_home_url($blog_id, '/'), $url);
				}
				$this->_html = str_replace($html, str_replace($url, $updated, $html), $this->_html);
			}

			// Add log entry if this change hasn't been logged
			if ( $updated && $url != $updated ) {
				$log = '[FIXED] Element: <' . $type . '> - ' . $url . ' => ' . $updated;
				if ( ! in_array($log, $this->getPlugin()->getLogger()->getLog()) ) {
					$this->getPlugin()->getLogger()->log($log);
				}
			}
		}
	}

	/**
	 * Output contents of the log to the browser's console.
	 *
	 * @param none
	 * @return void
	 */
	public function consoleLog() {
		$this->_html = str_replace('</body>', $this->getPlugin()->getLogger()->consoleLog() . "\n\n</body>", $this->_html);
	}

}