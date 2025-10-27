# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.5] - 2024-10-27

### Added
- New artisan command `php artisan podcast:bulk-update` for batch updating podcast links across all entries in a collection
- Bulk update features:
  - Automatically matches existing entries to Transistor episodes by title
  - Searches all platforms (Spotify, Apple Podcasts, YouTube) for each matched episode
  - Updates podcast_links field with found URLs
  - Progress bar with real-time status updates
  - Comprehensive summary report (updated, skipped, errors, quota usage)
- Command options:
  - `--dry-run` - Preview changes without saving
  - `--only-empty` - Only update entries without existing links
  - `--force-youtube` - Search YouTube even on restricted days
  - `--platforms` - Only search specific platforms
  - `--limit` - Limit number of entries to process
- Fuzzy matching algorithm to pair Statamic entries with Transistor episodes (60%+ similarity threshold)
- Respects YouTube search day restrictions unless overridden with --force-youtube flag

### Changed
- ServiceProvider now registers BulkUpdateLinksCommand

### Fixed
- Collection validation now correctly uses collection handles instead of numeric indices

## [1.0.4] - 2024-10-27

### Added
- Configurable search days restriction for YouTube to conserve API quota (defaults to Sundays only)
- New artisan command `php artisan podcast:test-youtube` for diagnosing YouTube API issues
- Detailed error logging that captures YouTube API error reasons (quotaExceeded, forbidden, etc.)
- Helper methods `isSearchAllowedToday()` and `getSearchRestrictionMessage()` in YouTubeService

### Changed
- YouTube search now respects configured `search_days` to prevent quota exhaustion
- Improved error messages to show specific YouTube API quota and permission issues
- Better distinction between API errors and day restrictions in user-facing messages

### Fixed
- YouTube API quota management - can now restrict searches to specific days to stay within free tier limits

## [1.0.3] - 2024-10-26

### Changed
- Updated platform logos to use official brand colors (YouTube red, Spotify green, Apple Podcasts purple)
- Improved visual alignment of platform icons and text in Control Panel UI

## [1.0.2] - 2024-10-26

### Fixed
- Fixed DateTime modification bug in YouTube date range calculation (was calculating wrong end date)
- YouTube API errors now display helpful messages to users instead of silently failing
- Added API connection testing to detect quota/permission issues

### Changed
- Date range for YouTube search now correctly uses ±7 days from publish date (was ±7/+7 due to bug)
- Improved error handling across all platform searches
- Added warning messages in UI when API calls fail

### Added
- YouTube API connection test method to diagnose authentication issues
- Warning display for each platform when searches fail
- Better logging for YouTube API errors (quota, permissions, bad requests)

## [1.0.1] - 2024-10-26

### Changed
- Increased spacing between platform URL input fields in manual override section for better readability

## [1.0.0] - 2024-10-26

### Added
- Initial release
- Transistor FM integration for fetching podcast episodes
- Spotify integration with OAuth authentication
- Apple Podcasts integration via iTunes Search API
- YouTube integration via YouTube Data API v3
- Fuzzy matching algorithm with configurable threshold
- Date proximity scoring (±7 days boost)
- Match scoring display (percentage match confidence)
- Multi-result selection for each platform
- Manual URL override option
- Auto-find functionality
- Production-ready Control Panel UI with:
  - Color-coded platform cards
  - Result count badges
  - Success checkmarks for selected links
  - Collapsible manual entry section
  - Professional status indicators
- Template augmentation for easy Antlers usage
- Configurable fieldtype options (auto_find, allow_manual_override)
- Comprehensive documentation

### Features
- Search and select episodes from Transistor FM
- Automatically find matching episodes on:
  - Spotify (with 1-hour OAuth token caching)
  - Apple Podcasts (via iTunes Search API)
  - YouTube (with date range filtering)
- View multiple search results per platform
- Select the correct match from dropdown lists
- See match confidence scores for each result
- Enter URLs manually when needed
- Save selected links with entries
- Access links in Antlers templates

### Technical
- PHP 8.1+ support
- Statamic 5.0+ compatibility
- Guzzle HTTP client for API requests
- Vue 2.7.16 for Control Panel UI
- Vite 5 for asset compilation
- Tailwind CSS for styling
