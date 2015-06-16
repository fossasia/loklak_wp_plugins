<?php
/**
 * @package PostgreSQL_For_Wordpress
 * @version $Id$
 * @author	Hawk__, www.hawkix.net
 */

/**
* This file registers functions used only when installing or upgrading WordPress
*/

	// List of types translations (the key is the mysql one, the value is the text to use instead)
	$GLOBALS['pg4wp_ttr'] = array(
		'bigint(20)'	=> 'bigint',
		'bigint(10)'	=> 'int',
		'int(11)'		=> 'int',
		'tinytext'		=> 'text',
		'mediumtext'	=> 'text',
		'longtext'		=> 'text',
		'unsigned'		=> '',
		'gmt datetime NOT NULL default \'0000-00-00 00:00:00\''	=> 'gmt timestamp NOT NULL DEFAULT timezone(\'gmt\'::text, now())',
		'default \'0000-00-00 00:00:00\''	=> 'DEFAULT now()',
		'datetime'		=> 'timestamp',
		'DEFAULT CHARACTER SET utf8'	=> '',
		
		// WP 2.7.1 compatibility
		'int(4)'		=> 'smallint',
		
		// For WPMU (starting with WP 3.2)
		'tinyint(2)'	=> 'smallint',
		'tinyint(1)'	=> 'smallint',
		"enum('0','1')"	=> 'smallint',
		'COLLATE utf8_general_ci'	=> '',
	);
	
	function pg4wp_installing( $sql, &$logto)
	{
		global $wpdb;
		
		// SHOW INDEX emulation
		if( 0 === strpos( $sql, 'SHOW INDEX'))
		{
			$logto = 'SHOWINDEX';
			$pattern = '/SHOW INDEX FROM\s+(\w+)/';
			preg_match( $pattern, $sql, $matches);
			$table = $matches[1];
$sql = 'SELECT bc.relname AS "Table",
	CASE WHEN i.indisunique THEN \'0\' ELSE \'1\' END AS "Non_unique",
	CASE WHEN i.indisprimary THEN \'PRIMARY\' WHEN bc.relname LIKE \'%usermeta\' AND ic.relname = \'umeta_key\'
		THEN \'meta_key\' ELSE REPLACE( ic.relname, \''.$table.'_\', \'\') END AS "Key_name",
	a.attname AS "Column_name",
	NULL AS "Sub_part"
FROM pg_class bc, pg_class ic, pg_index i, pg_attribute a
WHERE bc.oid = i.indrelid
	AND ic.oid = i.indexrelid
	AND (i.indkey[0] = a.attnum OR i.indkey[1] = a.attnum OR i.indkey[2] = a.attnum OR i.indkey[3] = a.attnum OR i.indkey[4] = a.attnum OR i.indkey[5] = a.attnum OR i.indkey[6] = a.attnum OR i.indkey[7] = a.attnum)
	AND a.attrelid = bc.oid
	AND bc.relname = \''.$table.'\'
	ORDER BY a.attname;';
		}
		// Table alteration
		elseif( 0 === strpos( $sql, 'ALTER TABLE'))
		{
			$logto = 'ALTER';
			$pattern = '/ALTER TABLE\s+(\w+)\s+CHANGE COLUMN\s+([^\s]+)\s+([^\s]+)\s+([^ ]+)( unsigned|)\s+(NOT NULL|)\s*(default (.+)|)/';
			if( 1 === preg_match( $pattern, $sql, $matches))
			{
				$table = $matches[1];
				$col = $matches[2];
				$newname = $matches[3];
				$type = $matches[4];
				if( isset($GLOBALS['pg4wp_ttr'][$type]))
					$type = $GLOBALS['pg4wp_ttr'][$type];
				$unsigned = $matches[5];
				$notnull = $matches[6];
				$default = $matches[7];
				$defval = $matches[8];
				if( isset($GLOBALS['pg4wp_ttr'][$defval]))
					$defval = $GLOBALS['pg4wp_ttr'][$defval];
				$newq = "ALTER TABLE $table ALTER COLUMN $col TYPE $type";
				if( !empty($notnull))
					$newq .= ", ALTER COLUMN $col SET NOT NULL";
				if( !empty($default))
					$newq .= ", ALTER COLUMN $col SET DEFAULT $defval";
				if( $col != $newname)
					$newq .= ";ALTER TABLE $table RENAME COLUMN $col TO $newcol;";
				$sql = $newq;
			}
			$pattern = '/ALTER TABLE\s+(\w+)\s+ADD COLUMN\s+([^\s]+)\s+([^ ]+)( unsigned|)\s+(NOT NULL|)\s*(default (.+)|)/';
			if( 1 === preg_match( $pattern, $sql, $matches))
			{
				$table = $matches[1];
				$col = $matches[2];
				$type = $matches[3];
				if( isset($GLOBALS['pg4wp_ttr'][$type]))
					$type = $GLOBALS['pg4wp_ttr'][$type];
				$unsigned = $matches[4];
				$notnull = $matches[5];
				$default = $matches[6];
				$defval = $matches[7];
				if( isset($GLOBALS['pg4wp_ttr'][$defval]))
					$defval = $GLOBALS['pg4wp_ttr'][$defval];
				$newq = "ALTER TABLE $table ADD COLUMN $col $type";
				if( !empty($default))
					$newq .= " DEFAULT $defval";
				if( !empty($notnull))
					$newq .= " NOT NULL";
				$sql = $newq;
			}
			$pattern = '/ALTER TABLE\s+(\w+)\s+ADD (UNIQUE |)KEY\s+([^\s]+)\s+\(([^\)]+)\)/';
			if( 1 === preg_match( $pattern, $sql, $matches))
			{
				$table = $matches[1];
				$unique = $matches[2];
				$index = $matches[3];
				$columns = $matches[4];
				// Workaround for index name duplicate
				$index = $table.'_'.$index;
				$sql = "CREATE {$unique}INDEX $index ON $table ($columns)";
			}
			$pattern = '/ALTER TABLE\s+(\w+)\s+DROP INDEX\s+([^\s]+)/';
			if( 1 === preg_match( $pattern, $sql, $matches))
			{
				$table = $matches[1];
				$index = $matches[2];
				$sql = "DROP INDEX ${table}_${index}";
			}
			$pattern = '/ALTER TABLE\s+(\w+)\s+DROP PRIMARY KEY/';
			if( 1 === preg_match( $pattern, $sql, $matches))
			{
				$table = $matches[1];
				$sql = "ALTER TABLE ${table} DROP CONSTRAINT ${table}_pkey";
			}
		}
		// Table description
		elseif( 0 === strpos( $sql, 'DESCRIBE'))
		{
			$logto = 'DESCRIBE';
			preg_match( '/DESCRIBE\s+(\w+)/', $sql, $matches);
			$table_name = $matches[1];
$sql = "SELECT pg_attribute.attname AS \"Field\",
	CASE pg_type.typname
		WHEN 'int2' THEN 'int(4)'
		WHEN 'int4' THEN 'int(11)'
		WHEN 'int8' THEN 'bigint(20) unsigned'
		WHEN 'varchar' THEN 'varchar(' || pg_attribute.atttypmod-4 || ')'
		WHEN 'timestamp' THEN 'datetime'
		WHEN 'text' THEN 'longtext'
		ELSE pg_type.typname
	END AS \"Type\",
	CASE WHEN pg_attribute.attnotnull THEN ''
		ELSE 'YES'
	END AS \"Null\",
	CASE pg_type.typname
		WHEN 'varchar' THEN substring(pg_attrdef.adsrc FROM '^''(.*)''.*$')
		WHEN 'timestamp' THEN CASE WHEN pg_attrdef.adsrc LIKE '%now()%' THEN '0000-00-00 00:00:00' ELSE pg_attrdef.adsrc END
		ELSE pg_attrdef.adsrc
	END AS \"Default\"
FROM pg_class
	INNER JOIN pg_attribute
		ON (pg_class.oid=pg_attribute.attrelid)
	INNER JOIN pg_type
		ON (pg_attribute.atttypid=pg_type.oid)
	LEFT JOIN pg_attrdef
		ON (pg_class.oid=pg_attrdef.adrelid AND pg_attribute.attnum=pg_attrdef.adnum)
WHERE pg_class.relname='$table_name' AND pg_attribute.attnum>=1 AND NOT pg_attribute.attisdropped;";
		} // DESCRIBE
		// Fix table creations
		elseif( 0 === strpos($sql, 'CREATE TABLE'))
		{
			$logto = 'CREATE';
			$sql = str_replace( 'CREATE TABLE IF NOT EXISTS ', 'CREATE TABLE ', $sql);
			$pattern = '/CREATE TABLE [`]?(\w+)[`]?/';
			preg_match($pattern, $sql, $matches);
			$table = $matches[1];
			
			// Remove trailing spaces
			$sql = trim( $sql).';';
			
			// Translate types and some other replacements
			$sql = str_replace(
				array_keys($GLOBALS['pg4wp_ttr']), array_values($GLOBALS['pg4wp_ttr']), $sql);
			
			// Fix auto_increment by adding a sequence
			$pattern = '/int[ ]+NOT NULL auto_increment/';
			preg_match($pattern, $sql, $matches);
			if($matches)
			{
				$seq = $table . '_seq';
				$sql = str_replace( 'NOT NULL auto_increment', "NOT NULL DEFAULT nextval('$seq'::text)", $sql);
				$sql .= "\nCREATE SEQUENCE $seq;";
			}
			
			// Support for INDEX creation
			$pattern = '/,\s+(UNIQUE |)KEY\s+([^\s]+)\s+\(((?:[\w]+(?:\([\d]+\))?[,]?)*)\)/';
			if( preg_match_all( $pattern, $sql, $matches, PREG_SET_ORDER))
				foreach( $matches as $match)
				{
					$unique = $match[1];
					$index = $match[2];
					$columns = $match[3];
					$columns = preg_replace( '/\(\d+\)/', '', $columns);
					// Workaround for index name duplicate
					$index = $table.'_'.$index;
					$sql .= "\nCREATE {$unique}INDEX $index ON $table ($columns);";
				}
			// Now remove handled indexes
			$sql = preg_replace( $pattern, '', $sql);
		}// CREATE TABLE
		elseif( 0 === strpos($sql, 'DROP TABLE'))
		{
			$logto = 'DROPTABLE';
			$pattern = '/DROP TABLE.+ [`]?(\w+)[`]?$/';
			preg_match($pattern, $sql, $matches);
			$table = $matches[1];
			$seq = $table . '_seq';
			$sql .= ";\nDROP SEQUENCE IF EXISTS $seq;";
		}// DROP TABLE
		
		return $sql;
	}
