# Podcast Link Finder for Statamic

A Statamic addon that automatically finds and links podcast episodes across multiple platforms (Spotify, Apple Podcasts, and YouTube) from your Transistor FM podcast.

![Statamic 5.0+](https://img.shields.io/badge/Statamic-5.0+-FF269E?style=flat-square&link=https://statamic.com)
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

## Supported Platforms

- **Spotify** - OAuth authentication with token caching
- **Apple Podcasts** - iTunes Search API integration
- **YouTube** - YouTube Data API v3 with date filtering

## Requirements

- PHP 8.1 or higher
- Statamic 5.0 or higher
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

Add the fieldtype to your collection blueprint (e.g., `resources/blueprints/collections/messages/message.yaml`):

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
- **Statamic Version**: 5.0+
- **PHP Version**: 8.1+
