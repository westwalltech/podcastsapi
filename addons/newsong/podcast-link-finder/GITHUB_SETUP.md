# GitHub Setup Instructions

This guide will help you set up this addon on GitHub and make it installable in other Statamic projects.

## 1. Initialize Git Repository

Navigate to the addon directory and initialize git:

```bash
cd /Users/davidatkins/Projects/Sites/podcast-link-finder-dev/addons/newsong/podcast-link-finder
git init
```

## 2. Create GitHub Repository

1. Go to [GitHub](https://github.com) and create a new repository
2. Name it: `podcast-link-finder`
3. Description: "Automatically find and link podcast episodes from Transistor to Spotify, Apple Podcasts, and YouTube"
4. Choose: **Public** (for open source) or **Private**
5. Do **NOT** initialize with README, .gitignore, or license (we already have these)
6. Click "Create repository"

## 3. Add Files to Git

```bash
# Add all files
git add .

# Create initial commit
git commit -m "Initial release v1.0.0

- Transistor FM integration
- Spotify, Apple Podcasts, and YouTube search
- Fuzzy matching with scoring
- Multi-result selection UI
- Manual override option
- Production-ready Control Panel interface"
```

## 4. Push to GitHub

Replace `YOUR_USERNAME` with your actual GitHub username or organization name:

```bash
# Add remote
git remote add origin https://github.com/YOUR_USERNAME/podcast-link-finder.git

# Push to main branch
git branch -M main
git push -u origin main
```

## 5. Create a Release

1. Go to your repository on GitHub
2. Click "Releases" → "Create a new release"
3. Click "Choose a tag" and create a new tag: `v1.0.0`
4. Release title: `v1.0.0 - Initial Release`
5. Description: Copy the content from CHANGELOG.md for v1.0.0
6. Click "Publish release"

## 6. Submit to Packagist (Optional)

To make it installable via `composer require`:

1. Go to [Packagist.org](https://packagist.org)
2. Sign in with your GitHub account
3. Click "Submit"
4. Enter your repository URL: `https://github.com/YOUR_USERNAME/podcast-link-finder`
5. Click "Check" then "Submit"

Packagist will automatically update when you push new releases to GitHub.

## 7. Installing in Another Project

### Option A: Via Packagist (if published)

```bash
composer require newsong/podcast-link-finder
```

### Option B: Via GitHub (direct)

Add to your project's `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/YOUR_USERNAME/podcast-link-finder"
        }
    ],
    "require": {
        "newsong/podcast-link-finder": "^1.0"
    }
}
```

Then run:

```bash
composer update newsong/podcast-link-finder
```

### Option C: Local Development

For local development/testing, use a path repository:

```json
{
    "repositories": [
        {
            "type": "path",
            "url": "../path/to/podcast-link-finder"
        }
    ],
    "require": {
        "newsong/podcast-link-finder": "*"
    }
}
```

## 8. Post-Installation Steps

After installing in another project:

1. Copy `.env` variables from the development project
2. Add the fieldtype to your blueprint
3. Clear caches: `php artisan cache:clear && php please stache:clear`
4. Test the addon in the Control Panel

## 9. Future Updates

### Creating a New Release

```bash
# Make your changes
git add .
git commit -m "Description of changes"

# Update version in composer.json
# Update CHANGELOG.md

# Create new tag
git tag -a v1.1.0 -m "Version 1.1.0"

# Push changes and tags
git push origin main
git push origin v1.1.0
```

Then create a new GitHub release for the tag.

## 10. Repository Settings

### Recommended GitHub Topics

Add these topics to your repository for better discoverability:

- `statamic`
- `statamic-addon`
- `podcast`
- `transistor`
- `spotify`
- `apple-podcasts`
- `youtube`
- `php`
- `laravel`

### Branch Protection (Optional)

For collaborative projects, set up branch protection:

1. Go to Settings → Branches
2. Add rule for `main` branch
3. Enable:
   - Require pull request reviews before merging
   - Require status checks to pass before merging

## Troubleshooting

### "Package not found" when installing

- Make sure you've pushed the tag to GitHub
- If using Packagist, wait a few minutes for it to update
- Check that composer.json has correct package name

### Built assets not working

- Make sure `/resources/dist/build/` directory contains compiled assets
- Run `pnpm run build` before committing
- Verify .gitignore doesn't exclude built assets

### Composer conflicts

- Check PHP version compatibility (8.1+)
- Check Statamic version compatibility (5.0+)
- Try `composer update --with-dependencies`

## Need Help?

- Open an issue: https://github.com/YOUR_USERNAME/podcast-link-finder/issues
- Read the README: [README.md](README.md)
- Check Statamic Docs: https://statamic.dev/extending/addons
