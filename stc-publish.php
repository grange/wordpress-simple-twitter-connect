<?php
/*
Plugin Name: STC - Publish
Plugin URI: http://ottopress.com/wordpress-plugins/simple-twitter-connect/
Description: Allows you to tweet your posts to a Twitter account. Activate this plugin, then look on the Edit Post pages for Twitter posting buttons.
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
function stc_publish_activation_check(){
	if (function_exists('stc_version')) {
		if (version_compare(stc_version(), '0.5', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die("The base STC plugin must be activated before this plugin will run.");
}
register_activation_hook(__FILE__, 'stc_publish_activation_check');

// add the meta boxes
add_action('admin_menu', 'stc_publish_meta_box_add');
function stc_publish_meta_box_add() {
	add_meta_box('stc-publish-div', 'Twitter Publisher', 'stc_publish_meta_box', 'post', 'side');
	add_meta_box('stc-publish-div', 'Twitter Publisher', 'stc_publish_meta_box', 'page', 'side');
}

// add the admin sections to the stc page
add_action('admin_init', 'stc_publish_admin_init');
function stc_publish_admin_init() {
	add_settings_section('stc_publish', 'Publish Settings', 'stc_publish_section_callback', 'stc');
	add_settings_field('stc_publish_flags', 'Automatic Publishing', 'stc_publish_auto_callback', 'stc', 'stc_publish');
	add_settings_field('stc_publish_text', 'Publish Tweet Text', 'stc_publish_text', 'stc', 'stc_publish');
	wp_enqueue_script('jquery');
}

function stc_publish_section_callback() {
	echo "<p>Settings for the STC-Publish plugin. The manual Twitter Publishing buttons can be found on the Edit Post or Edit Page screen, after you publish a post. If you can't find them, try scrolling down or seeing if you have the box disabled in the Options dropdown.</p>";
}

function stc_publish_auto_callback() {
	$options = get_option('stc_options');
	if (!$options['autotweet_flag']) $options['autotweet_flag'] = false;
	?>
	<p><label>Automatically Tweet on Publish: <input type="checkbox" name="stc_options[autotweet_flag]" value="1" <?php checked('1', $options['autotweet_flag']); ?> /></label></p>
	<?php
	$tw = stc_get_credentials(true);
	if ($tw->screen_name) echo "<p>Currently logged in as: <strong>{$tw->screen_name}</strong></p>";

	if ($options['autotweet_name']) {
		echo "<p>Autotweet set to Twitter User: <strong>{$options['autotweet_name']}</strong></p>";
	} else {
		echo "<p>Autotweet not set to a Twitter user.</p>";
	}
	echo '<p>To auto-publish new posts to any Twitter account, click this button and then log into that account to give the plugin access.</p><p>Authenticate for auto-tweeting: '.stc_get_connect_button('publish_preauth', 'authorize').'</p>';
	echo '<p>Afterwards, you can use this button to log back into your own normal account, if you are posting to a different account than your normal one. </p><p>Normal authentication: '.stc_get_connect_button('', 'authorize').'</p>';
}

function stc_publish_text() {
	$options = get_option('stc_options');
	if (!$options['publish_text']) $options['publish_text'] = '%title%: %url%';

	echo "<input type='text' name='stc_options[publish_text]' value='{$options['publish_text']}' size='40' /><br />";
	echo '<p>Use %title% for the post title.</p>';
	echo '<p>Use %url% for the post link (or shortlink).</p>';
}

add_action('stc_publish_preauth','stc_publish_preauth');
function stc_publish_preauth() {
	if ( ! current_user_can('manage_options') )
		wp_die(__('You do not have sufficient permissions to manage options for this blog.'));

	$tw = stc_get_credentials(true);

	$options = get_option('stc_options');

	// save the special settings
	if ($tw->screen_name) {
		$options['autotweet_name'] = $tw->screen_name;
		$options['autotweet_token'] = $_SESSION['stc_acc_token'];
		$options['autotweet_secret'] = $_SESSION['stc_acc_secret'];
	}

	update_option('stc_options', $options);
}

function stc_publish_meta_box( $post ) {
	$options = get_option('stc_options');

	if ($post->post_status == 'private') {
		echo '<p>Why would you put private posts on Twitter, for all to see?</p>';
		return;
	}

	if ($post->post_status !== 'publish') {
		echo '<p>After publishing the post, you can send it to Twitter from here.</p>';
		return;
	}

?><div id="stc-publish-buttons">
<div id="stc-manual-tweetbox" style="width:auto; padding-right:10px;"></div>
<script type="text/javascript">
  var tbox=new Array();
  tbox['height'] = 100;
  tbox['width'] = jQuery('#stc-manual-tweetbox').width();
  tbox['defaultContent'] = <?php echo json_encode(stc_get_default_tweet($post->ID)); ?>;
  tbox['label'] = 'Tweet this:';
  twttr.anywhere(function (T) {
    T("#stc-manual-tweetbox").tweetBox(tbox);
  });
</script>
</div>
<?php
}

// this new function prevents edits to existing posts from auto-posting
add_action('transition_post_status','stc_publish_auto_check',10,3);
function stc_publish_auto_check($new, $old, $post) {
	if ($new == 'publish' && $old != 'publish') {
		$post_types = get_post_types( array('public' => true), 'objects' );
		foreach ( $post_types as $post_type ) {
			if ( $post->post_type == $post_type->name ) {
				stc_publish_automatic($post->ID, $post);
				break;
			}
		}
	}
}

function stc_publish_automatic($id, $post) {

	// check to make sure post is published
	if ($post->post_status !== 'publish') return;

	// check options to see if we need to send to FB at all
	$options = get_option('stc_options');
	if (!$options['autotweet_flag'] || !$options['autotweet_token'] || !$options['autotweet_secret'] || !$options['publish_text'])
		return;

	// args to send to twitter
	$args=array();

	$args['status'] = stc_get_default_tweet($id);

	$args['acc_token'] = $options['autotweet_token'];
	$args['acc_secret'] = $options['autotweet_secret'];

	$resp = stc_do_request('http://api.twitter.com/1/statuses/update',$args);
}

function stc_get_default_tweet($id) {
	$options = get_option('stc_options');
	$link = '';
	
	if (function_exists('wp_get_shortlink')) {
		// use the shortlink if it's available
		$link = wp_get_shortlink($id);
	}
	
	if (empty($link)) {
		// no shortlink, try the full permalink
		$link = get_permalink($id);
	}
	
	$link = apply_filters('stc_publish_link', $link, $id);

	$output = $options['publish_text'];
	$title = str_replace('&nbsp;',' ',get_the_title($id) ); 
	$output = str_replace('%title%', $title, $output );
	$output = str_replace('%url%', $link, $output );

	$output = apply_filters('stc_publish_text', $output, $id);

	return $output;
}

add_filter('stc_validate_options','stc_publish_validate_options');
function stc_publish_validate_options($input) {
	$options = get_option('stc_options');
	if ($input['autotweet_flag'] != 1) $input['autotweet_flag'] = 0;

	$input['publish_text'] = trim($input['publish_text']);

	// preserve existing vars which are not inputs
	$input['autotweet_name'] = $options['autotweet_name'];
	$input['autotweet_token'] = $options['autotweet_token'];
	$input['autotweet_secret'] = $options['autotweet_secret'];

	return $input;
}
