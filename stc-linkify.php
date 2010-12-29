<?php
/*
Plugin Name: STC - Linkify
Plugin URI: http://ottopress.com/wordpress-plugins/simple-twitter-connect/
Description: Automatically link @usernames to twitter, anywhere on the whole site.
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

// checks for stc on activation
function stc_linkify_activation_check(){
	if (function_exists('stc_version')) {
		if (version_compare(stc_version(), '0.7', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die("The base stc plugin must be activated before this plugin will run.");
}
register_activation_hook(__FILE__, 'stc_linkify_activation_check');


// add the simple javascript to the footer
add_action('wp_footer','stc_linkify');
function stc_linkify() {
	$options = get_option('stc_options');
	if ($options['use_hovercards']) {
?>
<script type="text/javascript">
	twttr.anywhere(function (T) {
		T.hovercards();
	});
</script>
<?php } else {
?>
<script type="text/javascript">
	twttr.anywhere(function (T) {
		T.linkifyUsers();
	});
</script>
<?php
	}
}

// add the admin sections to the stc page
add_action('admin_init', 'stc_linkify_admin_init');
function stc_linkify_admin_init() {
	add_settings_section('stc_linkify', 'Linkify Settings', 'stc_linkify_section_callback', 'stc');
	add_settings_field('stc_linkify_hovercards', 'Use Twitter Hovercards', 'stc_linkify_hovercards', 'stc', 'stc_linkify');
}

function stc_linkify_section_callback() {
}

function stc_linkify_hovercards() {
	$options = get_option('stc_options');
	if (!$options['use_hovercards']) $options['use_hovercards'] = false;
	echo "<p><input type='checkbox' id='stc-linkify-hovercards' name='stc_options[use_hovercards]' ";
	checked($options['use_hovercards']);
	echo " /> Show Twitter Hovercards</p>";
}

add_filter('stc_validate_options','stc_linkify_validate_options');
function stc_linkify_validate_options($input) {
	if (isset($input['use_hovercards'])) $input['use_hovercards'] = true;
	else $input['use_hovercards'] = false;
	return $input;
}
