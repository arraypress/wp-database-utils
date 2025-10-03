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

	/**
	 * Get row count for a table with optional conditions.
	 *
	 * @param string $table Table name (without prefix).
	 * @param array  $where Optional WHERE conditions as column => value pairs.
	 *
	 * @return int Number of rows.
	 */
	public static function count_rows( string $table, array $where = [] ): int {
		global $wpdb;

		$table = sanitize_key( $table );
		$sql   = "SELECT COUNT(*) FROM {$wpdb->prefix}{$table}";

		if ( ! empty( $where ) ) {
			$conditions = [];
			$values     = [];

			foreach ( $where as $column => $value ) {
				$column = sanitize_key( $column );

				if ( is_null( $value ) ) {
					$conditions[] = "{$column} IS NULL";
				} else {
					$conditions[] = "{$column} = %s";
					$values[]     = $value;
				}
			}

			if ( ! empty( $conditions ) ) {
				$sql .= " WHERE " . implode( ' AND ', $conditions );

				if ( ! empty( $values ) ) {
					$sql = $wpdb->prepare( $sql, $values );
				}
			}
		}

		return (int) $wpdb->get_var( $sql );
	}

	/**
	 * Truncate a table (remove all rows).
	 *
	 * @param string $table                Table name (without prefix).
	 * @param bool   $reset_auto_increment Whether to reset AUTO_INCREMENT to 1.
	 *
	 * @return bool True on success, false on failure.
	 */
	public static function truncate_table( string $table, bool $reset_auto_increment = true ): bool {
		global $wpdb;

		$table           = sanitize_key( $table );
		$full_table_name = $wpdb->prefix . $table;

		// Check if table exists first
		if ( ! self::table_exists( $table ) ) {
			return false;
		}

		// Use TRUNCATE if we want to reset auto increment
		if ( $reset_auto_increment ) {
			$result = $wpdb->query( "TRUNCATE TABLE {$full_table_name}" );
		} else {
			// Use DELETE to preserve auto increment value
			$result = $wpdb->query( "DELETE FROM {$full_table_name}" );
		}

		return $result !== false;
	}

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

}