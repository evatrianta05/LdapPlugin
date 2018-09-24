<?php

// If this file is called directly, abort.

if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'DEBUG_USER_API', false );

include('UserApi.php');
$userApi = new UserAPI($USER_API_KEY, $USER_API_URL);

include('Logger.php');

if(DEBUG_USER_API) {
    $log = new Logger(__DIR__."/logme.log");
    $log->setTimestamp("D M d 'y h.i A");
}

// fired when a new wp user was created
add_action( 'woocommerce_created_customer', 'woocommerce_created_customer_handling', 10, 3 );
// fired when a wp user was updated
add_filter( 'user_profile_update_errors', 'add_user_to_backend_user_handling', 10, 3 );
// fired after user login
add_filter ( 'wp_authenticate_user' , 'save_existing_wpuser_wpent', 10, 2 );
// fired after successful payment
// add_action( 'woocommerce_payment_complete', 'payment_complete', 20, 3);
add_action( 'profile_update', 'wp_user_update', 20, 3);

function isUserValid($user) {
    return isset($user['email']) && isset($user['givenname']) && isset($user['familyname']);
}

function woocommerce_created_customer_handling( $customer_id, $new_customer_data, $password_generated ) {
    logErr('woocommerce_created_customer_handling');
    if ( ! $new_customer_data || ! $customer_id ) {
        logErr( 'DEBUG_USER_API - no created customer data on woocommerce_created_customer hook');
        return;
    }

    $password = ( ! empty( $new_customer_data['user_pass'] ) ) ? $new_customer_data['user_pass'] : $password_generated ;

    if ( ! $password  ) {
        logErr( 'DEBUG_USER_API - no password given on woocommerce_created_customer hook');
        return;
    }

    $wpuser = get_userdata( $customer_id );
    if ( ! $wpuser ) {
        logErr( 'DEBUG_USER_API - no user has been crated before woocommerce_created_customer hook');
        return;
    }

    add_new_user_wpent ( $wpuser, $password );
}

function add_user_to_backend_user_handling( $errors, $update, $wpuser ) {
    logErr('add_user_to_backend_user_handling');
    global $userApi;

    if ( $errors->get_error_codes() ) {
        logErr('$errors: '. $errors->get_error_codes());
        return;
    }

    if (!$wpuser->user_email) {
        return;
    }

    $email = $wpuser->user_email;

    try {
        $user = $userApi->getUser($email);
        try {
            $names = getUserNames($wpuser);
            $firstName = $names['firstName'];
            $lastName = $names['lastName'];

            $familyname = ( empty( $firstName ) ) ? $user['familyname'] : $firstName;
            $givenname  = ( empty( $lastName ) ) ? $user['givenname'] : $lastName;
            // pwd = NULL does not update the password in the backend
            // $pwd  = ( empty( $wpuser->user_pass ) ) ? NULL : $wpuser->user_pass;

            $familyname = empty($familyname) ? 'n/a' : $familyname;
            $givenname = empty($givenname) ? 'n/a' : $givenname;

            logErr('familyname: '. $familyname);
            logErr('givenname: '. $givenname);

            // never change the ldap user password on modify
            $user = $userApi->modifyUser($email, $givenname, $familyname, NULL);
        } catch (Exception $e ) {
            add_new_user_wpent($wpuser, $wpuser->user_pass);
        }
    }
    catch ( Exception $e ) {
        if ( ! $wpuser->user_pass || ! $wpuser->user_email ) {
            logErr('probably user exists in woocommerce, but not in ldap - we dont handle this flow');
            return;
        }

        add_new_user_wpent ( $wpuser, $wpuser->user_pass );
    }
}

function getUserNames($wpuser) {
    if ( isset( $_POST['first_name'] ) || isset( $_POST['billing_first_name'] ) ) {
        $firstName = $_POST['first_name'] ? $_POST['first_name'] : $_POST['billing_first_name'];
    } else {
        $firstName = isset($wpuser->first_name) ? $wpuser->first_name : 'n/a';
    }
    logErr('-firstName: '. $firstName);
    update_user_meta($wpuser->ID, 'first_name', $firstName);

    if ( isset( $_POST['last_name'] ) || isset( $_POST['billing_last_name'] ) ) {
        $lastName = $_POST['last_name'] ? $_POST['last_name'] : $_POST['billing_last_name'];
    } else {
        $lastName = isset($wpuser->last_name) ? $wpuser->last_name : 'n/a';
    }
    logErr('-lastName: '. $lastName);
    update_user_meta($wpuser->ID, 'last_name', $lastName);

    return array( 'firstName' => $firstName, 'lastName' => $lastName );
}

function add_new_user_wpent ( $wpuser, $plaintext_pass ) {
    logErr('add_new_user_wpent');
    global $userApi;
    $email = $wpuser->user_email;
    $user = NULL;

    try {
        $user = $userApi->getUser($email);
    }
    catch ( Exception $e ) {

        $names = getUserNames($wpuser);
        $firstName = $names['firstName'];
        $lastName = $names['lastName'];

        try {
            logErr('email: '. $email);
            logErr('lastName: '. $lastName);
            logErr('firstName: '. $firstName);
            $user = $userApi->createUser($email, $firstName, $lastName, $plaintext_pass);
            logErr('user created: '.json_encode($user));
        } catch( Exception $err ) {
            logErr('user not created: '. $err->getMessage());
        }
    }
    logErr(''); 
}

function save_existing_wpuser_wpent ( $wpuser, $plaintextpw ) {
    logErr('save_existing_wpuser_wpent');
    global $userApi;
    logErr( print_r( $wpuser, true ) . ' tried to login ' );
  
    if ( ! wp_check_password( $plaintextpw, $wpuser->user_pass, $wpuser->ID ) ) {
        return $wpuser;
    }
    
    add_new_user_wpent( $wpuser, $plaintextpw );  
  
    return $wpuser;
}

function wp_user_update($user_id, $old_user_data) {
    logErr('wp_user_update');
    global $userApi;
    try {
        $wp_user = get_userdata($user_id);
        $email = $wp_user->user_email;
        logErr('email: '. $email);
        $user = $userApi->getUser($email);

        logErr('$user[givenname]: '. $user);
        logErr('$user[familyname]: '. $user['familyname']);
        logErr('lastName: '. $wp_user->last_name);
        logErr('firstName: '. $wp_user->first_name);

        if ( $user['givenname'] != $wp_user->first_name || $user['familyname'] != $wp_user->last_name ) {
            $user = $userApi->modifyUser($email, $wp_user->first_name, $wp_user->last_name, $wpuser->user_pass);
        }
    }
    catch ( Exception $e ) {
        logErr('user does not exist');
    }
}

function logme($msg) {
    echo '<br/>'.$msg.'<br/>';
}

function logErr($data){
    global $log;
    if(DEBUG_USER_API) {
        $log->putLog($data);
    }
}
?>