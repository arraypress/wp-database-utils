# WordPress Database Utilities

A lean WordPress library for essential database operations and query building. Provides clean APIs for common database
tasks that developers use every day.

## Features

* 🔍 **Existence Checks**: Check if tables, columns, indexes, and values exist
* 📊 **Data Retrieval**: Get single values, rows, or multiple rows with simple APIs
* 🛡️ **Safe Query Building**: LIKE patterns, placeholders, and prepared statements
* 📋 **Query Components**: WHERE, ORDER BY, LIMIT, GROUP BY, HAVING clause builders
* 🗄️ **Table Information**: Get table names, columns, prefixes, and metadata tables
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
$index_exists  = Database::index_exists( 'posts', 'post_name' );
$value_exists  = Database::value_exists( 'users', 'email', 'user@example.com' );

// Get table information
$posts_table    = Database::get_table( 'post' );      // Returns wp_posts
$postmeta_table = Database::get_meta_table( 'post' ); // Returns wp_postmeta
$prefix         = Database::get_prefix();              // Returns wp_
$charset        = Database::get_charset_collate();
$columns        = Database::get_columns( 'posts' );    // ['ID', 'post_author', ...]
```

### Data Retrieval

```php
use ArrayPress\DatabaseUtils\Database;

// Get a single value
$email = Database::get_value( 'users', 'user_email', [ 'ID' => 1 ] );

// Get a single row
$user = Database::get_row( 'users', [ 'user_login' => 'admin' ] );
echo $user->user_email;

// Get multiple rows with conditions, ordering, and pagination
$posts = Database::get_rows(
    'posts',
    [ 'post_status' => 'publish', 'post_type' => 'post' ],  // WHERE
    [ 'post_date' => 'DESC' ],                               // ORDER BY
    10,                                                       // LIMIT
    0                                                         // OFFSET
);

// Count rows with conditions
$count = Database::count_rows( 'posts', [ 'post_status' => 'publish' ] );

// Truncate a table
Database::truncate_table( 'custom_logs' );
Database::truncate_table( 'custom_logs', false ); // Preserve AUTO_INCREMENT
```

### Query Building & Patterns

```php
use ArrayPress\DatabaseUtils\Builder;

// LIKE patterns (automatically escaped)
$prefix_pattern   = Builder::like_pattern( 'prefix', 'prefix' );    // "prefix%"
$suffix_pattern   = Builder::like_pattern( 'suffix', 'suffix' );    // "%suffix"
$contains_pattern = Builder::like_pattern( 'word', 'substring' );   // "%word%"

// Placeholders for prepared statements
$placeholders     = Builder::placeholders( [ 'a', 'b', 'c' ] );     // "%s, %s, %s"
$int_placeholders = Builder::placeholders( [ 1, 2, 3 ], '%d' );     // "%d, %d, %d"

// IN/NOT IN clauses
$in_clause     = Builder::in_clause( 'post_id', [ 1, 2, 3 ] );
$not_in_clause = Builder::in_clause( 'post_id', [ 1, 2, 3 ], true );

// LIKE clauses
$like_clause = Builder::like_clause( 'post_title', 'search term' );
$prefix_like = Builder::like_clause( 'post_name', 'hello', 'prefix' );

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
$where    = Builder::where_clause( $conditions );          // "WHERE ... AND ... AND ..."
$where_or = Builder::where_clause( $conditions, 'OR' );    // "WHERE ... OR ... OR ..."

// ORDER BY clauses
$order_by = Builder::order_by_clause( [ 'post_date' => 'DESC', 'post_title' => 'ASC' ] );

// LIMIT clauses
$limit        = Builder::limit_clause( 10 );       // "LIMIT 10"
$limit_offset = Builder::limit_clause( 10, 20 );   // "LIMIT 20, 10"

// GROUP BY clauses
$group_by = Builder::group_by_clause( [ 'post_author', 'post_type' ] );

// HAVING clauses (for aggregates)
$having_conditions = [
    Builder::condition( 'COUNT(*)', 5, '>', 'int' ),
];
$having = Builder::having_clause( $having_conditions );
```

### Safe Parameter Handling

The `safe_*` methods let you build complex queries by collecting parameters in an array, then calling `$wpdb->prepare()`
once at the end.

```php
// Safe IN clause with parameter building
$params = [];
$in_clause = Builder::safe_in_clause( 'post_id', [ 1, 2, 3 ], $params, false, '%d' );
// $in_clause = "post_id IN (%d, %d, %d)"
// $params = [1, 2, 3]

// Safe LIKE clause
$params = [];
$like_clause = Builder::safe_like_clause( 'post_title', 'search', $params, 'substring' );
// $like_clause = "post_title LIKE %s"
// $params = ['%search%']

// Safe BETWEEN clause
$params = [];
$between = Builder::safe_between_clause( 'price', 10, 100, $params, '%d' );
// $between = "price BETWEEN %d AND %d"
// $params = [10, 100]

// Safe condition
$params = [];
$condition = Builder::safe_condition( 'post_status', 'publish', $params );
// $condition = "post_status = %s"
// $params = ['publish']

// Placeholders with automatic parameter collection
$params = [];
$placeholders = Builder::placeholders_with_params( [ 'a', 'b', 'c' ], $params );
// $placeholders = "%s, %s, %s"
// $params = ['a', 'b', 'c']
```

### Date Range Queries

Perfect for reports and analytics:

```php
// Build date range conditions with parameters
$params = [];
$conditions = Builder::date_range_conditions( 
    'order_date', 
    '2024-01-01', 
    '2024-12-31', 
    $params 
);
// $conditions = ["order_date >= %s", "order_date <= %s"]
// $params = ['2024-01-01', '2024-12-31']

// Build complete date range clause
$params = [];
$date_clause = Builder::date_range_clause( 
    'order_date', 
    '2024-01-01', 
    '2024-12-31', 
    $params, 
    ' AND ' 
);
// $date_clause = " AND order_date >= %s AND order_date <= %s"

// Use in analytics query
$start_date = '2024-01-01';
$end_date = '2024-12-31';
$params = [ 'publish' ];

$date_clause = Builder::date_range_clause( 'post_date', $start_date, $end_date, $params );

$sql = "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s {$date_clause}";
$count = $wpdb->get_var( $wpdb->prepare( $sql, $params ) );
```

### Building Complete Queries

```php
// Use select_query() to build a complete SELECT statement
global $wpdb;

$where    = Builder::where_clause( [ Builder::condition( 'post_status', 'publish' ) ] );
$order_by = Builder::order_by_clause( [ 'post_date' => 'DESC' ] );
$limit    = Builder::limit_clause( 10 );
$group_by = Builder::group_by_clause( [ 'post_author' ] );

$sql = Builder::select_query(
    $wpdb->posts,
    [ 'post_author', 'COUNT(*) as post_count' ],  // columns
    $where,
    $order_by,
    $limit,
    $group_by
);
```

## Real-World Examples

### Check Table Before Querying

```php
if ( Database::table_exists( 'products' ) ) {
    $product = Database::get_row( 'products', [ 'sku' => 'PRODUCT-123' ] );
    
    if ( $product ) {
        echo $product->name;
    }
}
```

### Build a Search Query Safely

```php
global $wpdb;

$search_term = sanitize_text_field( $_GET['s'] ?? '' );
$params = [];

$conditions = [
    Builder::safe_condition( 'post_status', 'publish', $params ),
    Builder::safe_in_clause( 'post_type', [ 'post', 'page' ], $params ),
];

if ( ! empty( $search_term ) ) {
    $conditions[] = Builder::safe_like_clause( 'post_title', $search_term, $params, 'substring' );
}

$where = Builder::where_clause( $conditions );
$order = Builder::order_by_clause( [ 'post_date' => 'DESC' ] );
$limit = Builder::limit_clause( 20 );

$sql = $wpdb->prepare(
    "SELECT * FROM {$wpdb->posts} {$where} {$order} {$limit}",
    $params
);

$results = $wpdb->get_results( $sql );
```

### Analytics Dashboard Query

```php
global $wpdb;

$start_date = sanitize_text_field( $_GET['start_date'] ?? null );
$end_date   = sanitize_text_field( $_GET['end_date'] ?? null );
$params     = [];

// Build conditions
$conditions = [];
$conditions[] = Builder::safe_condition( 'status', 'completed', $params );

// Add date range if provided
$date_conditions = Builder::date_range_conditions( 'order_date', $start_date, $end_date, $params );
$conditions = array_merge( $conditions, $date_conditions );

$where    = Builder::where_clause( $conditions );
$group_by = Builder::group_by_clause( [ 'product_id' ] );
$order_by = Builder::order_by_clause( [ 'total_sales' => 'DESC' ] );
$limit    = Builder::limit_clause( 10 );

$sql = $wpdb->prepare(
    "SELECT product_id, SUM(quantity) as total_sales, SUM(total) as revenue 
     FROM {$wpdb->prefix}orders 
     {$where} {$group_by} {$order_by} {$limit}",
    $params
);

$top_products = $wpdb->get_results( $sql );
```

### Simple Data Lookups

```php
// Instead of writing raw SQL for simple lookups:
$user_email = Database::get_value( 'users', 'user_email', [ 'ID' => 123 ] );

$active_users = Database::get_rows(
    'users',
    [ 'user_status' => 0 ],
    [ 'user_registered' => 'DESC' ],
    50
);

$user_count = Database::count_rows( 'users', [ 'user_status' => 0 ] );
```

## API Reference

### Database Class

| Method                                              | Description                         |
|-----------------------------------------------------|-------------------------------------|
| `table_exists($table)`                              | Check if a table exists             |
| `column_exists($table, $column)`                    | Check if a column exists in a table |
| `index_exists($table, $index_name)`                 | Check if an index exists on a table |
| `value_exists($table, $column, $value)`             | Check if a value exists in a column |
| `get_value($table, $column, $where)`                | Get a single value                  |
| `get_row($table, $where)`                           | Get a single row as object          |
| `get_rows($table, $where, $order, $limit, $offset)` | Get multiple rows                   |
| `count_rows($table, $where)`                        | Count rows with optional conditions |
| `truncate_table($table, $reset_auto_increment)`     | Remove all rows from a table        |
| `get_columns($table)`                               | Get all column names for a table    |
| `get_table($object_type)`                           | Get table name for object type      |
| `get_meta_table($meta_type)`                        | Get meta table name                 |
| `get_prefix()`                                      | Get database table prefix           |
| `get_charset_collate()`                             | Get charset collate string          |

### Builder Class

| Method                                                             | Description                                   |
|--------------------------------------------------------------------|-----------------------------------------------|
| `like_pattern($pattern, $type)`                                    | Generate SQL LIKE pattern                     |
| `placeholders($values, $type)`                                     | Generate placeholders for prepared statements |
| `placeholders_with_params($values, &$params, $type)`               | Generate placeholders and collect params      |
| `in_clause($column, $values, $not)`                                | Generate IN/NOT IN clause                     |
| `safe_in_clause($column, $values, &$params, $not, $type)`          | Safe IN clause with params                    |
| `like_clause($column, $value, $type)`                              | Generate LIKE clause                          |
| `safe_like_clause($column, $value, &$params, $type)`               | Safe LIKE clause with params                  |
| `between_clause($column, $min, $max)`                              | Generate BETWEEN clause                       |
| `safe_between_clause($column, $min, $max, &$params, $type)`        | Safe BETWEEN with params                      |
| `condition($column, $value, $operator, $data_type)`                | Generate condition                            |
| `safe_condition($column, $value, &$params, $operator, $data_type)` | Safe condition with params                    |
| `where_clause($conditions, $operator)`                             | Generate WHERE clause                         |
| `order_by_clause($order_by)`                                       | Generate ORDER BY clause                      |
| `limit_clause($limit, $offset)`                                    | Generate LIMIT clause                         |
| `group_by_clause($columns)`                                        | Generate GROUP BY clause                      |
| `having_clause($conditions, $operator)`                            | Generate HAVING clause                        |
| `date_range_conditions($column, $start, $end, &$params)`           | Build date range conditions                   |
| `date_range_clause($column, $start, $end, &$params, $prefix)`      | Build date range clause                       |
| `select_query($table, $columns, $where, ...)`                      | Build complete SELECT query                   |

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