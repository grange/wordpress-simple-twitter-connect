<?php
/*
Plugin Name: STC - Twitter Dashboard
Plugin URI: http://www.avendimedia.com
Description: Allows you to tweet from the WordPress dashboard.
Author: John Bloch - Avendi Media, Inc.
Version: 0.15
Author URI: http://www.avendimedia.com
License: GPL2

    Copyright 2010  Avendi Media, Inc. (email info@avendimedia.com)

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


GPLv2 required notice: Modified by Otto on May 20, 2010

*/

// check for version 0.7 of base, as that's when we added anywhere support
function stc_dashboard_activation_check(){
	if (function_exists('stc_version')) {
		if (version_compare(stc_version(), '0.7', '>=')) {
			return;
		}
	}
	deactivate_plugins(basename(__FILE__)); // Deactivate ourself
	wp_die("The base STC plugin must be activated before this plugin will run.");
}
register_activation_hook(__FILE__, 'stc_dashboard_activation_check');

// If the current user can publish posts, add the widget
function stc_add_dashboard_widget_wrapper(){
	if(current_user_can('publish_posts'))
		wp_add_dashboard_widget( 'stc_twitter_publish', 'Twitter Publish', 'stc_add_dashboard_widget_callback' );
}

// function to display the appropriate content in the widget
add_action('wp_dashboard_setup','stc_add_dashboard_widget_wrapper');
function stc_add_dashboard_widget_callback(){
?><div id="stc-publish-buttons">
<div id="stc-manual-tweetbox" style="width:auto; padding-right:10px;"></div>
<script type="text/javascript">
  var tbox=new Array();
  tbox['height'] = 100;
  tbox['width'] = jQuery('#stc-manual-tweetbox').width();
  tbox['defaultContent'] = '';
  tbox['label'] = 'Tweet this:';
  twttr.anywhere(function (T) {
    T("#stc-manual-tweetbox").tweetBox(tbox);
  });
</script>
</div>
<?php
}
