<?php

// default service URL
// If you want to host and use your own service, set the constant in your wp-config.php
// define('PODLOVE_ONBOARDING', 'https://self-hosted-services.example.com/onboarding');
if (!defined('PODLOVE_ONBOARDING')) {
    define('PODLOVE_ONBOARDING', 'https://services.podlove.org/onboarding');
}

function podlove_get_onboarding_include()
{
    if (is_string(PODLOVE_ONBOARDING)) {
        return PODLOVE_ONBOARDING;
    }

    return null;
}
