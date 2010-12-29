<?php
/* 
Plugin Name: STC - Comments
Plugin URI: http://ottopress.com/wordpress-plugins/simple-twitter-connect/
Description: Comments plugin for STC (for sites that allow non-logged in commenting).
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

Usage Note: You have to modify your theme to use this plugin.

In your comments.php file (or wherever your comments form is), you need to do the following.

1. Find the three inputs for the name, email, and url.

2. Just before the first input, add this code:
<div id="comment-user-details">
<?php do_action('alt_comment_login'); ?>

3. Just below the last input (not the comment text area, just the name/email/url inputs, add this:
</div>

That will add the necessary pieces to allow the script to work.

Hopefully, a future version of WordPress will make this simpler.

*/

// check for logout request
add_action('init','stc_comm_logout',20);
function stc_comm_logout() {
	if ($_GET['stc-logout']) { 
		session_start();
		session_unset();
		session_destroy();
		$page = stc_get_current_url();
		if (strpos($page, "?") !== false) $page = reset(explode("?", $page));
		wp_redirect($page);
		exit; 
	}
}

// checks for stc on activation
function stc_comm_activation_check(){
	if (function_exists('stc_version')) {
		if (version_compare(stc_version(), '0.1', '>=')) {
			
			// default options set
			$options = get_option('stc_options');
			if (!$options['comment_text']) $options['comment_text'] = 'Just left a comment on %';
			update_option('stc_options', $options);
			
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die("The base stc plugin must be activated before this plugin will run.");
	
	
}
register_activation_hook(__FILE__, 'stc_comm_activation_check');

// force load jQuery (we need it later anyway)
add_action('wp_enqueue_scripts','stc_comm_jquery');
function stc_comm_jquery() {
	wp_enqueue_script('jquery');
	
	$options = get_option('stc_options');
	if (!empty($options['comment_text'])) {  // dont do this if disabled 
		wp_enqueue_script('google-jsapi','http://www.google.com/jsapi');
	}
}

// add the admin sections to the stc page
add_action('admin_init', 'stc_comm_admin_init');
function stc_comm_admin_init() {
	add_settings_section('stc_comm', 'Comment Settings', 'stc_comm_section_callback', 'stc');
	add_settings_field('stc_comm_text', 'Comment Tweet Text', 'stc_comm_text', 'stc', 'stc_comm');
}

function stc_comm_section_callback() {
	echo '<p>Define how you want the Tweet for Comments to be formatted. Use the % symbol in place of the link to the post. Leave blank to disable.</p>';
	if (!function_exists('get_shortlink') && !function_exists('wp_get_shortlink')) {
		echo '<p>Warning: No URL Shortener plugin detected. Links used will be full permalinks.</p>';
	}
}

function stc_comm_text() {
	$options = get_option('stc_options');
	echo "<input type='text' name='stc_options[comment_text]' value='{$options['comment_text']}' size='40' />";	
}

add_filter('stc_validate_options','stc_comm_validate_options');
function stc_comm_validate_options($input) {
	$input['comment_text'] = trim($input['comment_text']);
	return $input;
}

// set a variable to know when we are showing comments (no point in adding js to other pages)
add_action('comment_form','stc_comm_comments_enable');
function stc_comm_comments_enable() {
	global $stc_comm_comments_form;
	$stc_comm_comments_form = true;
}

// add placeholder for sending comment to twitter checkbox
add_action('comment_form','stc_comm_send_place');
function stc_comm_send_place() {
?><p id="stc_comm_send"></p><?php
}

// hook to the footer to add our scripting
add_action('wp_footer','stc_comm_footer_script',30); // 30 to ensure we happen after stc base
function stc_comm_footer_script() {
	global $stc_comm_comments_form;
	if ($stc_comm_comments_form != true) return; // nothing to do, not showing comments

	if ( is_user_logged_in() ) return; // don't bother with this stuff for logged in users
	
	?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		var ajax_url = '<?php echo admin_url("admin-ajax.php"); ?>';
		var data = {
			action: 'stc_comm_get_display'
		}
		jQuery.post(ajax_url, data, function(response) {
			if (response != '0' && response != 0) {
				jQuery('#comment-user-details').hide().after(response);
				
				<?php 
				$options = get_option('stc_options');
				if (!empty($options['comment_text'])) {  // dont do this if disabled 
				?>
				jQuery('#stc_comm_send').html('<input style="width: auto;" type="checkbox" name="stc_comm_send" value="send"/><label for="stc_comm_send">Send Comment to Twitter</label><input type="hidden" id="stc_lat" name="stc_lat" /><input type="hidden" id="stc_long" name="stc_long" />');
				
				if (google.loader.ClientLocation) {
					jQuery('#stc_lat').val(google.loader.ClientLocation.latitude);
					jQuery('#stc_long').val(google.loader.ClientLocation.longitude);
				}
				<?php } ?>
			}
		});
	});
</script>
	<?php
}

//add_action('wp_ajax_stc_comm_get_display', 'stc_comm_get_display');
add_action('wp_ajax_nopriv_stc_comm_get_display', 'stc_comm_get_display');
function stc_comm_get_display() {
	$tw = stc_get_credentials();
	if ($tw) {
		echo "<span id='tw-user'>".
			 "<img src='http://api.twitter.com/1/users/profile_image/".$tw->screen_name."?size=bigger' width='96' height='96' />".
			 "<span id='tw-msg'><strong>Hi ".$tw->name."!</strong><br />You are connected with your Twitter account. ".
			 "<a href='?stc-logout=1'>Logout</a>".
			 "</span></span>";
		exit;
	}
	
	echo 0;
	exit;
}

add_action('comment_post','stc_comm_send_to_twitter');
function stc_comm_send_to_twitter() {
	$options = get_option('stc_options');
	
	if (!$options['comment_text']) return;

	$postid = (int) $_POST['comment_post_ID'];
	if (!$postid) return;
	
	// send the comment to twitter
	if ($_POST['stc_comm_send'] == 'send') {
	
		// args to send to twitter
		$args=array();
	
		// check for coords from the post
		$lat = (double) $_POST['stc_lat'];
		$long = (double) $_POST['stc_long'];

		if ($lat != 0 && $long !=0) {
			// we got coords, include them
			$args['lat'] = $lat;
			$args['long'] = $long;
		}

		if (function_exists('wp_get_shortlink')) {
			// use the shortlink if it's available
			$link = wp_get_shortlink($postid);
		} else if (function_exists('get_shortlink')) {
			// use the shortlink if it's available
			$link = get_shortlink($postid);
		} else {
			// use the full permalink (twitter will shorten for you)
			$link = get_permalink($postid);
		}
		
		$args['status'] = str_replace('%',$link, $options['comment_text']);
		
		$resp = stc_do_request('http://api.twitter.com/1/statuses/update',$args);
	}
}


// this bit is to allow the user to add the relevant comments login button to the comments form easily
// user need only stick a do_action('alt_comment_login'); wherever he wants the button to display
add_action('alt_comment_login','stc_comm_login_button');
add_action('comment_form_before_fields', 'stc_comm_login_button',10,0); // WP 3.0 support
function stc_comm_login_button() {
	echo '<p>'.stc_get_connect_button('comment').'</p>';
}

// this exists so that other plugins can hook into the same place to add their login buttons
if (!function_exists('alt_login_method_div')) {

add_action('alt_comment_login','alt_login_method_div',5,0);
add_action('comment_form_before_fields', 'alt_login_method_div',5,0); // WP 3.0 support

function alt_login_method_div() { echo '<div id="alt-login-methods">'; }

add_action('alt_comment_login','alt_login_method_div_close',20,0);
add_action('comment_form_before_fields', 'alt_login_method_div_close',20,0); // WP 3.0 support

function alt_login_method_div_close() { echo '</div>'; }

}

// WP 3.0 support
if (!function_exists('comment_user_details_begin')) {

add_action('comment_form_before_fields', 'comment_user_details_begin',1,0);
function comment_user_details_begin() { echo '<div id="comment-user-details">'; }

add_action('comment_form_after_fields', 'comment_user_details_end',20,0);
function comment_user_details_end() { echo '</div>'; }

}



// generate facebook avatar code for Twitter user comments
add_filter('get_avatar','stc_comm_avatar', 10, 5);
function stc_comm_avatar($avatar, $id_or_email, $size = '96', $default = '', $alt = false) {
	// check to be sure this is for a comment
	if ( !is_object($id_or_email) || !isset($id_or_email->comment_ID) || $id_or_email->user_id) 
		 return $avatar;
		 
	// check for twuid comment meta
	$twuid = get_comment_meta($id_or_email->comment_ID, 'twuid', true);
	if ($twuid) {
		// return the avatar code
		$avatar = "<img class='avatar avatar-{$size} twitter-avatar' src='http://api.twitter.com/1/users/profile_image/{$twuid}?size=bigger' width='{$size}' height='{$size}' />";
	}
		
	return $avatar;
}

// store the Twitter screen_name as comment meta data ('twuid')
add_action('comment_post','stc_comm_add_meta', 10, 1);
function stc_comm_add_meta($comment_id) {
	$tw = stc_get_credentials();
	if ($tw) {
		update_comment_meta($comment_id, 'twuid', $tw->screen_name);
	}
}

// Add user fields for FB commenters
add_filter('pre_comment_on_post','stc_comm_fill_in_fields');
function stc_comm_fill_in_fields($comment_post_ID) {
	if (is_user_logged_in()) return; // do nothing to WP users
	
	$tw = stc_get_credentials();
	if ($tw) {	
		$_POST['author'] = $tw->name;
		$_POST['url'] = 'http://twitter.com/'.$tw->screen_name;
		
		// use an @twitter email address. This shows it's a twitter name, and email to it won't work.
		$_POST['email'] = $tw->screen_name.'@fake.twitter.com'; 
	}
}
