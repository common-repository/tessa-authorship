<?php
/**
 * Plugin Name: Tessa Authorhip
 * Plugin URI: http://takebarcelona.com/tessa-authorship/
 * Description: Add wordpress independent authorship information on posts, pages, create lists of authors, reflect related content on author's page
 * Author: Uli Hake
 * Version: 0.7.7
 * Author URI: http://takebarcelona.com/authorship/uli-hake/
 * @author Uli Hake
 * @since 0.1.0
 */
/*  Copyright 2013 Uli Hake (uli|dot|hake|at|gmail|dot|com) (if not stated otherwise)

	This program including all files in this directory and its subdirectories is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 2, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
require_once( 'classes/tessa-authorship.php' );
require_once( 'classes/tessa-authorship-tasrpas.php' );
require_once( 'classes/tessa-authorship-taxonomy.php' );
require_once( 'tessa-authorship-template.php' );
require_once( 'classes/tessa-authorship-widget.php' );
if ( !defined('TASRPAS_SLUG') ) :
	define( 'TASRPAS_SLUG', 'tessa-authorship' );
endif;
if ( !defined('AUTHORSHIP_POST_SLUG') ) :
	define( 'AUTHORSHIP_POST_SLUG', 'authorship' );
endif;
if ( !defined('AUTHORSHIP_IMAGE_SIZE') ) :
	//we set our Multiple Post Thumbnails compatible image identifier
	//users can overwrite via size argument with integer for pixels (square image) or use an image size identifier set in the theme's setup via function for instance
	define( 'AUTHORSHIP_IMAGE_SIZE', 'authorship-tas-thumbnail' );
endif;

if (is_admin() ) :
	add_action( 'admin_init', 'check_mpt_availability', 0 );
endif;



global $tessa_authorship, $tasrpas_options, $tessa_authorship_thumbnails, $mpt_available;
//load into global
$tessa_authorship = new Tessa_Authorship( __FILE__ );
$tessa_authorship->version = '0.7.7';

register_uninstall_hook( __FILE__, 'tasrpas_uninstaller' );

function tasrpas_uninstaller()
{
	global $wpdb;
	delete_option( 'tasrpas_settings' );
	delete_option( '_mpt_necessity' );
	delete_option( '_mpt_exists' );	
	$wpdb->query( 'DELETE FROM ' . $wpdb->postmeta . ' WHERE meta_key="_tasrpasids"' );
	$wpdb->query( 'DELETE FROM ' . $wpdb->postmeta . ' WHERE meta_key="_saprsatids"' );
}
/* Our fake dependency checker
 * Triggered by admin
 * Available everywhere
 * @since  0.2.0
 */
function check_mpt_availability() {
	global $tasrpas_options, $tessa_authorship_thumbnails;
	if ( !in_array( 'multiple-post-thumbnails/multi-post-thumbnails.php', (array) get_option( 'active_plugins', array() ) ) ) :
		$tasrpas_options = get_option('tasrpas_settings');
		if ($tasrpas_options && isset($tasrpas_options['activate_mpt']) && $tasrpas_options['activate_mpt'] ) :
			update_option('_mpt_necessity', true);
		endif;
	else :
		//update the shorthand for continuous use
		$mpt_necessity = false;
		update_option('_mpt_necessity', false);
		//check if we can
		if ( in_array( 'multiple-post-thumbnails/multi-post-thumbnails.php', (array) get_option( 'active_plugins', array() ) ) && class_exists('MultiPostThumbnails') ) :
			//yes, we can :-)
			//update the shorthand for continuous use
			$mpt_available = true;
			update_option('_mpt_exists', true);
		else :
			//oops, nothing to do about it
			update_option('_mpt_exists', false);
		endif;
	endif;
}

//needs two load cycles to activate but who cares; our fake dependency loader works with minimal impact (and admin interactivity/consent built-in, that's nice!)
if ( get_option('_mpt_necessity') ) :
	/*Philosophy hurts: for people who get here: it's better to install the "Multiple Post Thumbnails" plugin. This is just some kind of proof of concept here as Wordpress does not support dependencies in core, a bit like the SlimJetpack, despite the fact that this does not allow co-existence of JetPack and SlimJetpack. No support for dependencies is a mighty weakness if you start to think twice (and it's all-right)...
	The draw back: I had to modify the bundled class slightly to assure correct load operations from a subdirectory of this plugin; more a file system (reflection) question... Would be great if there existed some kind of a "good coding practice" to allow reusability of classes without overloading a Wordpress instance. Place "compatible" class in your plugin but load only if original plugin is not installed...*/
	$mpt_available = true;
	require_once('classes/multi-post-thumbnails.php' );
	if ( is_admin() ) :
		load_plugin_textdomain( 'multiple-post-thumbnails', FALSE, basename( dirname( __FILE__ ) ) . '/lang/' );
	endif;
elseif ( get_option('_mpt_exists') ) : //obladioblada, admin checked for everybody, including for himself last time, now we enjoy, the state won't change so easily in stable environments)
	$mpt_available = true;
	require_once('classes/multi-post-thumbnails.php' );
	if ( is_admin() ) :
		load_plugin_textdomain( 'multiple-post-thumbnails', FALSE, basename( dirname( __FILE__ ) ) . '/lang/' );
	endif;
endif;
?>
