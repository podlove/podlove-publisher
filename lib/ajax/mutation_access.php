<?php

namespace Podlove\AJAX;

class MutationAccess
{
    public static function authorize(
        string $nonce_action,
        string $nonce_field,
        ?string $capability = null,
        array $capability_args = []
    ): void {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            if (!headers_sent()) {
                header('Allow: POST');
            }

            self::send_error('method_not_allowed', __('This action requires a POST request.'), 405);
        }

        if (!check_ajax_referer($nonce_action, $nonce_field, false)) {
            self::send_error('invalid_nonce', __('The request could not be verified.'), 403);
        }

        if ($capability && !current_user_can($capability, ...$capability_args)) {
            self::send_error('forbidden', __('You are not allowed to perform this action.'), 403);
        }
    }

    public static function send_error(string $code, string $message, int $status): void
    {
        wp_send_json_error([
            'code' => $code,
            'message' => $message,
        ], $status);
    }
}
