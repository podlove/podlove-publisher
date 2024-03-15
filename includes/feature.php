<?php

function podlove_is_onboarding_active()
{
    if (defined('PODLOVE_ONBOARDING') && PODLOVE_ONBOARDING) {
        return true;
    }

    return false;
}
