module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    rsync: {
      dist: {
        src: "./",
        dest: ".wordpress_release/trunk",
        recursive: true,
        syncDest: true,
        exclude: [
          ".git",
          ".wordpress_release",
          ".gitmodules",
          ".tags",
          ".tags_sorted_by_file",
          "wprelease.yml",
          "podlove.sublime-workspace",
          "podlove.sublime-project",
          "lib/modules/podlove_web_player/player/podlove-web-player/libs",
          "vendor/bin",
          "vendor/phpunit",
          "vendor/symfony",
          "node_modules",
          "Gruntfile.js",
          "phpunit.xml",
          "test",
          "Rakefile"
        ]
      }
    }
  });

  // Load the plugin that provides the "uglify" task.
  // grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.loadNpmTasks("grunt-rsync");

  // Default task(s).
  grunt.registerTask('default', ['rsync']);

};