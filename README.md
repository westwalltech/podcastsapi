# Podcast Link Finder for Statamic

A Statamic addon that automatically finds and links podcast episodes across multiple platforms (Spotify, Apple Podcasts, and YouTube) from your Transistor FM podcast.

![Statamic 6.0+](https://img.shields.io/badge/Statamic-6.0+-FF269E?style=flat-square&link=https://statamic.com)
[![Latest Version](https://img.shields.io/github/v/release/newsong/podcast-link-finder?style=flat-square)](https://github.com/newsong/podcast-link-finder/releases)

## Features

- 🎙 **Transistor FM Integration** - Fetch episodes directly from your Transistor podcast
- 🔍 **Smart Fuzzy Matching** - Automatically finds episodes across platforms using intelligent title matching
- 📊 **Match Scoring** - See confidence scores for each match (e.g., "85% match")
- 🎯 **Manual Selection** - Choose from multiple search results for each platform
- ✏️ **Manual Override** - Enter URLs manually when needed
- 🎨 **Beautiful UI** - Clean, production-ready interface integrated with Statamic's Control Panel
- ⚡ **Auto-Find** - Optionally search platforms automatically when an episode is selected
- 📅 **Date Proximity Scoring** - Boosts match scores for episodes published within 7 days
- 🚀 **GraphQL Support** - Full GraphQL API for headless CMS and mobile app integrations
- 📺 **YouTube Livestream Auto-Fetch** - Automatically fetch scheduled livestream URLs for upcoming Sunday services

## Supported Platforms

- **Spotify** - OAuth authentication with token caching
- **Apple Podcasts** - iTunes Search API integration
- **YouTube** - YouTube Data API v3 with date filtering

## Requirements

- PHP 8.5 or higher
- Statamic 6.0 or higher
- Laravel 12 or higher
- Guzzle HTTP Client 7.0 or higher

## Installation

### Via Composer

```bash
composer require newsong/podcast-link-finder
```

### Manual Installation

1. Copy the addon to your project's `addons` directory:
   ```bash
   cp -r podcast-link-finder /path/to/your-project/addons/newsong/
   ```

2. Add the repository to your project's `composer.json`:
   ```json
   "repositories": [
       {
           "type": "path",
           "url": "addons/newsong/podcast-link-finder"
       }
   ]
   ```

3. Require the package:
   ```bash
   composer require newsong/podcast-link-finder
   ```

## Configuration

### 1. Environment Variables

Add your API credentials to your `.env` file:

```env
# Transistor FM (Required)
TRANSISTOR_API_KEY=your_transistor_api_key_here

# Spotify (Required)
SPOTIFY_CLIENT_ID=your_spotify_client_id
SPOTIFY_CLIENT_SECRET=your_spotify_client_secret
SPOTIFY_SHOW_ID=your_spotify_show_id

# YouTube (Required)
YOUTUBE_API_KEY=your_youtube_api_key
YOUTUBE_CHANNEL_ID=your_youtube_channel_id

# Apple Podcasts (Required)
APPLE_PODCASTS_SHOW_ID=1039720149
```

### 2. Getting API Credentials

#### Transistor FM
1. Log in to your Transistor account
2. Go to Settings → API Keys
3. Generate a new API key

#### Spotify
1. Go to [Spotify Developer Dashboard](https://developer.spotify.com/dashboard)
2. Create a new app
3. Note your Client ID and Client Secret
4. Find your Show ID from your podcast's Spotify URL: `https://open.spotify.com/show/[SHOW_ID]`

#### YouTube
1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Create a new project
3. Enable the YouTube Data API v3
4. Create credentials (API Key)
5. Find your Channel ID from your channel URL or About page

#### Apple Podcasts
1. Go to [Apple Podcasts Connect](https://podcastsconnect.apple.com/)
2. Find your podcast
3. Your Show ID is in the URL: `https://podcasts.apple.com/podcast/id[SHOW_ID]`

### 3. Publish Configuration (Optional)

Publish the config file to customize settings:

```bash
php please vendor:publish --tag=podcast-link-finder-config
```

Edit `config/podcast-link-finder.php` to customize:

```php
return [
    'search' => [
        'fuzzy_match_threshold' => 0.6,  // 60% similarity required
        'date_range_days' => 14,          // ±7 days from publish date
        'max_results' => 20,              // Max episodes to fetch
    ],
];
```

## Usage

### 1. Add to Blueprint

You have two options for adding the fieldtype to your blueprints:

#### Option A: Use the Provided Fieldset (Recommended)

Publish the included fieldset:

```bash
php please vendor:publish --tag=podcast-link-finder-fieldsets
```

Then import it in your blueprint:

```yaml
fields:
  -
    import: podcast-link-finder::podcast_episode
```

The fieldset will be available at `resources/fieldsets/vendor/podcast-link-finder/podcast_episode.yaml` and can be imported using the `podcast-link-finder::podcast_episode` handle.

#### Option B: Add the Field Manually

Add the fieldtype directly to your collection blueprint (e.g., `resources/blueprints/collections/messages/message.yaml`):

```yaml
fields:
  - handle: podcast_links
    field:
      type: podcast_link_finder
      display: 'Podcast Episode Links'
      instructions: 'Search for your episode from Transistor and select platform links'
      auto_find: true
      allow_manual_override: true
```

### 2. In the Control Panel

1. Create or edit an entry
2. Search for your episode from Transistor
3. Select an episode from the dropdown
4. The addon will automatically search all platforms
5. Review and select the correct match for each platform
6. Save your entry

### 3. In Your Templates

Access the links in your Antlers templates:

```antlers
{{ podcast_links }}
  {{ if youtube:has_link }}
    <a href="{{ youtube:url }}" target="_blank">Watch on YouTube</a>
  {{ /if }}

  {{ if spotify:has_link }}
    <a href="{{ spotify:url }}" target="_blank">Listen on Spotify</a>
  {{ /if }}

  {{ if apple_podcasts:has_link }}
    <a href="{{ apple_podcasts:url }}" target="_blank">Listen on Apple Podcasts</a>
  {{ /if }}
{{ /podcast_links }}
```

### Available Template Variables

```antlers
{{ podcast_links }}
  {{ episode_id }}           # Transistor episode ID
  {{ episode_title }}        # Episode title

  {{ youtube:url }}          # YouTube video URL
  {{ youtube:has_link }}     # Boolean - true if URL exists

  {{ spotify:url }}          # Spotify episode URL
  {{ spotify:has_link }}     # Boolean - true if URL exists

  {{ apple_podcasts:url }}   # Apple Podcasts episode URL
  {{ apple_podcasts:has_link }} # Boolean - true if URL exists

  {{ has_any_links }}        # Boolean - true if any platform link exists
{{ /podcast_links }}
```

## GraphQL API Usage

The addon includes full GraphQL support for headless CMS and mobile app integrations.

### GraphQL Schema

```graphql
type PodcastLinks {
  episode_id: String
  episode_title: String
  spotify: PlatformLink
  apple_podcasts: PlatformLink
  youtube: PlatformLink
  has_any_links: Boolean!
}

type PlatformLink {
  url: String
  has_link: Boolean!
}
```

### Example Queries

**Query a single entry:**

```graphql
query {
  entry(id: "entry-id-here") {
    ... on Entry_Messages_Message {
      id
      title
      podcast_links {
        episode_id
        episode_title
        spotify {
          url
          has_link
        }
        apple_podcasts {
          url
          has_link
        }
        youtube {
          url
          has_link
        }
        has_any_links
      }
    }
  }
}
```

**Query a collection:**

```graphql
query {
  entries(collection: "messages", limit: 10) {
    data {
      ... on Entry_Messages_Message {
        id
        title
        podcast_links {
          episode_title
          spotify {
            url
            has_link
          }
          apple_podcasts {
            url
            has_link
          }
          youtube {
            url
            has_link
          }
        }
      }
    }
  }
}
```

**Response Example:**

```json
{
  "data": {
    "entry": {
      "id": "123",
      "title": "Answer The Call - We Make Space",
      "podcast_links": {
        "episode_id": "2836688",
        "episode_title": "Answer The Call - We Make Space",
        "spotify": {
          "url": "https://open.spotify.com/episode/6RnlzXMXyUXVGNgPnL7nTc",
          "has_link": true
        },
        "apple_podcasts": {
          "url": "https://podcasts.apple.com/podcast/id1039720149?i=1000676321763",
          "has_link": true
        },
        "youtube": {
          "url": "https://www.youtube.com/watch?v=dQw4w9WgXcQ",
          "has_link": true
        },
        "has_any_links": true
      }
    }
  }
}
```

## Fieldtype Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `auto_find` | boolean | `true` | Automatically search platforms when episode selected |
| `allow_manual_override` | boolean | `true` | Allow manual URL entry |

## How It Works

### Fuzzy Matching Algorithm

The addon uses PHP's `similar_text()` function to calculate similarity between episode titles:

1. **Text Comparison** - Compares episode titles character by character
2. **Similarity Threshold** - Only matches above 60% similarity are considered (configurable)
3. **Date Proximity Boost** - Episodes published within 7 days get a 20% score boost
4. **Best Match Selection** - Results are sorted by score, with highest matches shown first

### Search Process

1. User selects episode from Transistor
2. Addon fetches episode title and publish date
3. Searches each platform's API for matching episodes
4. Calculates similarity scores for all results
5. Returns ranked list of matches for user selection
6. User confirms or changes selections
7. Links are saved with the entry

## Artisan Commands

### Bulk Update Links

Automatically find and add podcast platform links to all existing entries in a collection:

```bash
php artisan podcast:bulk-update {collection} [options]
```

**Options:**

- `--field=podcast_links` - The field handle containing podcast links (default: podcast_links)
- `--dry-run` - Preview changes without saving
- `--only-empty` - Only update entries without existing links
- `--force-youtube` - Search YouTube even if not an allowed search day
- `--platforms=spotify,apple,youtube` - Only search specific platforms
- `--limit=10` - Limit number of entries to process

**Examples:**

```bash
# Dry run to see what would be updated
php artisan podcast:bulk-update messages --dry-run

# Update all messages, only searching Spotify and Apple
php artisan podcast:bulk-update messages --platforms=spotify,apple

# Only update entries that don't have links yet
php artisan podcast:bulk-update messages --only-empty

# Force YouTube search even on non-Sunday (uses quota)
php artisan podcast:bulk-update messages --force-youtube

# Process only first 5 entries
php artisan podcast:bulk-update messages --limit=5
```

**How it works:**

1. Fetches all entries from the specified collection
2. Matches each entry to a Transistor episode by title (60%+ similarity required)
3. Searches each platform for the matched episode
4. Updates the podcast_links field with found URLs
5. Respects YouTube search day restrictions (unless --force-youtube is used)
6. Shows progress bar and summary report

**Output:**

```
Bulk updating collection: messages
Found 45 entries to process

Fetching episodes from Transistor...
Loaded 100 episodes from Transistor

⚠️  YouTube search restricted (not Sunday). Use --force-youtube to override.

 45/45 [▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓▓] 100% - Complete!

Summary:
  ✓ Updated: 42 entries
  ⏭ Skipped: 3 entries (no match)

  📊 YouTube quota used: 0 units
```

### Test YouTube API

Diagnose YouTube API connection issues:

```bash
php artisan podcast:test-youtube [--title="search term"]
```

This command tests your YouTube API configuration and displays detailed error messages if there are issues with quota, permissions, or connectivity.

### Auto-Update (Scheduled Task)

Automatically update podcast platform links for recent entries on a schedule:

```bash
php artisan podcast:auto-update
```

This command is designed to run automatically via Laravel's scheduler. It will:

1. Check entries created/modified in the last 7 days
2. Find missing platform links (Spotify, Apple Podcasts, YouTube)
3. Search only the missing platforms
4. Update entries automatically
5. Log all activity to Laravel logs

**Setup:**

1. **Configure the scheduler** - Add to your server's crontab:

```bash
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

2. **Configure auto-update** - Edit your `.env` file:

```env
# Enable/disable auto-update
PODCAST_AUTO_UPDATE_ENABLED=true

# Which collection to update
PODCAST_AUTO_UPDATE_COLLECTION=messages

# Field handle for podcast links
PODCAST_AUTO_UPDATE_FIELD=podcast_links
```

3. **Customize schedule** (optional) - Edit `config/podcast-link-finder.php`:

```php
'auto_update' => [
    'enabled' => env('PODCAST_AUTO_UPDATE_ENABLED', true),
    'collection' => env('PODCAST_AUTO_UPDATE_COLLECTION', 'messages'),
    'field' => env('PODCAST_AUTO_UPDATE_FIELD', 'podcast_links'),
    'days_lookback' => 7, // Only check recent entries
    'schedule' => [
        'day' => 'tuesdays', // Day to run (mondays, tuesdays, etc.)
        'time' => '08:00',   // Time to run (24-hour format)
    ],
],
```

**Default Schedule:** Every Tuesday at 8:00 AM

**Manual Execution:**

```bash
# Run now (respects enabled config)
php artisan podcast:auto-update

# Force run even if disabled
php artisan podcast:auto-update --force

# Test with verbose output
php artisan podcast:auto-update --force -v
```

**Viewing Logs:**

```bash
# View recent logs
tail -f storage/logs/laravel.log | grep "Podcast auto-update"

# View today's auto-update logs
grep "Podcast auto-update" storage/logs/laravel-$(date +%Y-%m-%d).log
```

**Example Log Output:**

```
[2024-10-29 08:00:00] INFO: Podcast auto-update started {"collection":"messages","days_lookback":7}
[2024-10-29 08:00:01] INFO: Found 8 entries from last 7 days
[2024-10-29 08:00:05] INFO: Updated: Looking Unto Jesus - Gospel Living {"added_platforms":"YouTube, Spotify"}
[2024-10-29 08:00:08] INFO: Updated: Looking Unto Jesus - Union with Christ {"added_platforms":"Apple Podcasts"}
[2024-10-29 08:00:10] INFO: Skipped: Looking Unto Jesus - Created to Flourish - All platforms already present
[2024-10-29 08:00:15] INFO: Podcast auto-update completed {"processed":8,"updated":5,"skipped":3,"errors":0,"youtube_quota_used":500}
[2024-10-29 08:00:15] INFO: Updated entries: [{"title":"Gospel Living","platforms":["YouTube","Spotify"]},...]
```

**YouTube Quota Management:**

- Auto-update runs on **Tuesdays by default** (configurable)
- YouTube search is **enabled on Sundays and Tuesdays**
- Only searches platforms that are **actually missing** (efficient quota usage)
- Logs quota usage for tracking

## Development

### Building Assets

The addon uses Vite for asset compilation:

```bash
# Install dependencies
pnpm install

# Development mode with hot reload
pnpm run dev

# Production build
pnpm run build
```

### Running Tests

```bash
composer test
```

## Troubleshooting

### "Failed to load episodes"
- Check your `TRANSISTOR_API_KEY` in `.env`
- Verify the API key is valid in Transistor settings

### "No matches found" for a platform
- Episode titles may be too different (try lowering `fuzzy_match_threshold`)
- Episode may not be published on that platform yet
- API credentials may be incorrect
- Try manual URL entry

### Spotify OAuth errors
- Verify `SPOTIFY_CLIENT_ID` and `SPOTIFY_CLIENT_SECRET`
- Check that your Spotify app is not in development mode restrictions

### YouTube API quota exceeded
- YouTube has daily quota limits (10,000 units/day for free tier)
- Each search costs 100 units
- Consider caching results or upgrading quota

## YouTube Livestream Auto-Fetch

Automatically fetch scheduled YouTube livestream URLs for upcoming Sunday services. This feature matches entries by their `air_date` field and finds the corresponding scheduled livestream.

### Features

- **Scheduled Auto-Fetch** - Runs automatically on Sundays at 8:00 AM and 9:45 AM Central Time
- **Manual Fetch Button** - Fetch livestream URL directly from the Control Panel
- **Smart Matching** - Matches entries to livestreams by date
- **URL Validation** - Checks if existing URLs are still valid before replacing
- **Dark Mode Support** - Full dark mode UI integration

### Configuration

Add to your `.env` file:

```env
# Enable/disable livestream fetch
YOUTUBE_LIVESTREAM_ENABLED=true

# YouTube API credentials (if not already configured)
YOUTUBE_API_KEY=your_youtube_api_key
YOUTUBE_CHANNEL_ID=your_youtube_channel_id
# OR use channel handle
YOUTUBE_CHANNEL_HANDLE=@newsongchurchokc
```

Publish the config file for more options:

```bash
php please vendor:publish --tag=podcast-link-finder-youtube-livestream-config
```

Edit `config/youtube-livestream.php`:

```php
return [
    'enabled' => env('YOUTUBE_LIVESTREAM_ENABLED', true),

    // Schedule configuration
    'schedule' => [
        'times' => ['08:00', '09:45'],
        'timezone' => 'America/Chicago',
    ],

    // Collection & field configuration
    'collection' => 'messages',
    'date_field' => 'air_date',
    'url_field' => 'youtube_url',
    'fetched_at_field' => 'youtube_fetched_at',

    // Matching configuration
    'matching' => [
        'sunday_only' => true,
        'prefer_earliest' => true,
        'days_ahead' => 7,
    ],

    // Overwrite behavior
    'overwrite' => [
        'validate_existing' => true,
        'replace_invalid' => true,
        'preserve_valid' => true,
    ],
];
```

### Artisan Command

Manually fetch livestream URLs:

```bash
# Fetch for next Sunday
php artisan newsong:fetch-youtube-livestreams

# Fetch for a specific date
php artisan newsong:fetch-youtube-livestreams --date=2024-12-25

# Preview without saving
php artisan newsong:fetch-youtube-livestreams --dry-run

# Force run even if disabled
php artisan newsong:fetch-youtube-livestreams --force
```

### Control Panel Usage

When editing an entry with an `air_date` set to an upcoming Sunday:

1. The fieldtype will show a "Fetch YouTube Livestream" button
2. Click to fetch the scheduled livestream URL
3. The URL will be automatically saved to the entry

### How It Works

1. Checks the entry's `air_date` field
2. Queries YouTube Data API for upcoming livestreams
3. Matches livestream by scheduled start date (converted to configured timezone)
4. If multiple livestreams are scheduled, picks the earliest one
5. Validates existing URLs before replacing (preserves valid URLs)
6. Updates the `youtube_url` field and tracks `youtube_fetched_at` timestamp

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for recent changes.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## Security

If you discover any security-related issues, please email security@newsongchurch.org instead of using the issue tracker.

## Credits

- [New Song Church](https://newsongchurch.org)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Support

- **Issues**: [GitHub Issues](https://github.com/newsong/podcast-link-finder/issues)
- **Statamic Version**: 6.0+
- **PHP Version**: 8.5+
