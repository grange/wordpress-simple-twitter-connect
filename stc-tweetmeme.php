<?php
/*
Plugin Name: STC - TweetMeme Button
Plugin URI: http://ottopress.com/wordpress-plugins/simple-twitter-connect/
Description: Adds a Tweetmeme button to your content.
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
function stc_tweetmeme_activation_check(){
	if (function_exists('stc_version')) {
		if (version_compare(stc_version(), '0.1', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die("The base stc plugin must be activated before this plugin will run.");
}
register_activation_hook(__FILE__, 'stc_tweetmeme_activation_check');

/**
 * Simple tweetmeme button
 *
 * @param string $source Source that the RT will appear to be from.
 * @param int $post_id An optional post ID.
 */
function get_stc_tweetmeme_button($source = '', $id = 0) {
	if (!$source) {
		$options = get_option('stc_options');
		if (!$options['tweetmeme_source']) $source = 'tweetmeme';
		else $source = $options['tweetmeme_source'];
	}
	$url = get_permalink($id);
	$out =  "<script type='text/javascript'>\n";
	$out .= "<!--\n";
	$out .= "tweetmeme_source = '{$source}';\n";
	$out .= "tweetmeme_url = '{$url}';\n";
	$out .= "//-->\n";
	$out .= "</script>";
	$out .= '<script type="text/javascript" src="http://tweetmeme.com/i/scripts/button.js"></script>';
	return $out;
}

function stc_tweetmeme_button($source = '', $id = 0) {
	echo get_stc_tweetmeme_button($source,$id);
}

/**
 * Simple tweetmeme button as a shortcode
 *
 * Example use: [tweetmeme source="tweetmeme"] or [tweetmeme id="123"]
 */
function stc_tweetmeme_shortcode($atts) {
	$options = get_option('stc_options');
	extract(shortcode_atts(array(
		'source' => '',
		'id' => 0,
	), $atts));
	return get_stc_tweetmeme_button($source,$id);
}

add_shortcode('tweetmeme', 'stc_tweetmeme_shortcode');

function stc_tweetmeme_button_automatic($content) {
	$options = get_option('stc_options');
	$button = get_stc_tweetmeme_button($options['tweetmeme_source']);
	switch ($options['tweetmeme_position']) {
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
add_filter('the_content', 'stc_tweetmeme_button_automatic', 30);

// add the admin sections to the stc page
add_action('admin_init', 'stc_tweetmeme_admin_init');
function stc_tweetmeme_admin_init() {
	add_settings_section('stc_tweetmeme', 'Tweetmeme Button Settings', 'stc_tweetmeme_section_callback', 'stc');
	add_settings_field('stc_tweetmeme_source', 'Tweetmeme Source', 'stc_tweetmeme_source', 'stc', 'stc_tweetmeme');
	add_settings_field('stc_tweetmeme_position', 'Tweetmeme Button Position', 'stc_tweetmeme_position', 'stc', 'stc_tweetmeme');
}

function stc_tweetmeme_section_callback() {
	echo '<p>Choose where you want the Tweetmeme button to add the button in your content.</p>';
}

function stc_tweetmeme_source() {
	$options = get_option('stc_options');
	if (!$options['tweetmeme_source']) $options['tweetmeme_source'] = 'tweetmeme';
	echo "<input type='text' id='stc-tweetmeme-source' name='stc_options[tweetmeme_source]' value='{$options['tweetmeme_source']}' size='40' /> (Username that appears to be RT'd)";
}

function stc_tweetmeme_position() {
	$options = get_option('stc_options');
	if (!$options['tweetmeme_position']) $options['tweetmeme_position'] = 'manual';
	?>
	<p><label><input type="radio" name="stc_options[tweetmeme_position]" value="before" <?php checked('before', $options['tweetmeme_position']); ?> /> Before the content of your post</label></p>
	<p><label><input type="radio" name="stc_options[tweetmeme_position]" value="after" <?php checked('after', $options['tweetmeme_position']); ?> /> After the content of your post</label></p>
	<p><label><input type="radio" name="stc_options[tweetmeme_position]" value="both" <?php checked('both', $options['tweetmeme_position']); ?> /> Before AND After the content of your post </label></p>
	<p><label><input type="radio" name="stc_options[tweetmeme_position]" value="manual" <?php checked('manual', $options['tweetmeme_position']); ?> /> Manually add the button to your theme or posts (use the stc_tweetmeme_button function in your theme, or the [tweetmeme] shortcode in your posts)</label></p>
<?php 
}

add_filter('stc_validate_options','stc_tweetmeme_validate_options');
function stc_tweetmeme_validate_options($input) {
	if (!in_array($input['tweetmeme_position'], array('before', 'after', 'both', 'manual'))) {
			$input['tweetmeme_position'] = 'manual';
	}
	
	if (!$input['tweetmeme_source']) $input['tweetmeme_source'] = 'tweetmeme';
	else {
		// only alnum and underscore allowed in twitter names
		$input['tweetmeme_source'] = preg_replace('/[^a-zA-Z0-9_\s]/', '', $input['tweetmeme_source']);
	}

	return $input;
}
