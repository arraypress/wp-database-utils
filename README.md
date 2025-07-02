# WordPress Database Utilities

A lightweight WordPress library for essential database operations and query building. Provides clean APIs for common database tasks that developers use every day.

## Features

* 🔍 **Existence Checks**: Check if tables, columns, rows, and values exist
* 🛡️ **Safe Query Building**: LIKE patterns, placeholders, and prepared statements
* 📋 **Query Components**: WHERE, ORDER BY, LIMIT, and other clause builders
* 🗄️ **Table Information**: Get table names, prefixes, and metadata tables
* ⚡ **Performance Helpers**: Type casting, result handling, and query optimization
* 🎯 **WordPress Integration**: Built specifically for WordPress database patterns

## Requirements

* PHP 7.4 or later
* WordPress 5.0 or later

## Installation

```bash
composer require arraypress/wp-database-utils
```

## Basic Usage

### Database Information & Existence Checks

```php
use ArrayPress\DatabaseUtils\Database;

// Check if things exist
$table_exists  = Database::table_exists( 'custom_table' );
$column_exists = Database::column_exists( 'posts', 'custom_field' );
$row_exists    = Database::row_exists( 'posts', 'ID', 123 );
$value_exists  = Database::value_exists( 'users', 'email', 'user@example.com' );

// Get table information
$posts_table    = Database::get_table( 'post' ); // Returns wp_posts
$postmeta_table = Database::get_meta_table( 'post' ); // Returns wp_postmeta
$prefix         = Database::get_prefix(); // Returns wp_
$charset        = Database::get_charset_collate();

// Query results
$last_id       = Database::get_insert_id();
$affected_rows = Database::get_affected_rows();

// Type casting for query results
$count = Database::get_var_cast( $query, 'int', 0 );
$price = Database::get_var_cast( $query, 'float', 0.00 );
```

### Query Building & Patterns

```php
use ArrayPress\DatabaseUtils\Query;

// LIKE patterns (automatically escaped)
$prefix_pattern   = Query::like_pattern( 'prefix', 'prefix' ); // "prefix%"
$suffix_pattern   = Query::like_pattern( 'suffix', 'suffix' ); // "%suffix"
$contains_pattern = Query::like_pattern( 'word', 'substring' ); // "%word%"
$exact_pattern    = Query::like_pattern( 'exact', 'exact' ); // "exact"

// Placeholders for prepared statements
$placeholders     = Query::placeholders( [ 'a', 'b', 'c' ] ); // "%s, %s, %s"
$int_placeholders = Query::placeholders( [ 1, 2, 3 ], '%d' ); // "%d, %d, %d"

// IN/NOT IN clauses
$in_clause     = Query::in_clause( 'post_id', [ 1, 2, 3 ] ); // "post_id IN (%s, %s, %s)"
$not_in_clause = Query::in_clause( 'post_id', [ 1, 2, 3 ], true ); // "post_id NOT IN (%s, %s, %s)"

// LIKE clauses
$like_clause = Query::like_clause( 'post_title', 'search term' ); // "post_title LIKE %search term%"
$prefix_like = Query::like_clause( 'post_name', 'hello', 'prefix' ); // "post_name LIKE hello%"

// Range conditions
$between_clause = Query::between_clause( 'post_date', '2024-01-01', '2024-12-31' );

// Flexible conditions with type safety
$condition  = Query::condition( 'price', 100, '>', 'float' );
$null_check = Query::condition( 'meta_value', null, '!=' ); // "meta_value IS NOT NULL"
```

### Query Component Building

```php
// WHERE clauses
$conditions = [
	Query::condition( 'post_status', 'publish' ),
	Query::condition( 'post_type', 'post' ),
	Query::like_clause( 'post_title', 'search term' )
];
$where      = Query::where_clause( $conditions ); // "WHERE post_status = 'publish' AND ..."
$where_or   = Query::where_clause( $conditions, 'OR' );

// ORDER BY clauses
$order_by = Query::order_by_clause( [ 'post_date' => 'DESC', 'post_title' => 'ASC' ] );

// LIMIT clauses
$limit        = Query::limit_clause( 10 ); // "LIMIT 10"
$limit_offset = Query::limit_clause( 10, 20 ); // "LIMIT 20, 10"

// GROUP BY clauses
$group_by = Query::group_by_clause( [ 'post_author', 'post_type' ] );

// Utility helpers
$direction = Query::sanitize_order( 'desc' ); // Returns 'DESC'
$operator  = Query::sanitize_operator( '>=' ); // Returns '>='
$not_empty = Query::not_empty( 'meta_value' ); // "(meta_value != '' AND meta_value IS NOT NULL)"
```

### Dynamic Query Building

```php
// The old way (repetitive)
$filters = [
	'status' => 'publish',
	'type'   => [ 'post', 'page' ],
	'author' => [ 1, 2, 3 ],
	'search' => 'wordpress'
];

$conditions = [];
foreach ( $filters as $key => $value ) {
	// ... lots of switch/case logic
}

// The new way (clean & reusable)
$query = Query::build_filtered_select( 'posts', [
	'status'    => 'publish',
	'type'      => [ 'post', 'page' ],
	'author'    => [ 1, 2, 3 ],
	'search'    => 'wordpress',
	'date_from' => '2024-01-01',
	'date_to'   => '2024-12-31'
], [ 'post_date' => 'DESC' ], 10 );

// Or just get the conditions
$conditions = Query::build_conditions( [
	'status' => 'publish',
	'search' => 'wordpress'
] );

// Custom column mapping
$conditions = Query::build_conditions( [
	'title_search' => 'wordpress',
	'price_min'    => 100
], [
	'title_search' => 'product_title',
	'price_min'    => 'product_price'
] );
```

### Real-world Examples

```php
// Check if a custom table exists before querying
if ( Database::table_exists( 'products' ) ) {
	// Safe to query the products table
	$exists = Database::row_exists( 'products', 'sku', 'PRODUCT-123' );
}

// Build a search query safely
$search_term = 'wordpress';
$conditions  = [
	Query::condition( 'post_status', 'publish' ),
	Query::in_clause( 'post_type', [ 'post', 'page' ] ),
	Query::like_clause( 'post_title', $search_term, 'substring' )
];

$query = Query::build_select(
	'posts',
	$conditions,
	[ 'post_date' => 'DESC' ],
	20
);

// Custom meta query with proper escaping
$meta_table = Database::get_meta_table( 'post' );
$conditions = [
	Query::condition( 'meta_key', '_featured' ),
	Query::condition( 'meta_value', '1' )
];

$meta_query = "SELECT post_id FROM {$meta_table} " . Query::where_clause( $conditions );

// Clean up old data with pattern matching
$temp_pattern  = Query::like_pattern( 'temp_', 'prefix' );
$cleanup_query = $wpdb->prepare(
	"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
	$temp_pattern
);

// Safe pagination queries
$page     = 2;
$per_page = 10;
$offset   = ( $page - 1 ) * $per_page;

$paginated_query = Query::build_select(
		'posts',
		[ Query::condition( 'post_status', 'publish' ) ],
		[ 'post_date' => 'DESC' ],
		$per_page
	) . ' ' . Query::limit_clause( $per_page, $offset );
```

### Advanced Usage

```php
// Build custom filters for different contexts
$post_filters = [
	'status'    => 'publish',
	'type'      => [ 'post', 'page' ],
	'search'    => 'wordpress',
	'date_from' => '2024-01-01'
];

$product_filters = [
	'status'    => 'publish',
	'price_min' => 100,
	'category'  => [ 1, 2, 3 ]
];

// Use custom mapping for non-standard column names
$product_mapping = [
	'price_min' => 'product_price',
	'category'  => 'product_category'
];

$post_query    = Query::build_filtered_select( 'posts', $post_filters );
$product_query = Query::build_filtered_select( 'products', $product_filters, [], 0, $product_mapping );

// Manual condition building for complex cases
$conditions = [
	Query::condition( 'post_status', 'publish' ),
	Query::between_clause( 'post_date', '2024-01-01', '2024-12-31' ),
	Query::in_clause( 'post_author', [ 1, 2, 3 ] ),
	Query::not_empty( 'post_excerpt' ),
	Query::like_clause( 'post_content', 'important', 'substring' )
];

$complex_query = Query::build_select( 'posts', $conditions, [ 'post_date' => 'DESC' ] );
```

## Key Features

- **Safe by Default**: All methods use proper escaping and prepared statements
- **WordPress Optimized**: Built specifically for WordPress database patterns
- **Type Safety**: Proper type casting and validation throughout
- **Pattern Matching**: Flexible LIKE pattern generation with automatic escaping
- **Component Based**: Build queries piece by piece or use complete builders
- **Error Prevention**: Existence checks prevent common database errors

## Requirements

- PHP 7.4+
- WordPress 5.0+

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This project is licensed under the GPL-2.0-or-later License.

## Support

- [Documentation](https://github.com/arraypress/wp-database-utils)
- [Issue Tracker](https://github.com/arraypress/wp-database-utils/issues)