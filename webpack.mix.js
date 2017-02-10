let mix = require('laravel-mix');

/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application, as well as bundling up your JS files.
 |
 */

mix.js('js/src/app.js', 'js/dist/')

.combine(
    [   
        'js/admin/chosen/chosen.jquery.min.js',
        'js/admin/chosen/chosenImage.jquery.js',
        'js/src/admin/md5.js',
        'js/src/admin/timeago.jquery.js',
        'js/src/admin/jquery.autogrow.js',
        'js/src/admin/jquery.count_characters.js',
        'js/src/admin/podlove_data_table.js',
        'js/src/admin/episode.js',
        'js/src/admin/jobs.js',
        'js/src/admin/audio_duration_loader.js',
        'js/src/admin/dashboard_asset_validation.js',
        'js/src/admin/dashboard_feed_validation.js',
        'js/src/admin/episode_asset_settings.js',
        'js/src/admin/episode_duration.js',
        'js/src/admin/license.js',
        'js/src/admin/media.js',
        'js/src/admin/protected_feed.js',
        'js/src/admin/feed_settings.js',
        'js/src/admin.js'
    ], 
    'js/dist/podlove-admin.js'
)

.combine(
    [
        'js/admin/dc.js',
        'js/src/analytics/common.js',
        'js/src/analytics/episode.js',
        'js/src/analytics/totals.js',
    ], 
    'js/dist/analytics.js'
);

// mix.js('src/app.js', 'dist/')
   // .sass('src/app.scss', 'dist/');

// Full API
// mix.js(src, output);
// mix.extract(vendorLibs);
// mix.sass(src, output);
// mix.less(src, output);
// mix.combine(files, destination);
// mix.copy(from, to);
// mix.minify(file);
// mix.sourceMaps(); // Enable sourcemaps
// mix.version(); // Enable versioning.
// mix.disableNotifications();
// mix.setPublicPath('path/to/public');
// mix.autoload({}); <-- Will be passed to Webpack's ProvidePlugin.
// mix.webpackConfig({}); <-- Override webpack.config.js, without editing the file directly.
// mix.then(function () {}) <-- Will be triggered each time Webpack finishes building.
