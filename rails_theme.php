<?php
/*
Plugin Name: Rails Theme
Plugin URI: http://www.performantsoftware.com/wordpress/plugins/rails_theme/
Description: This plugin causes WP to call into a Rails app to get stylesheets, javascripts, header, and footer info. This allows WP to seamlessly be integrated into a rails app.
Version: 1.1.1.0
Author: Paul Rosen
Author URI: http://paulrosen.net
License: GPL version 2 or later - http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
*/

/*
Copyright (C) 2011, 2012 Paul Rosen

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

/////////////////////////////////////////////////////
// options in the admin section
////////////////////////////////////////////////////

//------------- Add the admin menu -------------
function rails_admin_options() {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	echo '<div class="wrap">';
	echo '<h2>Rails Theme Options</h2>';
	echo 'Options for the Rails Theme Plugin';
	echo '<form action="options.php" method="post">';
	echo settings_fields("rails_theme_options");
	echo do_settings_sections("rails_theme");
	echo '<input name="Submit" type="submit" value="Save Changes" />';
	echo '</form></div>';
}

function rails_modify_menu() {
	add_options_page('Rails Theme', 'Rails Theme', 'manage_options', __FILE__, 'rails_admin_options');
}

add_action('admin_menu', 'rails_modify_menu');

//----------------- Add the settings for this plugin ---------------------
function rails_theme_admin_init(){
	register_setting( 'rails_theme_options', 'rails_theme_options', 'rails_theme_options_validate' );
	add_settings_section('rails_theme_main', 'Settings', 'rails_theme_section_text', 'rails_theme');
	add_settings_field('rails_theme_text_string', 'Base URL (e.g. http://example.com)', 'rails_theme_setting_string', 'rails_theme', 'rails_theme_main');
	add_settings_field('body_class_text_string', 'Body classes', 'body_class_text_string', 'rails_theme', 'rails_theme_main');
}

add_action('admin_init', 'rails_theme_admin_init');

function rails_theme_section_text() {
echo '<p>Set the location of the rails web service that will respond to the urls: /styles, /header, and /footer.</p>';
}

function rails_theme_setting_string() {
	$options = get_option('rails_theme_options');
	echo "<input id='rails_theme_text_string' name='rails_theme_options[url]' size='40' type='text' value='" . $options['url'] . "' />";
}

function body_class_text_string() {
	$options = get_option('rails_theme_options');
	echo "<input id='body_class_text_string' name='rails_theme_options[classes]' size='40' type='text' value='" . $options['classes'] . "' />";
}

function rails_theme_options_validate($input) {
	$newinput['url'] = trim($input['url']);
	$newinput['classes'] = trim($input['classes']);
	return $newinput;
}

/////////////////////////////////////////////////////
// Add specified CSS class by filter
/////////////////////////////////////////////////////

function rails_themes_names($classes) {
	$options = get_option('rails_theme_options');
	$classes[] = $options['classes'];
	// return the $classes array
	return $classes;
}

add_filter('body_class','rails_themes_names');

/////////////////////////////////////////////////////
// Calling rails app
/////////////////////////////////////////////////////

$rails_style = '';
$rails_head = '';
$rails_foot = '';
$divider = "~~~~~~~~~~";

function warn_if_debugging($msg) {
	if ( defined('WP_DEBUG') and WP_DEBUG == true )	
		echo $msg;
}

function call_http($URL) {
	global $divider;

	// Initialize the library if we've never called it before.
	if( !class_exists( 'WP_Http' ) )
		include_once( ABSPATH . WPINC. '/class-http.php' );

	$request = new WP_Http;
	//
	// NOTE: Passing the cookies through to rails no longer works. Rails is now smart enough to see that
	//  the request is coming from a different server, and invalidates the session. However, most of the
	// view is the same whether there is a cookie or not, so this method is still helpful. Typically the only
	// thing that is dependant on a cookie is the login area, and that can be ajaxed for in the client.
	//

	// Get and pass the page cookies through to the rails call.
	// We get all the cookies here, then need to divide them into individual cookies to send off.
	// $cookies_str = explode(';', $_SERVER['HTTP_COOKIE']);
	// $cookies = array();
	// foreach ( (array) $cookies_str as $cookie ) {
	// 	$cookies[] = new WP_Http_Cookie($cookie);
	// }

	// Call the rails server with the cookies
	// $result = $request->request( $URL, array('cookies' => $cookies ) );
	$result = $request->request( $URL );
	if ( is_wp_error($result) ) {
		warn_if_debugging("Error connecting to (" . $URL . "): " . $result->get_error_message());
		return "";
	} else if ($result['response']['code'] == 200)
	    return $result['body'];
	else {
		warn_if_debugging("Error connecting to (" . $URL . "): " . $result['response']['message']);
		return "";
	}
}

function load_all() {
	global $rails_head, $rails_style, $rails_foot, $divider;
	if ($rails_head == '') {
		$options = get_option('rails_theme_options');
		$base_url = $options['url'];

		$page = is_page() ? 'page' : 'post';
		$req = $base_url . "/wrapper?style=" . $page;
		$wrapper = "";
		$retries = 7;
		while ($wrapper == "" && $retries > 0) {
			$wrapper = call_http($req);
			$retries = $retries - 1;
		}
		if ($wrapper != "") {
			$arr = explode($divider, $wrapper);
			$rails_style = $arr[0];
			$rails_style = str_replace("/stylesheets", $base_url . "/stylesheets", $rails_style);
			$rails_style = str_replace("/javascripts", $base_url . "/javascripts", $rails_style);

			$rails_head = $arr[1];
			$rails_head = str_replace("href='/", "href='" . $base_url . "/", $rails_head);

			$rails_foot = $arr[2];
		} else {
			$rails_style = " ";
			$rails_head = " ";
			$rails_foot = " ";
		}
	}
}

add_action ( 'wp_head', 'load_stylesheets');

function load_stylesheets() {
	global $rails_style;
	load_all();
	echo $rails_style;
}

add_action ( 'hybrid_before_html', 'load_header');

function load_header() {
	global $rails_head;
	load_all();
	echo $rails_head;
}

add_action( 'wp_footer', 'load_footer' );

function load_footer() {
	global $rails_foot;
	load_all();
	echo $rails_foot;
}

?>
