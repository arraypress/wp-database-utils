<?php
/**
 * Query Utility Class
 *
 * Provides utility functions for building safe SQL queries in WordPress,
 * including LIKE patterns, placeholders, and common query components.
 *
 * @package ArrayPress\DatabaseUtils
 * @since   1.0.0
 * @author  ArrayPress
 * @license GPL-2.0-or-later
 */

declare( strict_types=1 );

namespace ArrayPress\DatabaseUtils;

/**
 * Query Class
 *
 * Core query building utilities for WordPress.
 */
class Query {

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

	// ========================================
	// Query Component Builders
	// ========================================

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
		if ( ! in_array( $operator, [ 'AND', 'OR' ] ) ) {
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
			return "`{$column}`";
		}, $columns );

		return 'GROUP BY ' . implode( ', ', $quoted );
	}

	// ========================================
	// Utility Methods
	// ========================================

	/**
	 * Sanitize ORDER BY direction.
	 *
	 * @param string $direction Direction string.
	 *
	 * @return string 'ASC' or 'DESC'.
	 */
	public static function sanitize_order( string $direction ): string {
		return strtoupper( $direction ) === 'DESC' ? 'DESC' : 'ASC';
	}

	/**
	 * Sanitize comparison operator.
	 *
	 * @param string $operator Operator string.
	 *
	 * @return string Valid operator or '='.
	 */
	public static function sanitize_operator( string $operator ): string {
		$valid = [ '=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE' ];

		return in_array( strtoupper( $operator ), $valid ) ? strtoupper( $operator ) : '=';
	}

	/**
	 * Generate NOT EMPTY condition.
	 *
	 * @param string $column Column name.
	 *
	 * @return string NOT EMPTY condition.
	 */
	public static function not_empty( string $column ): string {
		return "({$column} != '' AND {$column} IS NOT NULL)";
	}

	/**
	 * Build conditions from dynamic filters array.
	 *
	 * @param array $filters Array of filter key => value pairs.
	 * @param array $mapping Optional custom mapping of filter keys to columns.
	 *
	 * @return array Array of SQL conditions.
	 */
	public static function build_conditions( array $filters, array $mapping = [] ): array {
		$conditions = [];

		// Default mappings for common WordPress patterns
		$default_mapping = [
			'status'     => 'post_status',
			'type'       => 'post_type',
			'author'     => 'post_author',
			'parent'     => 'post_parent',
			'search'     => 'post_title',
			'date_from'  => 'post_date',
			'date_to'    => 'post_date',
			'meta_key'   => 'meta_key',
			'meta_value' => 'meta_value',
		];

		$mapping = array_merge( $default_mapping, $mapping );

		foreach ( $filters as $key => $value ) {
			if ( empty( $value ) && $value !== '0' ) {
				continue;
			}

			$column = $mapping[ $key ] ?? $key;

			switch ( $key ) {
				case 'search':
					$conditions[] = self::like_clause( $column, $value, 'substring' );
					break;

				case 'date_from':
					$conditions[] = self::condition( $column, $value, '>=', 'string' );
					break;

				case 'date_to':
					$conditions[] = self::condition( $column, $value, '<=', 'string' );
					break;

				case 'type':
				case 'status':
				case 'author':
				case 'parent':
					if ( is_array( $value ) ) {
						$conditions[] = self::in_clause( $column, $value );
					} else {
						$conditions[] = self::condition( $column, $value );
					}
					break;

				default:
					// Handle custom filters
					if ( is_array( $value ) ) {
						$conditions[] = self::in_clause( $column, $value );
					} else {
						$conditions[] = self::condition( $column, $value );
					}
					break;
			}
		}

		return $conditions;
	}

	/**
	 * Build SELECT query from filters array.
	 *
	 * @param string $table    Table name (without prefix).
	 * @param array  $filters  Filter key => value pairs.
	 * @param array  $order_by ORDER BY columns.
	 * @param int    $limit    LIMIT count.
	 * @param array  $mapping  Optional custom column mapping.
	 *
	 * @return string Complete SELECT query.
	 */
	public static function build_filtered_select( string $table, array $filters = [], array $order_by = [], int $limit = 0, array $mapping = [] ): string {
		$conditions = self::build_conditions( $filters, $mapping );

		return self::build_select( $table, $conditions, $order_by, $limit );
	}

	/**
	 * Build simple SELECT query.
	 *
	 * @param string $table      Table name (without prefix).
	 * @param array  $conditions WHERE conditions.
	 * @param array  $order_by   ORDER BY columns.
	 * @param int    $limit      LIMIT count.
	 *
	 * @return string Complete SELECT query.
	 */
	public static function build_select( string $table, array $conditions = [], array $order_by = [], int $limit = 0 ): string {
		global $wpdb;

		$table = sanitize_key( $table );
		$query = "SELECT * FROM {$wpdb->prefix}{$table}";

		if ( ! empty( $conditions ) ) {
			$query .= ' ' . self::where_clause( $conditions );
		}

		if ( ! empty( $order_by ) ) {
			$query .= ' ' . self::order_by_clause( $order_by );
		}

		if ( $limit > 0 ) {
			$query .= ' ' . self::limit_clause( $limit );
		}

		return $query;
	}

	// ========================================
// Placeholder & Parameter Helpers
// ========================================

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

		// Add values to params array
		$params = array_merge( $params, $values );

		// Return placeholders
		return self::placeholders( $values, $type );
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

// ========================================
// Date Range Helpers
// ========================================

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

}