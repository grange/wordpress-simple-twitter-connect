<?php
/*
Plugin Name: STC - Login
Plugin URI: http://ottopress.com/wordpress-plugins/simple-twitter-connect/
Description: Integrates Twitter Login and Authentication to WordPress
Author: Otto
Version: 0.15
Author URI: http://ottodestruct.com
License: GPL2

    Copyright 2010  Samuel Wood  (email : otto@ottodestruct.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2, 
    as published by the Free Software Foundation. 
    
    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    The license for this software can likely be found here: 
    http://www.gnu.org/licenses/gpl-2.0.html
    
*/

// if you want people to be unable to disconnect their WP and Twitter accounts, set this to false in wp-config
if (!defined('STC_ALLOW_DISCONNECT')) 
	define('STC_ALLOW_DISCONNECT',true);

// checks for stc on activation
function stc_login_activation_check(){
	if (function_exists('stc_version')) {
		if (version_compare(stc_version(), '0.1', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die("The base STC plugin must be activated before this plugin will run.");
}
register_activation_hook(__FILE__, 'stc_login_activation_check');

// add the section on the user profile page
add_action('profile_personal_options','stc_login_profile_page');

function stc_login_profile_page($profile) {
	$options = get_option('stc_options');
?>
	<table class="form-table">
		<tr>
			<th><label>Twitter Connect</label></th>
<?php
	$twuid = get_usermeta($profile->ID, 'twuid');
	if (empty($twuid)) { 
		?>
			<td><p><?php echo stc_get_connect_button('login_connect'); ?></p></td>
		</tr>
	</table>
	<?php	
	} else { ?>
		<td><p>Connected as 
		<img src='http://api.twitter.com/1/users/profile_image/<?php echo $twuid; ?>?size=bigger' width='32' height='32' />
		<a href='http://twitter.com/<?php echo $twuid; ?>'><?php echo $twuid; ?></a>
<?php if (STC_ALLOW_DISCONNECT) { ?>
		<input type="button" class="button-primary" value="Disconnect this account from WordPress" onclick="stc_login_disconnect(); return false;" />
		<script type="text/javascript">
		function stc_login_disconnect() {
			var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
			var data = {
				action: 'disconnect_twuid',
				twuid: '<?php echo $twuid; ?>'
			}
			jQuery.post(ajax_url, data, function(response) {
				if (response == '1') {
					location.reload(true);
				}
			});
		}
		</script>
<?php } ?>
</p></td>
	<?php } ?>
	</tr>
	</table>
	<?php
}

add_action('wp_ajax_disconnect_twuid', 'stc_login_disconnect_twuid');
function stc_login_disconnect_twuid() {
	$user = wp_get_current_user();

	if (!STC_ALLOW_DISCONNECT) {
		// disconnect not allowed
		echo 1;
		exit();
	}
	
	$twuid = get_usermeta($user->ID, 'twuid');
	if ($twuid == $_POST['twuid']) {
		delete_usermeta($user->ID, 'twuid');
	}
	
	echo 1;
	exit();
}

add_action('stc_login_connect','stc_login_connect');
function stc_login_connect() {
	if (!is_user_logged_in()) return; // this only works for logged in users
	$user = wp_get_current_user();
	
	$tw = stc_get_credentials();
	if ($tw) {
		// we have a user, update the user meta
		update_usermeta($user->ID, 'twuid', $tw->screen_name);
	}
}
	
add_action('login_form','stc_login_add_login_button');
function stc_login_add_login_button() {
	global $action;
	if ($action == 'login') echo '<p>'.stc_get_connect_button('login').'</p><br />';
}

add_filter('authenticate','stc_login_check');
function stc_login_check($user) {
	if ( is_a($user, 'WP_User') ) { return $user; } // check if user is already logged in, skip

	$tw = stc_get_credentials();
	if ($tw) {
		global $wpdb;
		$twuid = $tw->screen_name;
		$user_id = $wpdb->get_var( $wpdb->prepare("SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'twuid' AND meta_value = '%s'", $twuid) );

		if ($user_id) {
			$user = new WP_User($user_id);
		} else {
			do_action('stc_login_new_tw_user',$tw); // hook for creating new users if desired
			global $error;
			$error = '<strong>ERROR</strong>: Twitter user not recognized.';
		}
	}
	return $user;
}

add_action('wp_logout','stc_login_logout');
function stc_login_logout() {
	session_start();
	session_unset();
	session_destroy();
}
