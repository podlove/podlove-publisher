module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    makepot: {
        target: {
            options: {
                domainPath: '/language',
                potFilename: 'podlove.pot',   // Name of the POT file.
                type: 'wp-plugin'  // Type of project (wp-plugin or wp-theme).
            }
        }
    },
    po2mo: {
        files: {
            src: 'language/*.po',
            expand: true,
        },
    },
    shell: {
    options: {
      failOnError: true
    },

    msgmerge: {
      command:  function () {
          var locale = 'de_DE';
          var po = "language/podlove-" + locale + ".po";
          return "if [ -f \"" + po + "\" ]; then\n" +
              "    echo \"Updating " + po + "\"\n" +
              "    msgmerge " + po + " language/podlove.pot > .new.po.tmp\n" +
              "    exitCode=$?\n" +
              "    if [ $exitCode -ne 0 ]; then\n" +
              "        echo \"Msgmerge failed with exit code $?\"\n" +
              "        exit $exitCode\n" +
              "    fi\n" +
              "    mv .new.po.tmp " + po + "\n" +
              "fi\n";
      
      }
    }
    },
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
  grunt.loadNpmTasks('grunt-po2mo'); 
  grunt.loadNpmTasks('grunt-shell'); 
  grunt.loadNpmTasks( 'grunt-wp-i18n' );

  // Default task(s).
    grunt.registerTask('default', ['makepot','shell:msgmerge','po2mo','rsync']);


};
