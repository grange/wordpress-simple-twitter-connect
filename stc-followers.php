<?php
/*
Plugin Name: STC - Followers Widget
Plugin URI: http://ottopress.com/wordpress-plugins/simple-twitter-connect/
Description: Show a list of your followers in the sidebar. See plugin code for CSS styling to add to your theme.
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
    
Example CSS to use in the theme:

.stc-follower-username{
text-align:center;
font-size:15px;
margin:0;
}

.stc-follower-username a {
color:blue;
}

.stc-follower-box {
border:1px #94a3c4 solid;
background:#fff;
width:250px;
}

.stc-follower-head {
background:#eceff5;
border-bottom:1px #d8dfea solid;
margin-bottom:6px;
}

.stc-follower {
float:left;
width:50px;
height:70px;
}

.stc-follower-name {
text-align:center;
width:50px;
font-size:9px;
overflow:hidden;
}

*/

// default cache time to 24 hours
if (!defined('STC_FOLLOWER_CACHE')) 
	define('STC_FOLLOWER_CACHE',60*60*24);

// checks for stc on activation
function stc_followers_activation_check(){
	if (function_exists('stc_version') && function_exists('stc_publish_activation_check')) {
		if (version_compare(stc_version(), '0.10', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die("The Simple Twitter Connect and STC-Publish plugins must both be activated before this plugin will run.");
}
register_activation_hook(__FILE__, 'stc_followers_activation_check');

// gets a list of follower IDs from twitter
function stc_followers_get($username) {
	// check the cache first
	$resp = get_transient("stc_followers_{$username}");
	if ($resp != false) return $resp;

	$options = get_option('stc_options');
	
	if (!$username || !$options['autotweet_token'] || !$options['autotweet_secret']) return;

	$args=array();
	$args['acc_token'] = $options['autotweet_token'];
	$args['acc_secret'] = $options['autotweet_secret'];
	$args['screen_name']=$username;
	$args['cursor']=-1;
	
	$resp = stc_do_request('http://api.twitter.com/1/followers/ids',$args, 'GET');
	
	if (!$resp) return array();
	
	// save the count for later
	set_transient("stc_followers_{$username}_count", count($resp->ids), STC_FOLLOWER_CACHE);
	
	// $resp is an array of up to 5000 of our followers. Grab 100 of them at random.
	$fols = $resp->ids;
	$fols = array_rand(array_flip($fols),100);
	
	// we now have 100 integer IDs. So let's go look up their names and such:

	$args=array();
	$args['acc_token'] = $options['autotweet_token'];
	$args['acc_secret'] = $options['autotweet_secret'];
	$args['user_id']=implode($fols,',');
	
	$resp = stc_do_request('http://api.twitter.com/1/users/lookup',$args, 'GET');
	
	// $resp should now be an array of up to 100 twitter user objects.
	
	set_transient("stc_followers_{$username}", $resp, STC_FOLLOWER_CACHE); // cache the result
	
	return $resp;
}

// returns a count set of followers (if we've gotten them yet)
function stc_count_followers($username) {
	return get_transient("stc_followers_{$username}_count");
}

// returns an array of random followers (12 by default)
function stc_random_followers($username, $count = 12) {
	$list = stc_followers_get($username);
	if (count($list) == 0) return array();
	if (count($list) < $count) $count = count($list);
	shuffle($list);
	return array_slice($list,0,$count);
}

function get_stc_follower_box($username, $count = 12) {
	$resp = "<div class='stc-follower-box'><div class='stc-follower-head'>";
	$resp .="<p class='stc-follower-username'><a href='http://twitter.com/{$username}'>{$username} on Twitter</a></p>";
	$resp .='<p class="stc-follower-count">'.stc_count_followers($username).' Followers</p></div>';
	$fols = stc_random_followers($username, $count);
	foreach ($fols as $fol) {
		$resp .='<div class="stc-follower">';
		$resp .="<a href='http://twitter.com/{$fol->screen_name}'>";
		$resp .="<img src='{$fol->profile_image_url}' width='48' height='48' />";
		$resp .="<p class='stc-follower-name'>{$fol->screen_name}</p>";
		$resp .='</a></div>';
	}
	$resp .= '<br style="clear:left;" /></div>';
	return $resp;
}

// display the follower box
function stc_follower_box($username, $count = 12) {
	echo get_stc_follower_box($username, $count);
}

class STC_Followers_Widget extends WP_Widget {
	function STC_Followers_Widget() {
		$widget_ops = array('classname' => 'widget_stc-followers', 'description' => 'Twitter Followers List');
		$this->WP_Widget('stc-followers', 'Twitter Followers List (STC)', $widget_ops);
	}

	function widget($args, $instance) {
		extract( $args );
		$title = apply_filters('widget_title', $instance['title']);
		?>
		<?php echo $before_widget; ?>
		<?php if ( $title ) echo $before_title . $title . $after_title; ?>
		<?php stc_follower_box($instance['user'], $instance['count']); ?>
		<?php echo $after_widget; ?>
		<?php
	}

	function update($new_instance, $old_instance) {
		$options = get_option('stc_options');
		if (empty($options['autotweet_name'])) $defaultuser = '';
		else $defaultuser = $options['autotweet_name'];
		
		$instance = $old_instance;
		$new_instance = wp_parse_args( (array) $new_instance, array( 'title' => '', 'user' => $defaultuser, $count=>12) );
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['user'] = strip_tags($new_instance['user']);
		$instance['count'] = intval($new_instance['count']);
		return $instance;
	}

	function form($instance) {
		$options = get_option('stc_options');
		if (empty($options['autotweet_name'])) $defaultuser = ''; 
		else $defaultuser = $options['autotweet_name'];
		
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'user' => $defaultuser, $count=>12) );
		$title = strip_tags($instance['title']);
		$user = strip_tags($instance['user']);
		$count = intval($instance['count']);
		
		if (!$options['autotweet_token'] || !$options['autotweet_secret']) {
			echo '<p>Warning: The Autotweet user of the STC-Publish plugin must be set to a valid user for this plugin to be able to get follower lists from Twitter.</p>';
		}
		?>
<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('user'); ?>"><?php _e('Username:'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('user'); ?>" name="<?php echo $this->get_field_name('user'); ?>" type="text" value="<?php echo $user; ?>" />
</label></p>
<p><label for="<?php echo $this->get_field_id('count'); ?>"><?php _e('Count: (max 100)'); ?> 
<input class="widefat" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>" type="text" value="<?php echo $count; ?>" />
</label></p>
		<?php
	}
}
add_action('widgets_init', create_function('', 'return register_widget("STC_Followers_Widget");'));

