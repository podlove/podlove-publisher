<?php

  namespace Podlove;

  /**
   * Authentication methods for Podlove Publisher.
   *
   * Example usage:
   *
   * 	$applicationPassword = Authentication::application_password();
   * 	$applicationPassword['name'] -> username
   * 	$applicationPassword['password'] -> password
   */

  class Authentication {
    private static $appId = 'podlove-publisher';
    private static $appName = 'Podlove Publisher';

    // generates a one time password for authentication
    public static function application_password()
    {
      $user = wp_get_current_user();

      $applicationPasswords = \WP_Application_Passwords::get_user_application_passwords($user->data->ID);

      // find the app by the provided id since wordpress only has helper functions for their generated uuid ...
      $publisherApp = current(array_filter($applicationPasswords, function ($app) {
        return $app['app_id'] == self::$appId;
      }));

      // delete the existing password
      if ($publisherApp) {
        \WP_Application_Passwords::delete_application_password($user->data->ID, $publisherApp['uuid']);
      }

      $appPassword = \WP_Application_Passwords::create_new_application_password($user->data->ID, [
        'name' => self::$appName,
        'app_id' => self::$appId,
      ]);

      return [
        'name' => $user->data->user_login,
        'password' => current($appPassword),
      ];
    }
  }
