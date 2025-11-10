# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Statamic 5 CMS project that includes a custom Statamic addon called "Podcast Link Finder". The project is built on Laravel 12 and includes the addon as a local package located in `addons/newsong/podcast-link-finder`.

## Architecture

### Dual Repository Structure

This repository uses a **nested addon architecture**:

- **Main Project**: Standard Statamic installation at the root
- **Podcast Link Finder Addon**: Complete standalone package located at `addons/newsong/podcast-link-finder/`
- **Local Composer Repository**: The addon is loaded via a path repository configuration in the root `composer.json`

The addon has its own `composer.json`, `package.json`, `vendor/`, and `node_modules/` directories. Changes to the addon should be made in the `addons/newsong/podcast-link-finder/` directory.

### Podcast Link Finder Addon

The addon provides a custom Statamic fieldtype that integrates with multiple podcast platforms:

- **Services Layer** (`addons/newsong/podcast-link-finder/src/Services/`):
  - `TransistorService.php`: Fetches episodes from Transistor FM API
  - `SpotifyService.php`: Searches Spotify with OAuth token caching
  - `ApplePodcastsService.php`: Searches iTunes/Apple Podcasts API
  - `YouTubeService.php`: Searches YouTube Data API v3 with quota management

- **Fieldtype** (`addons/newsong/podcast-link-finder/src/Fieldtypes/PodcastLinkFinder.php`):
  - Custom Vue.js-based control panel field
  - Handles fuzzy matching between Transistor episodes and platform-specific episodes
  - Uses similarity scoring with date proximity boost (60% threshold, ±7 days)

- **Console Commands** (`addons/newsong/podcast-link-finder/src/Console/Commands/`):
  - `BulkUpdateLinksCommand.php`: Batch process existing entries to add platform links
  - `AutoUpdateLinksCommand.php`: Scheduled task for automatic link updates
  - `TestYouTubeCommand.php`: YouTube API diagnostics

- **API Controller** (`addons/newsong/podcast-link-finder/src/Http/Controllers/PodcastSearchController.php`):
  - AJAX endpoints for platform searches called from the fieldtype

## Essential Commands

### Project Setup

```bash
# Initial setup (runs composer install, npm install, builds assets, migrations)
composer setup

# Start development environment (server, queue, logs, vite)
composer dev
```

### Development Workflow

```bash
# Install PHP dependencies
composer install

# Install JS dependencies (uses pnpm)
pnpm install

# Build frontend assets
npm run build

# Watch and rebuild assets during development
npm run dev

# Start Laravel server
php artisan serve

# View logs in real-time
php artisan pail
```

### Testing

```bash
# Run PHPUnit tests
composer test
# or
php artisan test

# Run tests for the addon specifically
cd addons/newsong/podcast-link-finder
composer test
```

### Statamic-Specific Commands

```bash
# Access Statamic CLI
php please

# Create a new user
php please make:user

# Clear caches
php please cache:clear
php please static:clear

# Update Statamic
php please update
```

### Addon Development

```bash
# Navigate to addon directory
cd addons/newsong/podcast-link-finder

# Install addon dependencies
composer install
pnpm install

# Build addon assets (Vite)
pnpm run build

# Watch addon assets during development
pnpm run dev

# The addon uses Vite with input at resources/js/addon.js
# Output goes to resources/dist/
```

### Publishing Addon Resources

```bash
# Publish the podcast episode fieldset
php please vendor:publish --tag=podcast-link-finder-fieldsets

# Publish addon configuration (optional)
php please vendor:publish --tag=podcast-link-finder-config
```

### Podcast Link Finder Commands

```bash
# Bulk update podcast links for a collection
php artisan podcast:bulk-update messages

# Dry run to preview changes
php artisan podcast:bulk-update messages --dry-run

# Only update entries without existing links
php artisan podcast:bulk-update messages --only-empty

# Search only specific platforms
php artisan podcast:bulk-update messages --platforms=spotify,apple

# Force YouTube search (normally restricted to Sundays/Tuesdays)
php artisan podcast:bulk-update messages --force-youtube

# Test YouTube API configuration
php artisan podcast:test-youtube

# Manually run auto-update (normally scheduled)
php artisan podcast:auto-update

# Force auto-update even if disabled
php artisan podcast:auto-update --force
```

## Configuration

### Environment Variables

Required API credentials (see `.env.example`):

```env
TRANSISTOR_API_KEY=your_transistor_api_key
SPOTIFY_CLIENT_ID=your_spotify_client_id
SPOTIFY_CLIENT_SECRET=your_spotify_client_secret
SPOTIFY_SHOW_ID=your_spotify_show_id
YOUTUBE_API_KEY=your_youtube_api_key
YOUTUBE_CHANNEL_ID=your_youtube_channel_id
APPLE_PODCASTS_SHOW_ID=your_apple_show_id
```

### Addon Configuration

Edit `addons/newsong/podcast-link-finder/config/podcast-link-finder.php`:

- `fuzzy_threshold`: Minimum similarity score (0-1) for episode matching
- `date_range_days`: How many days before/after to search
- `youtube.search_days`: Days when YouTube search is allowed (quota management)
- `auto_update`: Configuration for scheduled automatic link updates

## Content Structure

- **Collections**: Located in `content/collections/`
- **Blueprints**: Located in `resources/blueprints/`
- **Fieldsets**: Main project fieldsets in `resources/fieldsets/`, addon-provided fieldsets in `resources/fieldsets/vendor/`
- **Globals**: Located in `content/globals/`
- **Assets**: Located in `content/assets/`
- **Users**: Located in `users/`

### Using the Podcast Episode Fieldset

The addon provides a reusable fieldset that can be imported into any blueprint:

1. Publish the fieldset: `php please vendor:publish --tag=podcast-link-finder-fieldsets`
2. Import in your blueprint YAML:
   ```yaml
   fields:
     -
       import: podcast-link-finder::podcast_episode
   ```

The fieldset includes the `podcast_links` field pre-configured with recommended settings.

## Important Considerations

### YouTube API Quota Management

The YouTube Data API has strict quota limits (10,000 units/day, 100 units per search):

- YouTube searches are restricted to Sundays and Tuesdays by default
- Use `--force-youtube` flag cautiously with bulk operations
- The auto-update command is scheduled for Tuesdays to conserve quota
- Check quota usage logs when troubleshooting

### Fuzzy Matching Algorithm

Episode matching uses PHP's `similar_text()` with:

- 60% similarity threshold (configurable)
- 20% score boost for episodes within 7 days of publish date
- Best match selection from ranked results

### Scheduled Tasks

The addon registers a scheduled task for `podcast:auto-update`:

- Default: Every Tuesday at 8:00 AM
- Only processes entries from last 7 days
- Only searches platforms with missing links
- Configure via `config/podcast-link-finder.php`

Make sure your server crontab is configured:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Testing the Addon

When making changes to the addon:

1. Rebuild addon assets: `cd addons/newsong/podcast-link-finder && pnpm run build`
2. Clear Laravel caches: `php artisan cache:clear`
3. Test in control panel by creating/editing an entry with the `podcast_link_finder` field
4. Check browser console for Vue.js errors
5. Monitor Laravel logs: `php artisan pail` or `tail -f storage/logs/laravel.log`

## Template Usage

Access podcast links in Antlers templates:

```antlers
{{ podcast_links }}
  {{ if youtube:has_link }}
    <a href="{{ youtube:url }}">Watch on YouTube</a>
  {{ /if }}
  {{ if spotify:has_link }}
    <a href="{{ spotify:url }}">Listen on Spotify</a>
  {{ /if }}
  {{ if apple_podcasts:has_link }}
    <a href="{{ apple_podcasts:url }}">Listen on Apple Podcasts</a>
  {{ /if }}
{{ /podcast_links }}
```

## Platform-Specific Notes

- **macOS**: Uses pnpm for package management
- **Database**: SQLite (located at `database/database.sqlite`)
- **Git Branch**: Main branch is `main`, current development on `develop`
- **PHP Version**: Requires PHP 8.2+
- **Node**: Managed via pnpm lockfile
