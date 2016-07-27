<?php
	/**
	* @package		Any User Twitter Feed
	* @copyright	Web Design Services. All rights reserved. All rights reserved.
	* @license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
	*/

	require_once dirname(__FILE__).'/lib/twitteroauth/twitteroauth.php';
	require_once dirname(__FILE__).'/lib/twitter-text/Autolink.php';
	require_once dirname(__FILE__).'/lib/twitter-text/Extractor.php';
	require_once dirname(__FILE__).'/lib/twitter-text/HitHighlighter.php';

	class AnyUserTwitterFeedHelper
	{
		private $data;
		private $cacheTransient = 'wdscache';

		public function getData($params) {
			$twitterConnection = new TwitterOAuth(
				trim($params->get('consumer_key', '')), // Consumer Key
				trim($params->get('consumer_secret', '')), // Consumer secret
				trim($params->get('access_token', '')), // Access token
				trim($params->get('access_secret', ''))	// Access token secret
			);

			if($params->get('type', 1)) {
				if(trim($params->get('username', '') == ''))
					return false;
				$twitterData = $twitterConnection->get(
					'statuses/user_timeline',
					array(
						'screen_name' => trim($params->get('username', 'twitter')),
						'count' => trim($params->get('count', 5)),
					)
				);
			}
			else {
				if(trim($params->get('query', '')) == '')
					return false;
				$twitterData = $twitterConnection->get(
					'search/tweets',
					array(
						'q' => trim($params->get('query', '')),
						'count' => trim($params->get('count', 5)),
					)
				);
				if(!isset($twitterData->errors))
					$twitterData = $twitterData->statuses;
			}

			// if there are no errors
			if(!isset($twitterData->errors)) {
				$tweets = array();
				foreach($twitterData as $tweet) {
					$tweetDetails = new stdClass();
					$tweetDetails->text = Twitter_Autolink::create($tweet->text)->setNoFollow(false)->addLinks();
					$tweetDetails->time = $this->getTime($tweet->created_at);
					$tweetDetails->id = $tweet->id_str;
					$tweetDetails->screenName = $tweet->user->screen_name;
					$tweetDetails->displayName = $tweet->user->name;
					$tweetDetails->profileImage = $tweet->user->profile_image_url_https;

					$tweets[] = $tweetDetails;
				}
				$data = new stdClass();
				$data->tweets = $tweets;

				$this->data = $data;
                
                if(empty($data->tweets[0]->text))
                    return '';
                    
				$this->setCache();
                
				return $data;
			}
			else {
				return $this->getCache();
			}
		}
		
		public function addStyles($params) {
			$styles = '';
			$border = $params->get('border_color', '#ccc');
			$styles .= '#wds-tweets a {color: ' . $params->get('link_color', '#0084B4') . '}';
			$styles .= '#wds-container {background-color: ' . $params->get('bgd_color', '#fff') . '}';
			$styles .= '#wds-header {border-bottom-color: ' . $border . '}';
			$styles .= '#wds-container {border-color: ' . $border . '}';
			$styles .= '.wds-copyright {border-top-color: ' . $border . '}';
			$styles .= '.wds-tweet-container {border-bottom-color: ' . $border . '}';
			$styles .= '#wds {color: ' . $params->get('text_color', '#333') . '}';
			$styles .= 'a .wds-display-name {color: ' . $params->get('header_link_color', '#333') . '}';
			$styles .= 'a .wds-screen-name {color: ' . $params->get('header_sub_color', '#666') . '}';
			$styles .= 'a:hover .wds-screen-name {color: ' . $params->get('header_sub_hover_color', '#999') . '}';
			$styles .= '#wds-header, #wds-header a {color: ' . $params->get('search_title_color', '#333') . '}';
			if($params->get('width', '')) {
				$styles .= '#wds-container {width: ' . intval($params->get('width', '')) . 'px;}';
			}
			if($params->get('height', '')) {
				$styles .= '#wds {height: ' . intval($params->get('height', '')) . 'px; overflow: auto;}';
			}
			
			wp_register_style('wdstwitterwidget', plugins_url('css/wdstwitterwidget.css', __FILE__));
        	wp_enqueue_style('wdstwitterwidget');
        	wp_add_inline_style('wdstwitterwidget', $styles);

		}

		private function setCache() {
			set_transient($this->cacheTransient, serialize($this->data), 15*MINUTE_IN_SECONDS);
		}

		private function getCache() {
			$cache = get_transient($this->cacheTransient);
			if(!empty($cache))
				return unserialize($cache);
			return false;
		}

		// parse time in a twitter style
		private function getTime($date)
		{
			$timediff = time() - strtotime($date);
			if($timediff < 60)
				return $timediff . 's';
			else if($timediff < 3600)
				return intval(date('i', $timediff)) . 'm';
			else if($timediff < 86400)
				return round($timediff/60/60) . 'h';
			else
				return date_i18n('M d', strtotime($date));
		}
	}


class AnyUserParams {
	private $params;
	public function __construct($params) {
		$this->params = $params;
	}
	
	public function get($param, $default = '') {
		if(isset($this->params[$param]))
			return $this->params[$param];
		return $default;
	}
}
