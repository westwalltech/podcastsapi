# Release Notes - Version 1.1.0

## 🚀 GraphQL Support

We're excited to announce version 1.1.0 of the Podcast Link Finder addon, bringing full **GraphQL API support** for headless CMS implementations and mobile applications!

## What's New

### GraphQL Integration

The addon now exposes podcast links as properly structured GraphQL types instead of serialized strings. This enables:

- **Mobile App Development** - Query podcast links directly from iOS, Android, or React Native apps
- **Headless CMS** - Build frontend applications with any framework (Next.js, Nuxt, etc.)
- **Third-party Integrations** - Connect podcast data to external services via GraphQL

### New GraphQL Types

**PodcastLinks Type:**
```graphql
type PodcastLinks {
  episode_id: String
  episode_title: String
  spotify: PlatformLink
  apple_podcasts: PlatformLink
  youtube: PlatformLink
  has_any_links: Boolean!
}
```

**PlatformLink Type:**
```graphql
type PlatformLink {
  url: String
  has_link: Boolean!
}
```

### Example Query

```graphql
query GetPodcastEpisode {
  entry(id: "your-entry-id") {
    ... on Entry_Messages_Message {
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
```

## Breaking Changes

None! This is a fully backward-compatible update.

## Upgrade Guide

Simply update via Composer:

```bash
composer update newsong/podcast-link-finder
```

Then clear your caches:

```bash
php artisan cache:clear
php please stache:clear
```

## Technical Details

- Automatic GraphQL type registration via ServiceProvider
- Custom `toGqlType()` method in fieldtype for proper serialization
- Zero configuration required - works out of the box
- Compatible with Statamic's GraphQL API and all GraphQL clients

## Previous Features (Still Included)

- ✅ Transistor FM integration
- ✅ Spotify, Apple Podcasts, and YouTube search
- ✅ Smart fuzzy matching with confidence scores
- ✅ Bulk update command for existing entries
- ✅ Automatic scheduled updates
- ✅ Beautiful Control Panel UI
- ✅ Reusable fieldsets
- ✅ Manual override options

## Documentation

Full documentation with GraphQL examples has been added to the README.md

## Thanks

Special thanks to the Statamic community for feedback and support!

---

**Full Changelog**: https://github.com/newsong/podcast-link-finder/blob/main/CHANGELOG.md
