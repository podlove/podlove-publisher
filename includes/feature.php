<?php

function is_onboarding_active()
{
    if (defined('PODLOVE_ONBOARDING')) {
        $var = constant('PODLOVE_ONBOARDING');
        if ($var != false) {
            return true;
        }
    }

    return false;
}
