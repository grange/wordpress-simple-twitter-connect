<?php
/*
Plugin Name: Simple Twitter Connect - Base
Plugin URI: http://ottopress.com/wordpress-plugins/simple-twitter-connect/
Description: Makes it easy for your site to use Twitter, in a wholly modular way.
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

add_action('init','stc_init');
function stc_init() {
	// fast check for authentication requests on plugin load.
	if (session_id() == '') {
		session_start();
	}
	if(isset($_GET['stc_oauth_start'])) {
		stc_oauth_start();
	}
	if(isset($_GET['oauth_token'])) {
		stc_oauth_confirm();
	}
}

// require PHP 5
function stc_activation_check(){
	if (version_compare(PHP_VERSION, '5.0.0', '<')) {
		deactivate_plugins(basename(__FILE__)); // Deactivate ourself
		wp_die("Sorry, Simple Twitter Connect requires PHP 5 or higher. Ask your host how to enable PHP 5 as the default on your servers.");
	}
}
register_activation_hook(__FILE__, 'stc_activation_check');

function stc_version() {
	return '0.11';
}

// plugin row links
add_filter('plugin_row_meta', 'stc_donate_link', 10, 2);
function stc_donate_link($links, $file) {
	if ($file == plugin_basename(__FILE__)) {
		$links[] = '<a href="'.admin_url('options-general.php?page=stc').'">Settings</a>';
		$links[] = '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=otto%40ottodestruct%2ecom">Donate</a>';
	}
	return $links;
}

// action links
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'stc_settings_link', 10, 1);
function stc_settings_link($links) {
	$links[] = '<a href="'.admin_url('options-general.php?page=stc').'">Settings</a>';
	return $links;
}

// add the admin settings and such
add_action('admin_init', 'stc_admin_init',9); // 9 to force it first, subplugins should use default
function stc_admin_init(){
	$options = get_option('stc_options');
	if (empty($options['consumer_key']) || empty($options['consumer_secret'])) {
		add_action('admin_notices', create_function( '', "echo '<div class=\"error\"><p>".sprintf('Simple Twitter Connect needs configuration information on its <a href="%s">settings</a> page.', admin_url('options-general.php?page=stc'))."</p></div>';" ) );
	}
	wp_enqueue_script('jquery');
	register_setting( 'stc_options', 'stc_options', 'stc_options_validate' );
	add_settings_section('stc_main', 'Main Settings', 'stc_section_text', 'stc');
	if (!defined('STC_CONSUMER_KEY')) add_settings_field('stc_consumer_key', 'Twitter Consumer Key', 'stc_setting_consumer_key', 'stc', 'stc_main');
	if (!defined('STC_CONSUMER_SECRET')) add_settings_field('stc_consumer_secret', 'Twitter Consumer Secret', 'stc_setting_consumer_secret', 'stc', 'stc_main');
	add_settings_field('stc_default_button', 'Twitter Default Button', 'stc_setting_default_button', 'stc', 'stc_main');
}

// add the admin options page
add_action('admin_menu', 'stc_admin_add_page');
function stc_admin_add_page() {
	global $stc_options_page;
	$stc_options_page = add_options_page('Simple Twitter Connect', 'Simple Twitter Connect', 'manage_options', 'stc', 'stc_options_page');
}

function stc_plugin_help($contextual_help, $screen_id, $screen) {

	global $stc_options_page;
	if ($screen_id == $stc_options_page) {

		$home = home_url('/');
		$contextual_help = <<< END
<p>To connect your site to Twitter, you will need a Twitter Application.
If you have already created one, please insert your Consumer Key and Consumer Secret below.</p>
<p><strong>Can't find your key?</strong></p>
<ol>
<li>Get a list of your applications from here: <a target="_blank" href="http://twitter.com/apps">Twitter Application List</a></li>
<li>Select the application you want, then copy and paste the Consumer Key and Consumer Secret from there.</li>
</ol>

<p><strong>Haven't created an application yet?</strong> Don't worry, it's easy!</p>
<ol>
<li>Go to this link to create your application: <a target="_blank" href="http://twitter.com/apps/new">Twitter: Register an Application</a></li>
<li>Important Settings:<ol>
<li>Application Type must be set to "Browser".</li>
<li>Callback URL must be set to <strong>{$home}</strong></li>
<li>Default Access type must be set to "Read and Write".</li>
<li>Use Twitter for login must be checked (enabled).</li>
</ol>
</li>
<li>The other application fields can be set up any way you like.</li>
<li>After creating the application, copy and paste the Consumer Key and Consumer Secret from the Application Details page.</li>
</ol>
END;
	}
	return $contextual_help;
}
add_action('contextual_help', 'stc_plugin_help', 10, 3);

// display the admin options page
function stc_options_page() {
?>
	<div class="wrap">
	<h2>Simple Twitter Connect</h2>
	<p>Options relating to the Simple Twitter Connect plugins.</p>
	<form method="post" action="options.php">
	<?php settings_fields('stc_options'); ?>
	<table><tr><td>
	<?php do_settings_sections('stc'); ?>
	</td><td style='vertical-align:top;'>
	<div style='width:20em; float:right; background: #ffc; border: 1px solid #333; margin: 2px; padding: 5px'>
			<h3 align='center'>About the Author</h3>
		<p><a href="http://ottopress.com/wordpress-plugins/simple-twitter-connect/">Simple Twitter Connect</a> is developed and maintained by <a href="http://ottodestruct.com">Otto</a>.</p>
			<p>He blogs at <a href="http://ottodestruct.com">Nothing To See Here</a> and <a href="http://ottopress.com">Otto on WordPress</a>, posts photos on <a href="http://www.flickr.com/photos/otto42/">Flickr</a>, and chats on <a href="http://twitter.com/otto42">Twitter</a>.</p>
			<p>You can follow his site on either <a href="http://www.facebook.com/apps/application.php?id=116002660893">Facebook</a> or <a href="http://twitter.com/ottodestruct">Twitter</a>, if you like.</p>
			<p>If you'd like to <a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=otto%40ottodestruct%2ecom">buy him a beer</a>, then he'd be perfectly happy to drink it.</p>
		</div>
	<div style='width:20em; float:right; background: #fff; border: 1px solid #333; margin: 2px; padding: 5px'>
		<h3 align='center'>Twitter Status</h3>
		<?php wp_widget_rss_output('http://status.twitter.com/rss',array('show_date' => 1, 'items' => 10) ); ?>
	</div>
	</td></tr></table>
	<p class="submit">
	<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" />
	</p>
	</form>

	</div>

<?php
}

function stc_oauth_start() {
	$options = get_option('stc_options');
	if (empty($options['consumer_key']) || empty($options['consumer_secret'])) return false;
	include_once "twitterOAuth.php";

	$to = new TwitterOAuth($options['consumer_key'], $options['consumer_secret']);
	$tok = $to->getRequestToken();

	$token = $tok['oauth_token'];
	$_SESSION['stc_req_token'] = $token;
	$_SESSION['stc_req_secret'] = $tok['oauth_token_secret'];

	$_SESSION['stc_callback'] = $_GET['loc'];
	$_SESSION['stc_callback_action'] = $_GET['stcaction'];

	if ($_GET['type'] == 'authorize') $url=$to->getAuthorizeURL($token);
	else $url=$to->getAuthenticateURL($token);

	wp_redirect($url);
	exit;
}

function stc_oauth_confirm() {
	$options = get_option('stc_options');
	if (empty($options['consumer_key']) || empty($options['consumer_secret'])) return false;
	include_once "twitterOAuth.php";

	$to = new TwitterOAuth($options['consumer_key'], $options['consumer_secret'], $_SESSION['stc_req_token'], $_SESSION['stc_req_secret']);

	$tok = $to->getAccessToken();

	$_SESSION['stc_acc_token'] = $tok['oauth_token'];
	$_SESSION['stc_acc_secret'] = $tok['oauth_token_secret'];

	$to = new TwitterOAuth($options['consumer_key'], $options['consumer_secret'], $tok['oauth_token'], $tok['oauth_token_secret']);

	// this lets us do things actions on the return from twitter and such
	if ($_SESSION['stc_callback_action']) {
		do_action('stc_'.$_SESSION['stc_callback_action']);
		$_SESSION['stc_callback_action'] = ''; // clear the action
	}

	wp_redirect($_SESSION['stc_callback']);
	exit;
}

// get the user credentials from twitter
function stc_get_credentials($force_check = false) {
	// cache the results in the session so we don't do this over and over
	if (!$force_check && $_SESSION['stc_credentials']) return $_SESSION['stc_credentials'];

	$_SESSION['stc_credentials'] = stc_do_request('http://twitter.com/account/verify_credentials');

	return $_SESSION['stc_credentials'];
}

// json is assumed for this, so don't add .xml or .json to the request URL
function stc_do_request($url, $args = array(), $type = NULL) {

	if ($args['acc_token']) {
		$acc_token = $args['acc_token'];
		unset($args['acc_token']);
	} else {
		$acc_token = $_SESSION['stc_acc_token'];
	}

	if ($args['acc_secret']) {
		$acc_secret = $args['acc_secret'];
		unset($args['acc_secret']);
	} else {
		$acc_secret = $_SESSION['stc_acc_secret'];
	}

	$options = get_option('stc_options');
	if (empty($options['consumer_key']) || empty($options['consumer_secret']) ||
		empty($acc_token) || empty($acc_secret) ) return false;

	include_once "twitterOAuth.php";

	$to = new TwitterOAuth($options['consumer_key'], $options['consumer_secret'], $acc_token, $acc_secret);
	$json = $to->OAuthRequest($url.'.json', $args, $type);

	return json_decode($json);
}

function stc_section_text() {
	$options = get_option('stc_options');
	if (empty($options['consumer_key']) || empty($options['consumer_secret'])) {
?>
<p>To connect your site to Twitter, you will need a Twitter Application.
If you have already created one, please insert your Consumer Key and Consumer Secret below.</p>
<p><strong>Can't find your key?</strong></p>
<ol>
<li>Get a list of your applications from here: <a target="_blank" href="http://twitter.com/apps">Twitter Application List</a></li>
<li>Select the application you want, then copy and paste the Consumer Key and Consumer Secret from there.</li>
</ol>

<p><strong>Haven't created an application yet?</strong> Don't worry, it's easy!</p>
<ol>
<li>Go to this link to create your application: <a target="_blank" href="http://twitter.com/apps/new">Twitter: Register an Application</a></li>
<li>Important Settings:<ol>
<li>Application Type must be set to "Browser".</li>
<li>Callback URL must be set to <strong><?php echo home_url('/') ?></strong></li>
<li>Default Access type must be set to "Read and Write".</li>
<li>Use Twitter for login must be checked (enabled).</li>
</ol>
</li>
<li>The other application fields can be set up any way you like.</li>
<li>After creating the application, copy and paste the Consumer Key and Consumer Secret from the Application Details page.</li>
</ol>
<?php
	}
}

function stc_get_connect_button($action='', $type='authenticate') {
	$options = get_option('stc_options');
	if (empty($options['default_button'])) $options['default_button'] = 'Sign-in-with-Twitter-darker';
	return '<a href="'.get_bloginfo('home').'/?stc_oauth_start=1&stcaction='.urlencode($action).'&loc='.urlencode(stc_get_current_url()).'&type='.urlencode($type).'">'.
		   '<img border="0" src="'.plugins_url('/images/'.$options['default_button'].'.png', __FILE__).'" />'.
		   '</a>';
}

function stc_get_current_url() {
	// build the URL in the address bar
	$requested_url  = ( !empty($_SERVER['HTTPS'] ) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
	$requested_url .= $_SERVER['HTTP_HOST'];
	$requested_url .= $_SERVER['REQUEST_URI'];
	return $requested_url;
}

function stc_setting_consumer_key() {
	if (defined('STC_CONSUMER_KEY')) return;
	$options = get_option('stc_options');
	echo "<input type='text' id='stcconsumerkey' name='stc_options[consumer_key]' value='{$options['consumer_key']}' size='40' /> (required)";
}

function stc_setting_consumer_secret() {
	if (defined('STC_CONSUMER_SECRET')) return;
	$options = get_option('stc_options');
	echo "<input type='text' id='stcconsumersecret' name='stc_options[consumer_secret]' value='{$options['consumer_secret']}' size='40' /> (required)";
}

function stc_setting_default_button() {
	$options = get_option('stc_options');
	if (empty($options['default_button'])) $options['default_button'] = 'Sign-in-with-Twitter-darker';
	?>
	<select name="stc_options[default_button]" id="stc_select_default_button">
	<option value="Sign-in-with-Twitter-darker" <?php selected('Sign-in-with-Twitter-darker', $options['default_button']); ?>><?php _e('Darker', 'stc'); ?></option>
	<option value="Sign-in-with-Twitter-darker-small" <?php selected('Sign-in-with-Twitter-darker-small', $options['default_button']); ?>><?php _e('Darker small', 'stc'); ?></option>
	<option value="Sign-in-with-Twitter-lighter" <?php selected('Sign-in-with-Twitter-lighter', $options['default_button']); ?>><?php _e('Lighter', 'stc'); ?></option>
	<option value="Sign-in-with-Twitter-lighter-small" <?php selected('Sign-in-with-Twitter-lighter-small', $options['default_button']); ?>><?php _e('Lighter small', 'stc'); ?></option>
	</select>
	<br /><br />
	<img id="stc_select_default_button_preview_image" src="<?php echo plugins_url('/images/'.$options['default_button'].'.png', __FILE__); ?>" />
	<script type="text/javascript">
		jQuery(document).ready(function() {
			jQuery("#stc_select_default_button").change(function() {
				var selected = jQuery("#stc_select_default_button").val();
				jQuery("#stc_select_default_button_preview_image").attr('src',"<?php echo plugins_url('/images/', __FILE__); ?>"+selected+".png");
			});
		});
	</script>
<?php
}

// this will override the main options if they are pre-defined
function stc_override_options($options) {
	if (defined('STC_CONSUMER_KEY')) $options['consumer_key'] = STC_CONSUMER_KEY;
	if (defined('STC_CONSUMER_SECRET')) $options['consumer_secret'] = STC_CONSUMER_SECRET;
	return $options;
}
add_filter('option_stc_options', 'stc_override_options');


// validate our options
function stc_options_validate($input) {
	if (!defined('STC_CONSUMER_KEY')) {
		$input['consumer_key'] = trim($input['consumer_key']);
		if(! preg_match('/^[A-Za-z0-9]+$/i', $input['consumer_key'])) {
		  $input['consumer_key'] = '';
		}
	}

	if (!defined('STC_CONSUMER_SECRET')) {
		$input['consumer_secret'] = trim($input['consumer_secret']);
		if(! preg_match('/^[A-Za-z0-9]+$/i', $input['consumer_secret'])) {
		  $input['consumer_secret'] = '';
		}
	}

	$input = apply_filters('stc_validate_options',$input); // filter to let sub-plugins validate their options too
	return $input;
}


// load the @anywhere script
add_action('wp_enqueue_scripts','stc_anywhereloader');
add_action('admin_enqueue_scripts','stc_anywhereloader');
function stc_anywhereloader() {
	$options = get_option('stc_options');

	if (!empty($options['consumer_key'])) {
		wp_enqueue_script( 'twitter-anywhere', "http://platform.twitter.com/anywhere.js?id={$options['consumer_key']}&v=1", array(), '1', false);
	}
}
