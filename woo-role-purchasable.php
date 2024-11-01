<?php
/**
 * woo-role-purchasable.php
 *
 * Copyright (c) 2016 Antonio Blanco (eggemplo) http://www.eggemplo.com
 *
 * This code is released under the GNU General Public License.
 * See COPYRIGHT.txt and LICENSE.txt.
 *
 * This code is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This header and all notices must be kept intact.
 *
 * @author Antonio Blanco (eggemplo)	
 * @package woorolepurchasable
 * @since woorolepurchasable 1.0.0
 *
 * Plugin Name: Woo Role Purchasable
 * Plugin URI: http://www.eggemplo.com/plugins/woocommerce-role-purchasable
 * Description: Makes products purchasable according to user's role.
 * Version: 1.0
 * Author: eggemplo
 * Author URI: http://www.eggemplo.com
 * Text Domain: woorolepurchasable
 * Domain Path: /languages
 * License: GPLv3
 */
define ( 'WOO_ROLE_PURCHASABLE_PLUGIN_NAME', 'woo-role-purchasable' );
define ( 'WOO_ROLE_PURCHASABLE_FILE', __FILE__ );
if (! defined ( 'WOO_ROLE_PURCHASABLE_CORE_DIR' )) {
	define ( 'WOO_ROLE_PURCHASABLE_CORE_DIR', WP_PLUGIN_DIR . '/woo-role-purchasable/core' );
}
define ( 'WOO_ROLE_PURCHASABLE_DECIMALS', apply_filters ( 'woo_role_purchasable_num_decimals', 2 ) );

class WooRolePurchasable_Plugin {
	private static $notices = array ();
	public static function init() {
		load_plugin_textdomain ( 'woorolepurchasable', null, WOO_ROLE_PURCHASABLE_PLUGIN_NAME . '/languages' );
		
		register_activation_hook ( WOO_ROLE_PURCHASABLE_FILE, array (
				__CLASS__,
				'activate' 
		) );
		register_deactivation_hook ( WOO_ROLE_PURCHASABLE_FILE, array (
				__CLASS__,
				'deactivate' 
		) );
		
		register_uninstall_hook ( WOO_ROLE_PURCHASABLE_FILE, array (
				__CLASS__,
				'uninstall' 
		) );
		
		add_action ( 'init', array (
				__CLASS__,
				'wp_init' 
		) );
		add_action ( 'admin_notices', array (
				__CLASS__,
				'admin_notices' 
		) );
	}
	public static function wp_init() {
		$active_plugins = get_option( 'active_plugins', array() );
		if ( is_multisite() ) {
			$active_sitewide_plugins = get_site_option( 'active_sitewide_plugins', array() );
			$active_sitewide_plugins = array_keys( $active_sitewide_plugins );
			$active_plugins = array_merge( $active_plugins, $active_sitewide_plugins );
		}

		$woo_is_active = in_array ( 'woocommerce/woocommerce.php', $active_plugins );
		
		if (! $woo_is_active) {
			self::$notices [] = "<div class='error'>" . __ ( 'The <strong>Woocommerce Role Purchasable</strong> plugin requires the <a href="http://wordpress.org/extend/plugins/woocommerce" target="_blank">Woocommerce</a> plugin to be activated.', 'woorolepurchasable' ) . "</div>";
			
			include_once (ABSPATH . 'wp-admin/includes/plugin.php');
			deactivate_plugins ( array (
					__FILE__ 
			) );
		} else {

			add_action ( 'admin_menu', array (
					__CLASS__,
					'admin_menu' 
			), 40 );

			if (! class_exists ( "WooRolePurchasable" )) {
				include_once 'core/class-woorolepurchasable.php';
			}
		}
	}
	public static function register_woorolepurchasable_settings() {
		global $wp_roles;

		register_setting ( 'woorolepurchasable', 'wrpur-method' );
		add_option ( 'wrpur-method', 'rate' ); // by default rate

		// by default all checked
		add_option ( "wrpur-guest", 1 );
		foreach ( $wp_roles->role_objects as $role ) {
			add_option ( "wrpur-" . $role->name, 1 );
		}
	}
	
	public static function admin_notices() {
		if (! empty ( self::$notices )) {
			foreach ( self::$notices as $notice ) {
				echo $notice;
			}
		}
	}
	
	/**
	 * Adds the admin section.
	 */
	public static function admin_menu() {
		$admin_page = add_submenu_page ( 'woocommerce', __ ( 'Role Purchasable' ), __ ( 'Role Purchasable' ), 'manage_options', 'woorolepurchasable', array (
				__CLASS__,
				'woorolepurchasable_settings' 
		) );
	}
	public static function woorolepurchasable_settings() {
		global $wp_roles;
		?>
		<div class="wrap">
			<h2><?php echo __( 'Woocommerce Role Purchasable', 'woorolepurchasable' ); ?></h2>
		<?php
		$alert = "";

		if (class_exists ( 'WP_Roles' )) {
			if (! isset ( $wp_roles )) {
				$wp_roles = new WP_Roles ();
			}
		}

		if (isset ( $_POST ['submit'] )) {
			$alert = __ ( "Saved", 'woorolepurchasable' );

			if ( isset( $_POST[ "enable" ] ) ) {
				add_option( "wrpur-enable",$_POST[ "enable" ] );
				update_option( "wrpur-enable", $_POST[ "enable" ] );
			} else {
				add_option( "wrpur-enable", 0 );
				update_option( "wrpur-enable", 0 );
			}

			if (isset ( $_POST ["wrpur-guest"] ) && ($_POST ["wrpur-guest"] !== "")) {
				add_option ( "wrpur-guest", $_POST ["wrpur-guest"] );
				update_option ( "wrpur-guest", $_POST ["wrpur-guest"] );
			} else {
				add_option ( "wrpur-guest", 0 );
				update_option ( "wrpur-guest", 0 );
			}

			foreach ( $wp_roles->role_objects as $role ) {
				
				if (isset ( $_POST ["wrpur-" . $role->name] ) && ($_POST ["wrpur-" . $role->name] !== "")) {
					add_option ( "wrpur-" . $role->name, $_POST ["wrpur-" . $role->name] );
					update_option ( "wrpur-" . $role->name, $_POST ["wrpur-" . $role->name] );
				} else {
					add_option ( "wrpur-" . $role->name, 0 );
					update_option ( "wrpur-" . $role->name, 0 );
				}
			}
		}
		
		if ($alert != "")
			echo '<div style="background-color: #ffffe0;border: 1px solid #993;padding: 1em;margin-right: 1em;">' . $alert . '</div>';
		
		?>
		<div class="wrap" style="border: 1px solid #ccc; padding: 10px;">
		<form method="post" action="">
			<table class="form-table">
				<tr valign="top">
					<th scope="row"><strong><?php echo __( 'Enable:', 'woorolepurchasable' ); ?></strong></th>
					<td>
						<?php
						$enable = get_option ( "wrpur-enable", 1 );
						$checked = "";
						if ($enable) {
							$checked = "checked";
						}
						?>
						<input type="checkbox" name="enable" value="1" <?php echo $checked; ?> />
					</td>
				</tr>
			</table>

			<h3><?php echo __( 'Roles:', 'woorolepurchasable' ); ?></h3>
			<div class="description">
				If a role is selected then will be purchasable.
			</div>

			<table class="form-table">
			<!--  Guest -->
				<tr valign="top">
					<th scope="row"><?php echo ucwords("Guest") . ':'; ?></th>
					<td>
					<?php
						$enable = get_option ( "wrpur-guest", 0 );
						$checked = "";
						if ($enable) {
							$checked = 'checked="checked"';
						}
						?>
						<input type="checkbox" name="wrpur-guest" value="1" <?php echo $checked; ?> />
					</td>
				</tr>
		
			<?php
				foreach ( $wp_roles->role_objects as $role ) {
					?>
					<tr valign="top">
						<th scope="row"><?php echo ucwords($role->name) . ':'; ?></th>
						<td>
						<?php
							$enable = get_option ( "wrpur-" . $role->name, 0 );
							$checked = "";
							if ($enable) {
								$checked = 'checked="checked"';
							}
							?>
							<input type="checkbox" name="wrpur-<?php echo $role->name;?>" value="1" <?php echo $checked; ?> />
						</td>
					</tr>
					<?php
				}
			?>
			</table>

			<?php submit_button( __( "Save", 'woorolepurchasable' ) ); ?>

			<?php settings_fields( 'woorolepurchasable' ); ?>

		</form>
		</div>
	</div>
	<?php
	}
	
	/**
	 * Plugin activation work.
	 */
	public static function activate() {
		// call register settings function
		self::register_woorolepurchasable_settings();
	}
	
	/**
	 * Plugin deactivation.
	 */
	public static function deactivate() {
	}
	
	/**
	 * Plugin uninstall.
	 * Delete database table.
	 */
	public static function uninstall() {
	}
}
WooRolePurchasable_Plugin::init ();
