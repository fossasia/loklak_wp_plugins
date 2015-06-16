<?php
/**
 * @package PostgreSQL_For_Wordpress
 * @version $Id$
 * @author	Hawk__, www.hawkix.net
 */

/**
* Provides a driver for MySQL
* This file remaps all wpsql_* calls to mysql_* original name
*/
	function wpsql_num_rows($result)
		{ return mysql_num_rows($result); }
	function wpsql_numrows($result)
		{ return mysql_num_rows($result); }
	function wpsql_num_fields($result)
		{ return mysql_num_fields($result); }
	function wpsql_fetch_field($result)
		{ return mysql_fetch_field($result); }
	function wpsql_fetch_object($result)
		{ return mysql_fetch_object($result); }
	function wpsql_free_result($result)
		{ return mysql_free_result($result); }
	function wpsql_affected_rows()
		{ return mysql_affected_rows(); }
	function wpsql_fetch_row($result)
		{ return mysql_fetch_row($result); }
	function wpsql_data_seek($result, $offset)
		{ return mysql_data_seek( $result, $offset ); }
	function wpsql_error()
		{ return mysql_error();}
	function wpsql_fetch_assoc($result)
		{ return mysql_fetch_assoc($result); }
	function wpsql_escape_string($s)
		{ return mysql_real_escape_string($s); }
	function wpsql_real_escape_string($s,$c=NULL)
		{ return mysql_real_escape_string($s,$c); }
	function wpsql_get_server_info()
		{ return mysql_get_server_info(); }
	function wpsql_result($result, $i, $fieldname)
		{ return mysql_result($result, $i, $fieldname); }
	function wpsql_connect($dbserver, $dbuser, $dbpass)
		{ return mysql_connect($dbserver, $dbuser, $dbpass); }
	function wpsql_fetch_array($result)
		{ return mysql_fetch_array($result); }
	function wpsql_select_db($dbname, $connection_id)
		{ return mysql_select_db($dbname, $connection_id); }
	function wpsql_query($sql)
		{ return mysql_query($sql); }
	function wpsql_insert_id($table)
		{ return mysql_insert_id($table); }
