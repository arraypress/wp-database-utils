<?php
/**
 * Database Utility Class
 *
 * Provides utility functions for WordPress database operations,
 * including existence checks, table information, and basic queries.
 *
 * @package ArrayPress\DatabaseUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\DatabaseUtils;

/**
 * Database Class
 *
 * Core database utilities for WordPress.
 */
class Database {

	/**
	 * Check if a row exists in a table.
	 *
	 * @param string $table  Table name (without prefix).
	 * @param string $column Column to check.
	 * @param mixed  $value  Value to look for.
	 *
	 * @return bool True if row exists.
	 */
	public static function row_exists( string $table, string $column, $value ): bool {
		global $wpdb;

		if ( empty( $table ) || empty( $column ) || $value === null ) {
			return false;
		}

		$table  = sanitize_key( $table );
		$column = sanitize_key( $column );

		$sql = $wpdb->prepare(
			"SELECT EXISTS(SELECT 1 FROM {$wpdb->prefix}{$table} WHERE {$column} = %s LIMIT 1) AS result",
			$value
		);

		return $wpdb->get_var( $sql ) === '1';
	}

	/**
	 * Check if a table exists.
	 *
	 * @param string $table Table name (without prefix).
	 *
	 * @return bool True if table exists.
	 */
	public static function table_exists( string $table ): bool {
		global $wpdb;

		$table = sanitize_key( $table );
		$query = $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$wpdb->prefix . $table
		);

		return (bool) $wpdb->get_var( $query );
	}

	/**
	 * Check if a column exists in a table.
	 *
	 * @param string $table  Table name (without prefix).
	 * @param string $column Column name.
	 *
	 * @return bool True if column exists.
	 */
	public static function column_exists( string $table, string $column ): bool {
		global $wpdb;

		$table  = sanitize_key( $table );
		$column = sanitize_key( $column );

		$query = $wpdb->prepare(
			"SHOW COLUMNS FROM {$wpdb->prefix}{$table} LIKE %s",
			$column
		);

		return (bool) $wpdb->get_var( $query );
	}

	/**
	 * Check if a value exists in a table column.
	 *
	 * @param string $table  Table name (without prefix).
	 * @param string $column Column name.
	 * @param mixed  $value  Value to check.
	 *
	 * @return bool True if value exists.
	 */
	public static function value_exists( string $table, string $column, $value ): bool {
		global $wpdb;

		if ( empty( $table ) || empty( $column ) || $value === null ) {
			return false;
		}

		$table  = sanitize_key( $table );
		$column = sanitize_key( $column );

		$sql = $wpdb->prepare(
			"SELECT EXISTS(SELECT 1 FROM {$wpdb->prefix}{$table} WHERE {$column} = %s LIMIT 1) AS result",
			$value
		);

		return $wpdb->get_var( $sql ) === '1';
	}

	// ========================================
	// Table Information
	// ========================================

	/**
	 * Get meta table name for object type.
	 *
	 * @param string $meta_type Meta type ('post', 'user', 'term', 'comment').
	 *
	 * @return string|null Meta table name or null if invalid.
	 */
	public static function get_meta_table( string $meta_type ): ?string {
		global $wpdb;

		$tables = [
			'post'    => $wpdb->postmeta,
			'user'    => $wpdb->usermeta,
			'term'    => $wpdb->termmeta,
			'comment' => $wpdb->commentmeta,
		];

		return $tables[ $meta_type ] ?? null;
	}

	/**
	 * Get table name for object type.
	 *
	 * @param string $object_type Object type ('post', 'user', 'term', 'comment').
	 *
	 * @return string|null Table name or null if invalid.
	 */
	public static function get_table( string $object_type ): ?string {
		global $wpdb;

		$tables = [
			'post'    => $wpdb->posts,
			'user'    => $wpdb->users,
			'term'    => $wpdb->terms,
			'comment' => $wpdb->comments,
		];

		return $tables[ $object_type ] ?? null;
	}

	/**
	 * Get database table prefix.
	 *
	 * @return string Table prefix.
	 */
	public static function get_prefix(): string {
		global $wpdb;

		return $wpdb->prefix;
	}

	/**
	 * Get charset collate string.
	 *
	 * @return string Charset collate.
	 */
	public static function get_charset_collate(): string {
		global $wpdb;

		return $wpdb->get_charset_collate();
	}

	// ========================================
	// Query Results
	// ========================================

	/**
	 * Get last insert ID.
	 *
	 * @return int Last insert ID.
	 */
	public static function get_insert_id(): int {
		global $wpdb;

		return $wpdb->insert_id;
	}

	/**
	 * Get number of affected rows from last query.
	 *
	 * @return int Number of affected rows.
	 */
	public static function get_affected_rows(): int {
		global $wpdb;

		return $wpdb->rows_affected;
	}

	/**
	 * Execute query and return single value with type casting.
	 *
	 * @param string $query     SQL query.
	 * @param string $cast_type Type to cast to ('int', 'float', 'string').
	 * @param mixed  $default   Default value if result is null.
	 *
	 * @return mixed Casted result or default.
	 */
	public static function get_var_cast( string $query, string $cast_type = 'string', $default = null ) {
		global $wpdb;

		$result = $wpdb->get_var( $query );

		if ( is_null( $result ) ) {
			return $default;
		}

		switch ( $cast_type ) {
			case 'int':
				return (int) $result;
			case 'float':
				return (float) $result;
			case 'string':
			default:
				return (string) $result;
		}
	}

}