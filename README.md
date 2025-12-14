# P2P to Taxonomy Migration

A WordPress plugin that facilitates the migration of People to People (P2P) data to WordPress Taxonomy structures.

## Overview

This plugin provides tools to help developers migrate their WordPress projects that use the [Posts to Posts (P2P)](https://github.com/scribu/wp-posts-to-posts) plugin to use native WordPress taxonomies instead. This migration path offers improved performance, better SEO support, and deeper integration with WordPress core features.

## Features

- **Automated Data Migration**: Convert P2P relationships to taxonomy terms
- **Data Integrity**: Ensure no data loss during migration
- **Rollback Support**: Safely revert migrations if needed
- **Batch Processing**: Handle large datasets efficiently
- **Progress Tracking**: Monitor migration progress in real-time
- **Detailed Logging**: Comprehensive logs for debugging and auditing

## Requirements

- WordPress 5.0 or higher
- PHP 7.2 or higher
- Posts to Posts (P2P) plugin installed and active (for source data)

## Installation

1. Download the plugin or clone the repository into your WordPress plugins directory:
   ```bash
   git clone https://github.com/carstingaxion/p2p-to-taxonomy-migration.git wp-content/plugins/p2p-to-taxonomy-migration
   ```

2. Activate the plugin through the WordPress admin panel or via WP-CLI:
   ```bash
   wp plugin activate p2p-to-taxonomy-migration
   ```

## Usage

### Admin Interface

Once activated, the plugin adds a new menu item under **Tools** > **P2P to Taxonomy Migration**:

1. Navigate to the migration tool in your WordPress admin
2. Select the P2P relationships you want to migrate
3. Map P2P post types to target taxonomies
4. Configure migration options
5. Run the migration in batch mode
6. Monitor progress and review logs

### WP-CLI Commands

The plugin provides WP-CLI commands for command-line migration:

```bash
# List available P2P relationships
wp p2p-migration list-relationships

# Start a migration
wp p2p-migration migrate --relationship=<relationship_key> --taxonomy=<taxonomy_name>

# Check migration status
wp p2p-migration status

# Rollback a migration
wp p2p-migration rollback --relationship=<relationship_key>
```

## Configuration

### Migration Settings

Settings can be configured in the admin interface or via hooks:

```php
// Set maximum items to process per batch
add_filter( 'p2p_migration_batch_size', function() {
    return 100;
} );

// Customize taxonomy arguments for created taxonomies
add_filter( 'p2p_migration_taxonomy_args', function( $args, $relationship ) {
    $args['show_in_rest'] = true;
    return $args;
}, 10, 2 );
```

## Database Structure

The plugin creates:

- **Taxonomies**: One taxonomy per P2P relationship type
- **Terms**: Terms representing related posts
- **Term Relationships**: Native WordPress term_taxonomy relationships

Migration data is stored in post meta for reference:
- `_p2p_migration_source_relationship`: Original P2P relationship key
- `_p2p_migration_status`: Migration status

## Hooks

### Filters

- `p2p_migration_batch_size` - Modify batch processing size
- `p2p_migration_taxonomy_args` - Customize taxonomy creation arguments
- `p2p_migration_skip_post` - Skip specific posts during migration
- `p2p_migration_term_args` - Modify term creation arguments

### Actions

- `p2p_migration_before_start` - Fires before migration begins
- `p2p_migration_batch_complete` - Fires after each batch completes
- `p2p_migration_complete` - Fires when migration completes
- `p2p_migration_error` - Fires when an error occurs

## Troubleshooting

### Migration is slow
- Reduce the batch size for more frequent saves
- Run migration during off-peak hours
- Check database for locks and optimize indexes

### Data appears to be missing
- Check the migration logs for errors
- Verify P2P plugin is still active and data is accessible
- Run the validation tool to check data integrity

### Rollback failed
- Ensure taxonomy and terms haven't been modified manually
- Check database permissions
- Review error logs for specific issues

## Logging

All migration operations are logged to:
- WordPress debug log (if `WP_DEBUG_LOG` is enabled)
- Plugin-specific log file: `/wp-content/plugins/p2p-to-taxonomy-migration/logs/`

View logs in the admin interface under **Tools** > **P2P to Taxonomy Migration** > **Logs**

## Development

### Getting Started

1. Clone the repository
2. Install dependencies (if any)
3. Create a feature branch for your changes
4. Make your changes and test thoroughly
5. Submit a pull request

### Code Standards

- Follow WordPress Coding Standards
- Use appropriate namespacing
- Include PHPDoc comments for functions
- Write unit tests for new functionality

### Testing

```bash
# Run unit tests
npm run test

# Run code quality checks
npm run lint
```

## Migration Best Practices

1. **Backup First**: Always backup your database before running migrations
2. **Test in Staging**: Run migrations on a staging environment first
3. **Monitor Performance**: Check server resources during migration
4. **Verify Results**: Validate that all data migrated correctly
5. **Keep P2P Intact**: Don't delete P2P plugin until validation is complete
6. **Document Changes**: Keep notes on what was migrated and when

## Support

For issues, questions, or suggestions:
- Open an issue on [GitHub](https://github.com/carstingaxion/p2p-to-taxonomy-migration/issues)
- Check existing documentation and FAQs
- Review migration logs for specific errors

## License

This plugin is released under the [GPL v2 or later](LICENSE) license.

## Changelog

### Version 1.0.0
- Initial release
- Basic P2P to taxonomy migration functionality
- Admin interface and WP-CLI support
- Batch processing and progress tracking

## Credits

Developed by [carstingaxion](https://github.com/carstingaxion)

## Disclaimer

This plugin makes significant changes to your database structure. Always test thoroughly on a staging environment before running migrations on production databases. The authors are not responsible for data loss or unexpected behavior resulting from this plugin's use.
