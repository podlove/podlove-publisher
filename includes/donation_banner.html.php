<style>
    .px-4 {
        padding-left: 1rem;
        padding-right: 1rem;
    }

    .py-12 {
        padding-top: 3rem;
        padding-bottom: 3rem;
    }

    .mx-auto {
        margin-left: auto;
        margin-right: auto;
    }

    .h-56 {
        height: 14rem;
    }

    @media (min-width: 640px) {
        .sm\:px-6 {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }

        .sm\:h-72 {
            height: 18rem;
        }
    }

    @media (min-width: 768px) {
        .md\:absolute {
            position: absolute;
        }

        .md\:left-0 {
            left: 0px;
        }

        .md\:h-full {
            height: 100%;
        }

        .md\:w-1\/2 {
            width: 50%;
        }

        .md\:ml-auto {
            margin-left: auto;
        }

        .md\:pl-10 {
            padding-left: 2.5rem;
        }
    }
</style>

<script>
    jQuery(function($) {
        $("#podlove_donation .dismiss a").on("click", function(e) {

            var data = {
                action: 'podlove-hide-donation-banner'
            };

            $.ajax({
                url: ajaxurl,
                data: data,
                dataType: 'json'
            });

            $("#podlove_donation").slideUp();

            return false;
        });
    });
</script>

<div style="position:relative; margin-top: 60px; margin-right: 10px; box-sizing: border-box;">
<div id="podlove_donation" class="relative bg-gray-800" style="position: relative; background-color: rgba(31, 41, 55, 1); max-width: 1330px;">
    <div class="h-56 bg-indigo-600 sm:h-72 md:absolute md:left-0 md:h-full md:w-1/2" style="background-color: rgba(37, 99, 235, 1);">
        <img class="w-full h-full object-cover" style="width: 100%; height: 100%; object-fit: cover;" src="<?php include 'donation_banner.img.src'; ?>" alt="">
    </div>
    <div class="relative max-w-7xl mx-auto px-4 py-12 sm:px-6 lg:px-8 lg:py-16" style="position: relative; 	box-sizing: border-box;">
        <div class="md:ml-auto md:w-1/2 md:pl-10" style="box-sizing: border-box;">
            <h2 class="text-base font-semibold uppercase tracking-wider text-gray-300" style="margin: 0; color: rgba(209, 213, 219, 1); letter-spacing: 0.05em; font-size: 1rem; line-height: 1.5rem; text-transform: uppercase;">
                <?php _e('Support Open Source Software', 'podlove-podcasting-plugin-for-wordpress'); ?>
            </h2>
            <p class="mt-2 text-white text-3xl font-extrabold tracking-tight sm:text-4xl" style="margin-top: 0.5rem; margin-bottom: 0; color: white; font-size: 1.875rem; line-height: 2.25rem; font-weight: 900;">
                <?php _e('Dear Podcaster,', 'podlove-podcasting-plugin-for-wordpress'); ?>
            </p>
            <p class="mt-3 text-lg text-gray-300" style="margin-top: 0.75rem; font-size: 1rem;
  line-height: 1.5rem; color: rgba(209, 213, 219, 1);	">
                <?php _e('Hi 👋 I\'m Eric and I\'m maintaining this plugin. If you want to support the work we do with Podlove,
                please consider a donation. The more we collect, the more time we can spend on making podcasting better.
                Thank you!', 'podlove-podcasting-plugin-for-wordpress'); ?>
            </p>
            <div class="mt-8" style="margin-top: 2rem; display: inline-flex; align-items: center; justify-content: center; ">
                <div class="inline-flex rounded-md shadow" style="display: inline-flex; border-radius: 0.375rem; ">
                    <a href="https://opencollective.com/podlove" target="_blank" class="inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-gray-900 bg-white hover:bg-gray-50" style="display: inline-flex; align-items: center; justify-content: center; padding: 0.75rem 1.25rem; border-radius: 0.375rem; background: white; color: rgba(17, 24, 39, 1); font-size: 1rem; line-height: 1.5rem; 	font-weight: 500; text-decoration: none;">
                        <?php _e('Donate to Podlove', 'podlove-podcasting-plugin-for-wordpress'); ?>
                        <svg class="-mr-1 ml-3 h-5 w-5 text-gray-400" style="margin-right: -0.25rem; margin-left: 0.75rem; height: 1.25rem; width: 1.25rem; color: rgba(156, 163, 175, 1);
  " xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path d="M11 3a1 1 0 100 2h2.586l-6.293 6.293a1 1 0 101.414 1.414L15 6.414V9a1 1 0 102 0V4a1 1 0 00-1-1h-5z" />
                            <path d="M5 5a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-3a1 1 0 10-2 0v3H5V7h3a1 1 0 000-2H5z" />
                        </svg>
                    </a>
                </div>

                <span class="dismiss" style="color: white; padding-left: 1rem;">
                    <a href="#" style="color: white; text-decoration: none">
                        <?php _e('or hide this banner', 'podlove-podcasting-plugin-for-wordpress'); ?>
                    </a>
                </span>

            </div>
        </div>
    </div>
</div>
</div>
