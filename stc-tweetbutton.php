<?php
/*
Plugin Name: STC - Tweet Button
Plugin URI: http://ottopress.com/wordpress-plugins/simple-twitter-connect/
Description: Adds a Tweet button to your content.
Author: Otto
Version: 0.15
Author URI: http://ottodestruct.com
License: GPL2

    Copyright 2009-2010  Samuel Wood  (email : otto@ottodestruct.com)

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
function stc_tweetbutton_activation_check(){
	if (function_exists('stc_version')) {
		if (version_compare(stc_version(), '0.1', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die("The base stc plugin must be activated before this plugin will run.");
}
register_activation_hook(__FILE__, 'stc_tweetbutton_activation_check');


global $stc_tweetbutton_defaults;
$stc_tweetbutton_defaults = array(
	'id'=>0,
	'style'=>'vertical',
	'source'=>'',
	'related'=>'',
	'text'=>'', 
);	
		
/**
 * Simple tweet button
 */
function get_stc_tweetbutton($args='') {
	global $stc_tweetbutton_defaults;
	$args = wp_parse_args($args, $stc_tweetbutton_defaults);
	extract($args);
	
	// fix for missing ID in some cases (some shortlink plugins don't work well with ID = zero)
	if (!$id) $id = get_the_ID();
	
	$options = get_option('stc_options');
	if ($options['tweetbutton_source']) $source = $options['tweetbutton_source'];
	if ($options['tweetbutton_style']) $style = $options['tweetbutton_style'];
	if ($options['tweetbutton_related']) $related = $options['tweetbutton_related'];
	$url = wp_get_shortlink($id);
	$counturl = get_permalink($id);
	$post = get_post($id);
	$text = esc_attr(strip_tags($post->post_title));;
	
	if (!empty($related)) $datarelated = " data-related='{$related}'";
	
	$query = http_build_query(array(
		'url'     => $url,
		'count'   => $style,
		'related' => $related,
		));

	$query .= '&text='.rawurlencode($text);

	$out = "<a href='http://twitter.com/share?{$query}' class='twitter-share-button' data-text='{$text}' data-url='{$url}' data-counturl='{$counturl}' data-count='{$style}' data-via='{$source}'{$datarelated}>Tweet</a>";
	return $out;
}

function stc_tweetbutton($args='') {
	echo get_stc_tweetbutton($args);
}

// we need this script in the footer to make the tweet buttons show up
add_action('wp_footer','stc_tweetbutton_footer');
function stc_tweetbutton_footer() {
	?><script type='text/javascript' src='http://platform.twitter.com/widgets.js'></script><?php
}

/**
 * Simple tweetbutton button as a shortcode
 *
 * Example use: [tweetbutton source="ottodestruct"] or [tweetbutton id="123"]
 */
function stc_tweetbutton_shortcode($atts) {
	global $stc_tweetbutton_defaults;
	$args = shortcode_atts($stc_tweetbutton_defaults, $atts);
	return get_stc_tweetbutton($args);
}

add_shortcode('tweetbutton', 'stc_tweetbutton_shortcode');

function stc_tweetbutton_automatic($content) {
	$options = get_option('stc_options');
	$button = get_stc_tweetbutton();
	switch ($options['tweetbutton_position']) {
		case "before":
			$content = $button . $content;
			break;
		case "after":
			$content = $content . $button;
			break;
		case "both":
			$content = $button . $content . $button;
			break;
		case "manual":
		default:
			break;
	}
	return $content;
}
add_filter('the_content', 'stc_tweetbutton_automatic', 30);

// add the admin sections to the stc page
add_action('admin_init', 'stc_tweetbutton_admin_init');
function stc_tweetbutton_admin_init() {
	add_settings_section('stc_tweetbutton', 'Tweet Button Settings', 'stc_tweetbutton_section_callback', 'stc');
	add_settings_field('stc_tweetbutton_source', 'Tweet Source', 'stc_tweetbutton_source', 'stc', 'stc_tweetbutton');
	add_settings_field('stc_tweetbutton_position', 'Tweet Button Position', 'stc_tweetbutton_position', 'stc', 'stc_tweetbutton');
	add_settings_field('stc_tweetbutton_style', 'Tweet Button Style', 'stc_tweetbutton_style', 'stc', 'stc_tweetbutton');
	add_settings_field('stc_tweetbutton_related', 'Tweet Button related', 'stc_tweetbutton_related', 'stc', 'stc_tweetbutton');
}

function stc_tweetbutton_section_callback() {
	echo '<p>Choose where you want the Tweetbutton button to add the button in your content.</p>';
}

function stc_tweetbutton_source() {
	$options = get_option('stc_options');
	if (!$options['tweetbutton_source']) $options['tweetbutton_source'] = 'ottodestruct';
	echo "<input type='text' id='stc-tweetbutton-source' name='stc_options[tweetbutton_source]' value='{$options['tweetbutton_source']}' size='40' /> (Username that appears to be RT'd)";
}

function stc_tweetbutton_position() {
	$options = get_option('stc_options');
	if (!$options['tweetbutton_position']) $options['tweetbutton_position'] = 'manual';
	?>
	<p><label><input type="radio" name="stc_options[tweetbutton_position]" value="before" <?php checked('before', $options['tweetbutton_position']); ?> /> Before the content of your post</label></p>
	<p><label><input type="radio" name="stc_options[tweetbutton_position]" value="after" <?php checked('after', $options['tweetbutton_position']); ?> /> After the content of your post</label></p>
	<p><label><input type="radio" name="stc_options[tweetbutton_position]" value="both" <?php checked('both', $options['tweetbutton_position']); ?> /> Before AND After the content of your post </label></p>
	<p><label><input type="radio" name="stc_options[tweetbutton_position]" value="manual" <?php checked('manual', $options['tweetbutton_position']); ?> /> Manually add the button to your theme or posts (use the stc_tweetbutton function in your theme, or the [tweetbutton] shortcode in your posts)</label></p>
<?php 
}

function stc_tweetbutton_style() {
	$options = get_option('stc_options');
	if (!$options['tweetbutton_style']) $options['tweetbutton_style'] = 'manual';
	?>
	<select name="stc_options[tweetbutton_style]" id="stc_select_tweetbutton_position">
	<option value="none" <?php selected('none', $options['tweetbutton_style']); ?>><?php _e('None', 'stc'); ?></option>
	<option value="horizontal" <?php selected('horizontal', $options['tweetbutton_style']); ?>><?php _e('Horizonal', 'stc'); ?></option>
	<option value="vertical" <?php selected('vertical', $options['tweetbutton_style']); ?>><?php _e('Vertical', 'stc'); ?></option>
	</select>
<?php
}

function stc_tweetbutton_related() {
	$options = get_option('stc_options');
	if (!$options['tweetbutton_related']) $options['tweetbutton_related'] = '';
	echo "<input type='text' id='stc-tweetbutton-related' name='stc_options[tweetbutton_related]' value='{$options['tweetbutton_related']}' size='40' /> Users that the person will be suggested to follow. Max 2, separate with comma. Example: otto42,ottodestruct";

}

add_filter('stc_validate_options','stc_tweetbutton_validate_options');
function stc_tweetbutton_validate_options($input) {
	if (!in_array($input['tweetbutton_position'], array('before', 'after', 'both', 'manual'))) {
			$input['tweetbutton_position'] = 'manual';
	}

	if (!in_array($input['tweetbutton_style'], array('none', 'horizontal', 'vertical'))) {
			$input['tweetbutton_style'] = 'none';
	}
	
	if (!$input['tweetbutton_source']) $input['tweetbutton_source'] = '';
	else {
		// only alnum and underscore allowed in twitter names
		$input['tweetbutton_source'] = preg_replace('/[^a-zA-Z0-9_\s]/', '', $input['tweetbutton_source']);
	}

	if (!$input['tweetbutton_related']) $input['tweetbutton_related'] = '';
	else {
		// only alnum and underscore allowed in twitter names
		$input['tweetbutton_related'] = preg_replace('/[^a-zA-Z0-9_,:\s]/', '', $input['tweetbutton_related']);
	}

	return $input;
}
