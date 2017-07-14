<?php
/**
 * Plugin Name: Groups bbPress
 * Plugin URI: http://www.itthinx.com/
 * Description: Groups and bbPress integration - protect bbPress Forums, Topics and Replies using Groups.
 * Version: 1.0.0
 * Author: itthinx
 * Author URI: http://www.itthinx.com
 * Donate-Link: http://www.itthinx.com/product-category/groups/
 * License: GPLv3
 *
 * Copyright (c) 2016 "kento" Karim Rahimpur www.itthinx.com
 *
 * This code is released under the GNU General Public License Version 3.
 * The following additional terms apply to all files as per section
 * "7. Additional Terms." See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * All legal, copyright and license notices and all author attributions
 * must be preserved in all files and user interfaces.
 *
 * Where modified versions of this material are allowed under the applicable
 * license, modified version must be marked as such and the origin of the
 * modified material must be clearly indicated, including the copyright
 * holder, the author and the date of modification and the origin of the
 * modified material.
 *
 * This material may not be used for publicity purposes and the use of
 * names of licensors and authors of this material for publicity purposes
 * is prohibited.
 *
 * The use of trade names, trademarks or service marks, licensor or author
 * names is prohibited unless granted in writing by their respective owners.
 *
 * Where modified versions of this material are allowed under the applicable
 * license, anyone who conveys this material (or modified versions of it) with
 * contractual assumptions of liability to the recipient, for any liability
 * that these contractual assumptions directly impose on those licensors and
 * authors, is required to fully indemnify the licensors and authors of this
 * material.
 *
 * This header and all notices must be kept intact.
 *
 * @author itthinx
 * @package groups-bbpress
 * @since 1.0.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GROUPS_BBPRESS_PLUGIN_VERSION',  '1.0.0' );
define( 'GROUPS_BBPRESS_PLUGIN_NAME',     'groups-bbpress' );
define( 'GROUPS_BBPRESS_PLUGIN_DOMAIN',   'groups-bbpress' );
define( 'GROUPS_BBPRESS_PLUGIN_FILE',     __FILE__ );
define( 'GROUPS_BBPRESS_PLUGIN_BASENAME', plugin_basename( GROUPS_BBPRESS_PLUGIN_FILE ) );
define( 'GROUPS_BBPRESS_PLUGIN_DIR',      WP_PLUGIN_DIR . '/groups-bbpress' );
define( 'GROUPS_BBPRESS_PLUGIN_LIB',      GROUPS_BBPRESS_PLUGIN_DIR . '/lib' );
define( 'GROUPS_BBPRESS_PLUGIN_URL',      WP_PLUGIN_URL . '/groups-bbpress' );

/**
 * Plugin main class.
 */
class Groups_bbPress_Plugin {

	/**
	 * Plugin setup.
	 */
	public static function init() {
		$active_plugins = get_option( 'active_plugins', array() );
		$groups_is_active = in_array( 'groups/groups.php', $active_plugins );
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$groups_is_active =
				$groups_is_active ||
				key_exists( 'groups/groups.php', $active_sitewide_plugins );
		}
		if ( !$groups_is_active ) {
			add_action( 'admin_notices', array( __CLASS__, 'admin_notices' ) );
		} else {
			add_action( 'init', array( __CLASS__, 'wp_init' ) );
			require_once GROUPS_BBPRESS_PLUGIN_LIB . '/class-groups-bbpress.php';
			if ( is_admin() ) {
				//require_once GROUPS_BBPRESS_PLUGIN_LIB . '/admin/class-groups-bbpress-plugin-admin.php';
			}
		}
	}

	/**
	 * Hooked on the init action, loads translations.
	 */
	public static function wp_init() {
		load_plugin_textdomain( GROUPS_BBPRESS_PLUGIN_DOMAIN, null, 'groups-bbpress/languages' );
	}

	/**
	 * Prints an admin notice to install the Groups plugin.
	 */
	public static function admin_notices() {
		if ( current_user_can( 'activate_plugins' ) || current_user_can( 'install_plugins' ) || current_user_can( 'delete_plugins' )) {
			echo '<div class="error">';
			echo '<p>';
			_e( 'Please install <a href="http://wordpress.org/plugins/groups/">Groups</a> to protect bbPress forums, topics and replies with <em>Groups bbPress</em>.', GROUPS_BBPRESS_PLUGIN_DOMAIN );
			echo '</p>';
			echo '</div>';
		}
	}
}
Groups_bbPress_Plugin::init();
