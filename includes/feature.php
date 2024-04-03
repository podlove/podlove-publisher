<?php

function podlove_is_onboarding_active()
{
  return (bool) defined('PODLOVE_ONBOARDING') && PODLOVE_ONBOARDING;
}

function podlove_get_onboarding_include()
{
  if (podlove_is_onboarding_active() && is_string(PODLOVE_ONBOARDING)) {
    return PODLOVE_ONBOARDING;
  } else {
    return null;
  }
}
