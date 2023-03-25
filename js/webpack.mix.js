let mix = require("laravel-mix");

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

mix.js('src/app.js', 'dist/').vue()
  .combine(
    [
      "node_modules/clipboard/dist/clipboard.min.js",
      "admin/chosen/chosen.jquery.min.js",
      "admin/chosen/chosenImage.jquery.js",
      "src/admin/md5.js",
      "src/admin/timeago.jquery.js",
      "src/admin/jquery.count_characters.js",
      "src/admin/podlove_data_table.js",
      "src/admin/episode.js",
      "src/admin/jobs.js",
      "src/admin/audio_duration_loader.js",
      "src/admin/dashboard_asset_validation.js",
      "src/admin/dashboard_feed_validation.js",
      "src/admin/episode_asset_settings.js",
      "src/admin/license.js",
      "src/admin/media.js",
      "src/admin/protected_feed.js",
      "src/admin/feed_settings.js",
      "src/admin/post_title_autogenerate.js",
      "src/admin.js",
    ],
    "dist/podlove-admin.js"
  )

  .babel(
    [
      "admin/dc.js",
      "src/analytics/common.js",
      "src/analytics/episode.js",
      "src/analytics/totals.js",
    ],
    "dist/podcast-stats.js"
  );

// Full API
// mix.js(src, output);
// mix.react(src, output); <-- Identical to mix.js(), but registers React Babel compilation.
// mix.extract(vendorLibs);
// mix.sass(src, output);
// mix.standaloneSass('src', output); <-- Faster, but isolated from Webpack.
// mix.fastSass('src', output); <-- Alias for mix.standaloneSass().
// mix.less(src, output);
// mix.stylus(src, output);
// mix.browserSync('my-site.dev');
// mix.combine(files, destination);
// mix.babel(files, destination); <-- Identical to mix.combine(), but also includes Babel compilation.
// mix.copy(from, to);
// mix.copyDirectory(fromDir, toDir);
// mix.minify(file);
// mix.sourceMaps(); // Enable sourcemaps
// mix.version(); // Enable versioning.
// mix.disableNotifications();
// mix.setPublicPath('path/to/public');
// mix.setResourceRoot('prefix/for/resource/locators');
// mix.autoload({}); <-- Will be passed to Webpack's ProvidePlugin.
// mix.webpackConfig({}); <-- Override webpack.config.js, without editing the file directly.
// mix.then(function () {}) <-- Will be triggered each time Webpack finishes building.
// mix.options({
//   extractVueStyles: false, // Extract .vue component styling to file, rather than inline.
//   processCssUrls: true, // Process/optimize relative stylesheet url()'s. Set to false, if you don't want them touched.
//   purifyCss: false, // Remove unused CSS selectors.
//   uglify: {}, // Uglify-specific options. https://webpack.github.io/docs/list-of-plugins.html#uglifyjsplugin
//   postCss: [] // Post-CSS options: https://github.com/postcss/postcss/blob/master/docs/plugins.md
// });
