# WIP Handover: Admin Asset Loading and PLUS Bundle Split

## Summary

This branch experiments with two related changes:

1. Splitting the Publisher PLUS settings page out of the main Vue client bundle.
2. Refactoring admin asset loading away from the old procedural `includes/scripts_and_styles.php`
   flow into small classes under `lib/admin/`.

The immediate performance goal was achieved:

- the PLUS settings page no longer loads the full episode-page client bundle
- the main `client` bundle is smaller
- the build no longer emits the earlier chunk size warning

The architectural question is still open:

- whether the new bundle split and class-based asset loader are the right long-term direction
- whether the remaining legacy admin screens should keep using the old `podlove-episode-vue-apps`
  path, or whether asset loading should be redesigned more holistically

## Current Structure

### JS / client side

- `client` is now treated as the episode-page bundle only.
- `plus` is a separate bundle for the Publisher PLUS settings page.
- shared PLUS saga logic was extracted into:
  - `client/src/shared/plus.sagas.ts`
  - `client/src/shared/plusFileMigration.sagas.ts`
  - `client/src/lib/createApiFactory.ts`
- the PLUS-only store/bootstrap lives under:
  - `client/src/plus.ts`
  - `client/src/plus/store/*`
  - `client/src/plus/sagas/*`

### PHP / admin asset loading

`includes/scripts_and_styles.php` is now intentionally thin and only instantiates:

- `Podlove\Admin\PublisherAssetManager`

The asset system is split into one file per class:

- `lib/admin/asset_context.php`
- `lib/admin/asset_bundle.php`
- `lib/admin/publisher_asset_manager.php`

The current asset manager model is:

- `AssetContext` captures screen/post/episode state.
- `AssetBundle` defines one logical bundle with:
  - screen matcher
  - scripts
  - styles
  - translations
  - `PODLOVE_DATA` provider
  - optional localizer
- `PublisherAssetManager`:
  - hooks into `script_loader_tag`
  - hooks into `podlove_data_js`
  - resolves active bundles on `admin_enqueue_scripts`
  - registers and enqueues them

## Behavior After This Work

### Episode edit screen

Loads:

- legacy `podlove-episode-vue-apps`
- `client`
- admin assets (`podlove_admin`, CSS, etc.)

This preserves the rule that everything needed on the episode page stays together.

### Publisher PLUS settings page

Loads:

- `plus`
- shared client CSS
- admin assets

It does **not** load the main `client` bundle anymore.

### Legacy non-episode Vue screens

Still load:

- legacy `podlove-episode-vue-apps`

They no longer load the main `client` bundle.

This is deliberate based on the current mount analysis: the remaining Vue modules in
`client/src/modules/index.ts` are all episode-page mounts.

## Important Open Questions

### 1. Is the bundle split correct at the product level?

The current split assumes:

- episode page stays cohesive
- PLUS settings is the only safe extraction right now

This seems consistent with current mounts, but it may or may not match where the Publisher UI
is ultimately going.

### 2. What should happen to the legacy Vue app path?

The current refactor improves structure around asset loading, but it does not redesign the legacy
`podlove-episode-vue-apps` system.

That means there are effectively two admin UI loading systems in play:

- old `js/dist/app.js`
- new Vite-based bundles under `client/dist/`

If the goal is long-term simplification, the next design decision should probably be about whether
those remaining legacy screens should stay on the old path or be migrated into explicit bundles too.

### 3. Is the `PublisherAssetManager` abstraction the right level?

The new class structure is cleaner than the previous procedural file, but it is still a custom
internal registry. It helps readability and future growth, but it also introduces a small framework
that the team has to own.

If this direction is kept, the next cleanup could be:

- introduce value objects for script/style definitions instead of raw arrays
- potentially move bundle definitions into dedicated classes if the registry grows further

If this direction is not kept, this branch should still be useful as a prototype showing which
seams exist in the current asset-loading design.

## Files Added / Restructured

### New PHP classes

- `lib/admin/asset_context.php`
- `lib/admin/asset_bundle.php`
- `lib/admin/publisher_asset_manager.php`

### New JS entrypoints / shared logic

- `client/src/plus.ts`
- `client/src/plus/store/*`
- `client/src/plus/sagas/*`
- `client/src/shared/plus.sagas.ts`
- `client/src/shared/plusFileMigration.sagas.ts`
- `client/src/lib/createApiFactory.ts`
- `client/src/store/plus.selectors.ts`

## Validation Performed

- `mise exec -- npm --prefix client run build`
- `mise exec -- php -l` on the new/changed PHP asset files
- targeted `php-cs-fixer` on the changed PHP asset files

At the point of the successful build, bundle sizes were approximately:

- `client.js`: 384.52 kB, gzip 112.46 kB
- `plus.js`: 26.40 kB, gzip 8.56 kB

## Recommended Next Decision

Before doing more implementation, decide one of these:

1. Keep this direction:
   - separate bundles by real screen boundaries
   - keep episode-page UI together
   - continue improving the asset manager abstraction

2. Back up and redesign:
   - rethink asset loading around a fuller migration plan from legacy admin JS to explicit modern
     bundles
   - possibly reduce custom registry logic if a simpler model is preferred

If continuing from this branch, I would next review whether the raw script/style arrays inside
`PublisherAssetManager` should become dedicated small objects or bundle classes, because that is
the main remaining readability issue in the PHP side.
