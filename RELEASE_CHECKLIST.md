# Release Checklist - Version 1.1.0

## Pre-Release Verification

### Code Quality
- [x] All new code follows PSR-12 standards
- [x] GraphQL types properly defined and registered
- [x] ServiceProvider updated with GraphQL registration
- [x] Fieldtype includes `toGqlType()` method
- [ ] Run tests: `cd addons/newsong/podcast-link-finder && composer test`
- [ ] Check for PHP errors: `composer check` (if configured)

### Documentation
- [x] README.md updated with GraphQL section
- [x] CHANGELOG.md updated with v1.1.0 changes
- [x] composer.json version bumped to 1.1.0
- [x] GraphQL keyword added to composer.json
- [x] Release notes created (RELEASE_NOTES_v1.1.0.md)
- [x] All code examples tested and verified

### Assets
- [ ] Rebuild production assets: `cd addons/newsong/podcast-link-finder && pnpm run build`
- [ ] Verify dist files are committed to git
- [ ] No `hot` file in resources/dist

### Git
- [ ] All changes committed
- [ ] Commit messages are clear and descriptive
- [ ] No sensitive data in commits
- [ ] Branch is up to date with main

## Release Process

### 1. Final Checks
```bash
# From addon directory
cd addons/newsong/podcast-link-finder

# Clear any dev artifacts
rm -f resources/dist/hot

# Rebuild assets
pnpm run build

# Run tests (if available)
composer test

# Verify composer.json
cat composer.json | grep version
```

### 2. Git Tag and Push
```bash
# Create and push tag
git tag v1.1.0
git push origin v1.1.0

# Or push to main/master first
git push origin main
git tag v1.1.0
git push origin v1.1.0
```

### 3. Create GitHub Release
1. Go to: https://github.com/newsong/podcast-link-finder/releases/new
2. Choose tag: `v1.1.0`
3. Release title: `v1.1.0 - GraphQL Support`
4. Copy content from `RELEASE_NOTES_v1.1.0.md`
5. Check "Set as the latest release"
6. Publish release

### 4. Update Packagist (if registered)
- Packagist should auto-update from GitHub webhook
- If not: https://packagist.org/packages/newsong/podcast-link-finder
- Click "Update" button

### 5. Verify Installation
Test fresh installation:
```bash
# In a test project
composer require newsong/podcast-link-finder:^1.1

# Verify GraphQL types are registered
php artisan tinker
>>> \GraphQL::type('PodcastLinks')
>>> \GraphQL::type('PlatformLink')
```

## Post-Release

### Documentation
- [ ] Update any external documentation
- [ ] Announce on Statamic Discord (optional)
- [ ] Tweet/social media announcement (optional)

### Monitoring
- [ ] Watch for GitHub issues
- [ ] Monitor Packagist download stats
- [ ] Check for compatibility reports

## Rollback Plan

If issues are discovered:

```bash
# Revert to previous version
git revert v1.1.0
git tag v1.1.1
git push origin main
git push origin v1.1.1

# Update CHANGELOG.md with rollback note
# Create new GitHub release v1.1.1
```

## Notes

- **Breaking Changes**: None
- **Migration Required**: No
- **Configuration Changes**: None
- **Dependencies**: No new dependencies added

## Support Channels

- GitHub Issues: https://github.com/newsong/podcast-link-finder/issues
- Email: tech@newsongpeople.com
