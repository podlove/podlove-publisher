var PODLOVE = PODLOVE || {};

/**
 * Load duration of audio source.
 *
 * Basic Usage:
 *
 * var loader = AudioDurationLoader({
 *   success: function(audio, event) {
 *     console.log("Duration of audio is ", audio.duration);
 *   }
 * });
 * loader.load("http://meta.metaebene.me/media/metaebene/episodes/me001-stand-der-dinge-sommer-2015.m4a");
 *
 * Callbacks:
 *
 * - before: called before preloading starts
 * - success(audio, event): called when duration is available
 * - error(error): called when an error occured
 */
PODLOVE.AudioDurationLoader = function (options) {
    'use strict';

    var durationLoader = {};

    if (!options) {
        options = {};
    }

    if (!options.success) {
        options.success = function (audio, event) {
            console.log("duration", audio.duration);
        };
    }

    if (!options.error) {
        options.error = function (error) {
            console.log("Could not determine duration.", error);
        };
    }

    durationLoader.load = function (src) {
        
        if (options.before) {
            options.before();
        }
        
        try {
            var audio = new Audio();
            
            audio.addEventListener("loadedmetadata", function (e) {
                return options.success(audio, e);
            });
            
            audio.addEventListener("error", options.error);
            
            audio.setAttribute("preload", "metadata");
            audio.setAttribute("src", src);
            audio.load();
        } catch (e) {
            options.error(e);
        }
    };
    
    return durationLoader;
};
