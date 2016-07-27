<?php
/**
 * @author     Mike Cochrane <mikec@mikenz.geek.nz>
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Mike Cochrane, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 * @package    Twitter
 */

require_once 'Regex.php';

/**
 * Twitter Extractor Class
 *
 * Parses tweets and extracts URLs, usernames, username/list pairs and
 * hashtags.
 *
 * Originally written by {@link http://github.com/mikenz Mike Cochrane}, this
 * is based on code by {@link http://github.com/mzsanford Matt Sanford} and
 * heavily modified by {@link http://github.com/ngnpope Nick Pope}.
 *
 * @author     Mike Cochrane <mikec@mikenz.geek.nz>
 * @author     Nick Pope <nick@nickpope.me.uk>
 * @copyright  Copyright © 2010, Mike Cochrane, Nick Pope
 * @license    http://www.apache.org/licenses/LICENSE-2.0  Apache License v2.0
 * @package    Twitter
 */
class Twitter_Extractor extends Twitter_Regex {

  /**
   * Provides fluent method chaining.
   *
   * @param  string  $tweet        The tweet to be converted.
   *
   * @see  __construct()
   *
   * @return  Twitter_Extractor
   */
  public static function create($tweet) {
    return new self($tweet);
  }

  /**
   * Reads in a tweet to be parsed and extracts elements from it.
   *
   * Extracts various parts of a tweet including URLs, usernames, hashtags...
   *
   * @param  string  $tweet  The tweet to extract.
   */
  public function __construct($tweet) {
    parent::__construct($tweet);
  }

  /**
   * Extracts all parts of a tweet and returns an associative array containing
   * the extracted elements.
   *
   * @return  array  The elements in the tweet.
   */
  public function extract() {
    return array(
      'hashtags' => $this->extractHashtags(),
      'urls'     => $this->extractURLs(),
      'mentions' => $this->extractMentionedUsernames(),
      'replyto'  => $this->extractRepliedUsernames(),
      'hashtags_with_indices' => $this->extractHashtagsWithIndices(),
      'urls_with_indices'     => $this->extractURLsWithIndices(),
      'mentions_with_indices' => $this->extractMentionedUsernamesWithIndices(),
    );
  }

  /**
   * Extracts all the hashtags from the tweet.
   *
   * @return  array  The hashtag elements in the tweet.
   */
  public function extractHashtags() {
    preg_match_all(self::REGEX_HASHTAG, $this->tweet, $matches);
    return $matches[3];
  }

  /**
   * Extracts all the URLs from the tweet.
   *
   * @return  array  The URL elements in the tweet.
   */
  public function extractURLs() {
    preg_match_all(self::$REGEX_VALID_URL, $this->tweet, $matches);
    list($all, $before, $url, $protocol, $domain, $path, $query) = array_pad($matches, 7, '');
    return $url;
  }

  /**
   * Extract all the usernames from the tweet.
   *
   * A mention is an occurrence of a username anywhere in a tweet.
   *
   * @return  array  The usernames elements in the tweet.
   */
  public function extractMentionedUsernames() {
    preg_match_all(self::REGEX_USERNAME_MENTION, $this->tweet, $matches);
    list($all, $before, $username, $after) = array_pad($matches, 4, '');
    $usernames = array();
    for ($i = 0; $i < count($username); $i ++) {
      # If $after is not empty, there is an invalid character.
      if (!empty($after[$i])) continue;
      array_push($usernames, $username[$i]);
    }
    return $usernames;
  }

  /**
   * Extract all the usernames replied to from the tweet.
   *
   * A reply is an occurrence of a username at the beginning of a tweet.
   *
   * @return  array  The usernames replied to in a tweet.
   */
  public function extractRepliedUsernames() {
    preg_match(self::$REGEX_REPLY_USERNAME, $this->tweet, $matches);
    return isset($matches[2]) ? $matches[2] : '';
  }

  /**
   * Extracts all the hashtags and the indices they occur at from the tweet.
   *
   * @return  array  The hashtag elements in the tweet.
   */
  public function extractHashtagsWithIndices() {
    preg_match_all(self::REGEX_HASHTAG, $this->tweet, $matches, PREG_OFFSET_CAPTURE);
    $m = &$matches[3];
    for ($i = 0; $i < count($m); $i++) {
      $m[$i] = array_combine(array('hashtag', 'indices'), $m[$i]);
      # XXX: Fix for PREG_OFFSET_CAPTURE returning byte offsets...
      $start = mb_strlen(substr($this->tweet, 0, $matches[1][$i][1]));
      $start += mb_strlen($matches[1][$i][0]);
      $length = mb_strlen($m[$i]['hashtag']);
      $m[$i]['indices'] = array($start, $start + $length + 1);
    }
    return $m;
  }

  /**
   * Extracts all the URLs and the indices they occur at from the tweet.
   *
   * @return  array  The URLs elements in the tweet.
   */
  public function extractURLsWithIndices() {
    preg_match_all(self::$REGEX_VALID_URL, $this->tweet, $matches, PREG_OFFSET_CAPTURE);
    $m = &$matches[2];
    for ($i = 0; $i < count($m); $i++) {
      $m[$i] = array_combine(array('url', 'indices'), $m[$i]);
      # XXX: Fix for PREG_OFFSET_CAPTURE returning byte offsets...
      $start = mb_strlen(substr($this->tweet, 0, $matches[1][$i][1]));
      $start += mb_strlen($matches[1][$i][0]);
      $length = mb_strlen($m[$i]['url']);
      $m[$i]['indices'] = array($start, $start + $length);
    }
    return $m;
  }

  /**
   * Extracts all the usernames and the indices they occur at from the tweet.
   *
   * @return  array  The username elements in the tweet.
   */
  public function extractMentionedUsernamesWithIndices() {
    preg_match_all(self::REGEX_USERNAME_MENTION, $this->tweet, $matches, PREG_OFFSET_CAPTURE);
    $m = &$matches[2];
    for ($i = 0; $i < count($m); $i++) {
      $m[$i] = array_combine(array('screen_name', 'indices'), $m[$i]);
      # XXX: Fix for PREG_OFFSET_CAPTURE returning byte offsets...
      $start = mb_strlen(substr($this->tweet, 0, $matches[1][$i][1]));
      $start += mb_strlen($matches[1][$i][0]);
      $length = mb_strlen($m[$i]['screen_name']);
      $m[$i]['indices'] = array($start, $start + $length + 1);
    }
    return $m;
  }

}
