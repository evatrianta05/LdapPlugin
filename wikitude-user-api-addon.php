<?php
/**
 * Plugin Name:     Wikitude UserAPI Addon
 * Plugin URI:      https://wikitude.com
 * Description:     This plugin adds the wikitude user api integration.
 * Version:         0.1
 * Author:          Wikitude GmbH - Christian Ebner / Eva Triantafillopoulou
 * Author URI:      https://wikitude.com
 * License:         All rights reserved by Wikitude GmbH
 * Copyright:       Copyright 2018 Wikitude GmbH
 */

$USER_API_KEY = getUserAPIKey();
$USER_API_URL = getUserAPIUrl();

if ( isset($USER_API_KEY) && isset($USER_API_URL) ) {
    include('wikitude-user-api.php');
}
add_action('admin_menu', 'authenticate_users_menu');
add_action( 'admin_init', 'authenticate_users_settings' );      


function authenticate_users_menu() 
{
    add_menu_page(
        'Authenticate Users Settings', 
        'Wikitude Users API Addon', 
        'administrator', 
        'authenticate_users_settings', 
        'authenticate_users_settings_page', 
        'dashicons-admin-generic');
}


function authenticate_users_settings_page() {
    ?>
    <div class="wrap">
    <h2>Authenticate Users - SetUp</h2>
    
    <form method="post" action="options.php">
        <?php settings_fields( 'authenticate-users-settings-group' ); ?>
        <?php do_settings_sections( 'authenticate-users-settings-group' ); ?>
        <table class="form-table">
            <tr valign="top">
            <th scope="row">User API url</th>
            <td><input type="text" name="user_url" value="<?php echo esc_attr( get_option('user_url') ); ?>" /></td>
            </tr>
    
            <tr valign="top">
            <th scope="row">User API Key</th>
            <td><input type="text" name="user_key" value="<?php echo esc_attr( get_option('user_key') ); ?>" /></td>
            </tr>
        </table>
    
        <?php submit_button(); ?>
    </form>
    </div>
    <?php
    }
    
    function authenticate_users_settings() {
        register_setting( 'authenticate-users-settings-group', 'user_url' );
        register_setting( 'authenticate-users-settings-group', 'user_key' );
    }

    function getUserAPIUrl ()
    {
        return get_option('user_url');
    }

    function getUserAPIKey ()
    {
        return get_option('user_key');
    }