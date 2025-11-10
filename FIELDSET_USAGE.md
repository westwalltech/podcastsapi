# Using the Podcast Episode Fieldset

This addon provides a reusable fieldset that can be easily imported into any Statamic blueprint.

## Quick Start

### 1. Publish the Fieldset (First Time Only)

When you install the addon in a new project, publish the fieldset:

```bash
php please vendor:publish --tag=podcast-link-finder-fieldsets
```

This copies the fieldset to `resources/fieldsets/vendor/podcast-link-finder/podcast_episode.yaml`

### 2. Import in Your Blueprint

In any collection or entry blueprint, add the import:

```yaml
fields:
  -
    import: podcast-link-finder::podcast_episode
```

That's it! The fieldset includes the `podcast_links` field with all recommended settings:
- `auto_find: true` - Automatically searches platforms when episode selected
- `allow_manual_override: true` - Allows manual URL editing
- Icon and instructions pre-configured

## Example: Adding to a Collection Blueprint

**File**: `resources/blueprints/collections/sermons/sermon.yaml`

```yaml
title: Sermon
tabs:
  main:
    display: Main
    sections:
      -
        fields:
          -
            handle: title
            field:
              type: text
              required: true
          -
            handle: date
            field:
              type: date
              required: true
          -
            import: podcast-link-finder::podcast_episode
```

## Customizing the Fieldset

If you need to customize the field settings for your project:

1. After publishing, edit the fieldset at `resources/fieldsets/vendor/podcast-link-finder/podcast_episode.yaml`
2. Modify the field configuration as needed
3. Your changes will be preserved (the file won't be overwritten on future publishes unless you use `--force`)

### Example Customization

```yaml
title: 'Podcast Episode'
fields:
  -
    handle: podcast_links
    field:
      type: podcast_link_finder
      display: 'Message Audio Links'  # Custom display name
      instructions: 'Custom instructions here'
      icon: microphone  # Different icon
      listable: visible  # Make it visible in listings
      auto_find: false  # Disable auto-search
      allow_manual_override: true
```

## Alternative: Manual Field Definition

If you prefer not to use the fieldset, you can add the field directly to your blueprint:

```yaml
fields:
  -
    handle: podcast_links
    field:
      type: podcast_link_finder
      display: 'Podcast Episode Links'
      instructions: 'Search for your episode from Transistor'
      auto_find: true
      allow_manual_override: true
```

## Verifying Installation

Check that the fieldset is available:

```bash
php artisan tinker --execute="echo Statamic\Facades\Fieldset::all()->keys()->join(PHP_EOL);"
```

You should see `podcast-link-finder::podcast_episode` in the output.
