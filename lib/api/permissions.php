<?php

namespace Podlove;

class Api_Permissons
{
    public static function authorization_status_code()
    {
        $status = 401;

        if (is_user_logged_in()) {
            $status = 403;
        }

        return $status;
    }
}
