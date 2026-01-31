<?php
/**
 * Query Builder Utility Class
 *
 * Provides utility functions for building safe SQL query components in WordPress,
 * including LIKE patterns, placeholders, and common query clauses.
 *
 * @package     ArrayPress\DatabaseUtils
 * @copyright   Copyright (c) 2025, ArrayPress Limited
 * @license     GPL-2.0-or-later
 * @since       1.0.0
 * @author      David Sherlock
 */

declare( strict_types=1 );

namespace ArrayPress\DatabaseUtils;

/**
 * Class Builder
 *
 * Query component building utilities for WordPress.
 */
class Builder {

	/**
	 * Generate SQL LIKE pattern.
	 *
	 * @param string $pattern Pattern to match.
	 * @param string $type    Pattern type ('prefix', 'suffix', 'substring', 'exact').
	 *
	 * @return string SQL LIKE pattern.
	 */
	public static function like_pattern( string $pattern, string $type = 'exact' ): string {
		global $wpdb;
		$escaped = $wpdb->esc_like( $pattern );

		switch ( $type ) {
			case 'prefix':
				return $escaped . '%';
			case 'suffix':
				return '%' . $escaped;
			case 'substring':
				return '%' . $escaped . '%';
			case 'exact':
			default:
				return $escaped;
		}
	}

	/**
	 * Generate placeholders for prepared statements.
	 *
	 * @param array  $values Array of values.
	 * @param string $type   Placeholder type ('%s', '%d', '%f').
	 *
	 * @return string Comma-separated placeholders.
	 */
	public static function placeholders( array $values, string $type = '%s' ): string {
		return implode( ', ', array_fill( 0, count( $values ), $type ) );
	}

	/**
	 * Generate placeholders and add values to params array.
	 *
	 * @param array   $values Array of values.
	 * @param array  &$params Reference to params array to append to.
	 * @param string  $type   Placeholder type ('%s', '%d', '%f').
	 *
	 * @return string Comma-separated placeholders.
	 */
	public static function placeholders_with_params( array $values, array &$params, string $type = '%s' ): string {
		if ( empty( $values ) ) {
			return '';
		}

		$params = array_merge( $params, $values );

		return self::placeholders( $values, $type );
	}

	/**
	 * Generate SQL IN or NOT IN clause.
	 *
	 * @param string $column Column name.
	 * @param array  $values Array of values.
	 * @param bool   $not    Whether to generate NOT IN.
	 *
	 * @return string SQL IN clause.
	 */
	public static function in_clause( string $column, array $values, bool $not = false ): string {
		global $wpdb;

		if ( empty( $values ) ) {
			return $not ? '1=1' : '1=0';
		}

		$placeholders = self::placeholders( $values );
		$operator     = $not ? 'NOT IN' : 'IN';

		return $wpdb->prepare( "{$column} {$operator} ({$placeholders})", $values );
	}

	/**
	 * Generate safe IN clause with parameters.
	 *
	 * @param string  $column Column name.
	 * @param array   $values Array of values.
	 * @param array  &$params Reference to params array.
	 * @param bool    $not    Whether to generate NOT IN.
	 * @param string  $type   Value type ('%s', '%d', '%f').
	 *
	 * @return string SQL IN clause with placeholders.
	 */
	public static function safe_in_clause( string $column, array $values, array &$params, bool $not = false, string $type = '%s' ): string {
		if ( empty( $values ) ) {
			return $not ? '1=1' : '1=0';
		}

		$placeholders = self::placeholders_with_params( $values, $params, $type );
		$operator     = $not ? 'NOT IN' : 'IN';

		return "{$column} {$operator} ({$placeholders})";
	}

	/**
	 * Generate SQL LIKE clause.
	 *
	 * @param string $column Column name.
	 * @param string $value  Value for LIKE.
	 * @param string $type   Pattern type ('prefix', 'suffix', 'substring', 'exact').
	 *
	 * @return string SQL LIKE clause.
	 */
	public static function like_clause( string $column, string $value, string $type = 'substring' ): string {
		global $wpdb;
		$pattern = self::like_pattern( $value, $type );

		return $wpdb->prepare( "{$column} LIKE %s", $pattern );
	}

	/**
	 * Generate safe LIKE clause with parameters.
	 *
	 * @param string  $column Column name.
	 * @param string  $value  Value for LIKE.
	 * @param array  &$params Reference to params array.
	 * @param string  $type   Pattern type ('prefix', 'suffix', 'substring', 'exact').
	 *
	 * @return string SQL LIKE clause with placeholder.
	 */
	public static function safe_like_clause( string $column, string $value, array &$params, string $type = 'substring' ): string {
		$pattern  = self::like_pattern( $value, $type );
		$params[] = $pattern;

		return "{$column} LIKE %s";
	}

	/**
	 * Generate SQL range condition (BETWEEN).
	 *
	 * @param string $column Column name.
	 * @param mixed  $min    Minimum value.
	 * @param mixed  $max    Maximum value.
	 *
	 * @return string SQL BETWEEN clause.
	 */
	public static function between_clause( string $column, $min, $max ): string {
		global $wpdb;

		return $wpdb->prepare( "{$column} BETWEEN %s AND %s", $min, $max );
	}

	/**
	 * Generate safe BETWEEN clause with parameters.
	 *
	 * @param string  $column Column name.
	 * @param mixed   $min    Minimum value.
	 * @param mixed   $max    Maximum value.
	 * @param array  &$params Reference to params array.
	 * @param string  $type   Value type ('%s', '%d', '%f').
	 *
	 * @return string SQL BETWEEN clause with placeholders.
	 */
	public static function safe_between_clause( string $column, $min, $max, array &$params, string $type = '%s' ): string {
		$params[] = $min;
		$params[] = $max;

		return "{$column} BETWEEN {$type} AND {$type}";
	}

	/**
	 * Generate prepared condition for different data types.
	 *
	 * @param string $column    Column name.
	 * @param mixed  $value     Value to compare.
	 * @param string $operator  Comparison operator.
	 * @param string $data_type Data type ('string', 'int', 'float').
	 *
	 * @return string Prepared SQL condition.
	 */
	public static function condition( string $column, $value, string $operator = '=', string $data_type = 'string' ): string {
		global $wpdb;

		// Handle NULL values
		if ( is_null( $value ) ) {
			return $operator === '=' ? "{$column} IS NULL" : "{$column} IS NOT NULL";
		}

		// Handle empty string
		if ( $data_type === 'string' && $value === '' ) {
			return $operator === '=' ? "{$column} = ''" : "{$column} != ''";
		}

		$placeholders = [
			'string' => '%s',
			'int'    => '%d',
			'float'  => '%f',
		];

		$placeholder = $placeholders[ $data_type ] ?? '%s';

		return $wpdb->prepare( "{$column} {$operator} {$placeholder}", $value );
	}

	/**
	 * Generate safe condition with parameters.
	 *
	 * @param string  $column    Column name.
	 * @param mixed   $value     Value to compare.
	 * @param array  &$params    Reference to params array.
	 * @param string  $operator  Comparison operator.
	 * @param string  $data_type Data type ('string', 'int', 'float').
	 *
	 * @return string SQL condition with placeholder.
	 */
	public static function safe_condition( string $column, $value, array &$params, string $operator = '=', string $data_type = 'string' ): string {
		// Handle NULL values
		if ( is_null( $value ) ) {
			return $operator === '=' ? "{$column} IS NULL" : "{$column} IS NOT NULL";
		}

		// Handle empty string
		if ( $data_type === 'string' && $value === '' ) {
			return $operator === '=' ? "{$column} = ''" : "{$column} != ''";
		}

		$placeholders = [
			'string' => '%s',
			'int'    => '%d',
			'float'  => '%f',
		];

		$placeholder = $placeholders[ $data_type ] ?? '%s';
		$params[]    = $value;

		return "{$column} {$operator} {$placeholder}";
	}

	/**
	 * Generate WHERE clause from conditions.
	 *
	 * @param array  $conditions Array of condition strings.
	 * @param string $operator   Operator to join conditions ('AND' or 'OR').
	 *
	 * @return string WHERE clause.
	 */
	public static function where_clause( array $conditions, string $operator = 'AND' ): string {
		if ( empty( $conditions ) ) {
			return '';
		}

		$operator = strtoupper( $operator );
		if ( ! in_array( $operator, [ 'AND', 'OR' ], true ) ) {
			$operator = 'AND';
		}

		$filtered = array_filter( $conditions );

		return empty( $filtered ) ? '' : 'WHERE ' . implode( " {$operator} ", $filtered );
	}

	/**
	 * Generate ORDER BY clause.
	 *
	 * @param array $order_by Array of column => direction pairs.
	 *
	 * @return string ORDER BY clause.
	 */
	public static function order_by_clause( array $order_by ): string {
		if ( empty( $order_by ) ) {
			return '';
		}

		$clauses = [];
		foreach ( $order_by as $column => $direction ) {
			$column    = sanitize_key( $column );
			$direction = strtoupper( $direction ) === 'DESC' ? 'DESC' : 'ASC';
			$clauses[] = "`{$column}` {$direction}";
		}

		return 'ORDER BY ' . implode( ', ', $clauses );
	}

	/**
	 * Generate LIMIT clause with optional offset.
	 *
	 * @param int $limit  Number of rows to return.
	 * @param int $offset Number of rows to skip.
	 *
	 * @return string LIMIT clause.
	 */
	public static function limit_clause( int $limit, int $offset = 0 ): string {
		if ( $limit <= 0 ) {
			return '';
		}

		if ( $offset > 0 ) {
			return "LIMIT {$offset}, {$limit}";
		}

		return "LIMIT {$limit}";
	}

	/**
	 * Generate GROUP BY clause.
	 *
	 * @param array $columns Columns to group by.
	 *
	 * @return string GROUP BY clause.
	 */
	public static function group_by_clause( array $columns ): string {
		if ( empty( $columns ) ) {
			return '';
		}

		$quoted = array_map( function ( $column ) {
			return '`' . sanitize_key( $column ) . '`';
		}, $columns );

		return 'GROUP BY ' . implode( ', ', $quoted );
	}

	/**
	 * Generate HAVING clause from conditions.
	 *
	 * @param array  $conditions Array of condition strings.
	 * @param string $operator   Operator to join conditions ('AND' or 'OR').
	 *
	 * @return string HAVING clause.
	 */
	public static function having_clause( array $conditions, string $operator = 'AND' ): string {
		if ( empty( $conditions ) ) {
			return '';
		}

		$operator = strtoupper( $operator );
		if ( ! in_array( $operator, [ 'AND', 'OR' ], true ) ) {
			$operator = 'AND';
		}

		$filtered = array_filter( $conditions );

		return empty( $filtered ) ? '' : 'HAVING ' . implode( " {$operator} ", $filtered );
	}

	/**
	 * Build date range conditions with parameters.
	 *
	 * @param string       $column     Column name.
	 * @param string|null  $start_date Start date.
	 * @param string|null  $end_date   End date.
	 * @param array       &$params     Reference to params array.
	 *
	 * @return array Array of date conditions.
	 */
	public static function date_range_conditions( string $column, ?string $start_date, ?string $end_date, array &$params ): array {
		$conditions = [];

		if ( ! empty( $start_date ) ) {
			$conditions[] = "{$column} >= %s";
			$params[]     = $start_date;
		}

		if ( ! empty( $end_date ) ) {
			$conditions[] = "{$column} <= %s";
			$params[]     = $end_date;
		}

		return $conditions;
	}

	/**
	 * Build date range WHERE clause.
	 *
	 * @param string       $column     Column name.
	 * @param string|null  $start_date Start date.
	 * @param string|null  $end_date   End date.
	 * @param array       &$params     Reference to params array.
	 * @param string       $prefix     Prefix for conditions (e.g., ' AND ').
	 *
	 * @return string Date range clause.
	 */
	public static function date_range_clause( string $column, ?string $start_date, ?string $end_date, array &$params, string $prefix = ' AND ' ): string {
		$conditions = self::date_range_conditions( $column, $start_date, $end_date, $params );

		return empty( $conditions ) ? '' : $prefix . implode( ' AND ', $conditions );
	}

	/**
	 * Build a complete SELECT query string.
	 *
	 * @param string $table    Table name (with prefix).
	 * @param array  $columns  Columns to select (empty for *).
	 * @param string $where    WHERE clause.
	 * @param string $order_by ORDER BY clause.
	 * @param string $limit    LIMIT clause.
	 * @param string $group_by GROUP BY clause.
	 * @param string $having   HAVING clause.
	 *
	 * @return string Complete SELECT query.
	 */
	public static function select_query(
		string $table,
		array $columns = [],
		string $where = '',
		string $order_by = '',
		string $limit = '',
		string $group_by = '',
		string $having = ''
	): string {
		$select = empty( $columns ) ? '*' : implode( ', ', array_map( function ( $col ) {
			return '`' . sanitize_key( $col ) . '`';
		}, $columns ) );

		$parts = [ "SELECT {$select} FROM {$table}" ];

		if ( ! empty( $where ) ) {
			$parts[] = $where;
		}

		if ( ! empty( $group_by ) ) {
			$parts[] = $group_by;
		}

		if ( ! empty( $having ) ) {
			$parts[] = $having;
		}

		if ( ! empty( $order_by ) ) {
			$parts[] = $order_by;
		}

		if ( ! empty( $limit ) ) {
			$parts[] = $limit;
		}

		return implode( ' ', $parts );
	}

}