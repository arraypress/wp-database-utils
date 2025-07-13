# WordPress Database Utilities

A lean WordPress library for essential database operations and query building. Provides clean APIs for common database tasks that developers use every day.

## Features

* 🔍 **Existence Checks**: Check if tables, columns, rows, and values exist
* 🛡️ **Safe Query Building**: LIKE patterns, placeholders, and prepared statements
* 📋 **Query Components**: WHERE, ORDER BY, LIMIT, and other clause builders
* 🗄️ **Table Information**: Get table names, prefixes, and metadata tables
* 📅 **Date Range Helpers**: Prevent code duplication for date range queries
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
```

### Query Building & Patterns

```php
use ArrayPress\DatabaseUtils\Builder;

// LIKE patterns (automatically escaped)
$prefix_pattern   = Builder::like_pattern( 'prefix', 'prefix' ); // "prefix%"
$suffix_pattern   = Builder::like_pattern( 'suffix', 'suffix' ); // "%suffix"
$contains_pattern = Builder::like_pattern( 'word', 'substring' ); // "%word%"

// Placeholders for prepared statements
$placeholders     = Builder::placeholders( [ 'a', 'b', 'c' ] ); // "%s, %s, %s"
$int_placeholders = Builder::placeholders( [ 1, 2, 3 ], '%d' ); // "%d, %d, %d"

// IN/NOT IN clauses
$in_clause     = Builder::in_clause( 'post_id', [ 1, 2, 3 ] ); // "post_id IN (%s, %s, %s)"
$not_in_clause = Builder::in_clause( 'post_id', [ 1, 2, 3 ], true ); // "post_id NOT IN (%s, %s, %s)"

// LIKE clauses
$like_clause = Builder::like_clause( 'post_title', 'search term' ); // "post_title LIKE %search term%"
$prefix_like = Builder::like_clause( 'post_name', 'hello', 'prefix' ); // "post_name LIKE hello%"

// Range conditions
$between_clause = Builder::between_clause( 'post_date', '2024-01-01', '2024-12-31' );

// Flexible conditions with type safety
$condition  = Builder::condition( 'price', 100, '>', 'float' );
$null_check = Builder::condition( 'meta_value', null, '!=' ); // "meta_value IS NOT NULL"
```

### Query Component Building

```php
// WHERE clauses
$conditions = [
	Builder::condition( 'post_status', 'publish' ),
	Builder::condition( 'post_type', 'post' ),
	Builder::like_clause( 'post_title', 'search term' )
];
$where      = Builder::where_clause( $conditions ); // "WHERE post_status = 'publish' AND ..."
$where_or   = Builder::where_clause( $conditions, 'OR' );

// ORDER BY clauses
$order_by = Builder::order_by_clause( [ 'post_date' => 'DESC', 'post_title' => 'ASC' ] );

// LIMIT clauses
$limit        = Builder::limit_clause( 10 ); // "LIMIT 10"
$limit_offset = Builder::limit_clause( 10, 20 ); // "LIMIT 20, 10"

// GROUP BY clauses
$group_by = Builder::group_by_clause( [ 'post_author', 'post_type' ] );
```

### Advanced Parameter Handling

```php
// Safe IN clause with parameter building (prevents SQL injection)
$params = [];
$safe_in = Builder::safe_in_clause( 'post_id', [ 1, 2, 3 ], $params, false, '%d' );
// $params now contains [1, 2, 3]

// Placeholders with automatic parameter collection
$params = [];
$placeholders = Builder::placeholders_with_params( $values, $params );
```

### Date Range Queries (Perfect for Reports & Analytics)

```php
// Build date range conditions with parameters
$params = [];
$conditions = Builder::date_range_conditions( 
    'order_date', 
    '2024-01-01', 
    '2024-12-31', 
    $params 
);
// Returns: ["order_date >= %s", "order_date <= %s"]
// $params contains: ['2024-01-01', '2024-12-31']

// Build complete date range clause
$params = [];
$where_clause = Builder::date_range_clause( 
    'order_date', 
    '2024-01-01', 
    '2024-12-31', 
    $params, 
    ' AND ' 
);
// Returns: " AND order_date >= %s AND order_date <= %s"

// Perfect for analytics queries
$start_date = '2024-01-01';
$end_date = '2024-12-31';
$params = [ 'publish' ];

$date_clause = Builder::date_range_clause( 'post_date', $start_date, $end_date, $params );

$sql = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s {$date_clause}";
$count = $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
```

### Real-world Examples

```php
// Check if a custom table exists before querying
if ( Database::table_exists( 'products' ) ) {
    $exists = Database::row_exists( 'products', 'sku', 'PRODUCT-123' );
}

// Build a search query safely
$search_term = 'wordpress';
$conditions  = [
    Builder::condition( 'post_status', 'publish' ),
    Builder::in_clause( 'post_type', [ 'post', 'page' ] ),
    Builder::like_clause( 'post_title', $search_term, 'substring' )
];

$where = Builder::where_clause( $conditions );
$order = Builder::order_by_clause( [ 'post_date' => 'DESC' ] );
$limit = Builder::limit_clause( 20 );

$sql = "SELECT * FROM {$wpdb->posts} {$where} {$order} {$limit}";

// Date range analytics (common in EDD, WooCommerce, etc.)
$params = [];
$date_conditions = Builder::date_range_conditions( 
    'order_date', 
    $_GET['start_date'] ?? null, 
    $_GET['end_date'] ?? null, 
    $params 
);

if ( ! empty( $date_conditions ) ) {
    $where = 'WHERE ' . implode( ' AND ', $date_conditions );
    $sql = "SELECT SUM(total) FROM orders {$where}";
    $total = $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
}
```

## Key Features

- **Safe by Default**: All methods use proper escaping and prepared statements
- **WordPress Optimized**: Built specifically for WordPress database patterns
- **Lean & Focused**: Only essential utilities that save time and prevent errors
- **Date Range Helpers**: Prevent code duplication in analytics and reporting
- **Parameter Safety**: Automatic parameter handling to prevent SQL injection
- **Component Based**: Build queries piece by piece with reusable components

## What's Not Included (By Design)

This library intentionally **does not** include:
- Complex query builders (use WP_Query or custom SQL instead)
- ORM functionality (WordPress has enough abstraction layers)
- Basic utilities that are one-liners (`$wpdb->insert_id`, etc.)

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