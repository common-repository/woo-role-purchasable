<?php
/**
 * class-woorolepurchasable.php
 *
 * Copyright (c) Antonio Blanco http://www.blancoleon.com
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
 * @author Antonio Blanco
 * @package woorolepurchasable
 * @since woorolepurchasable 1.0.0
 */

/**
 * WooRolePurchasable class
 */
class WooRolePurchasable {

	public static function init() {

		add_filter('woocommerce_is_purchasable', array( __CLASS__, 'woocommerce_is_purchasable' ), 10, 2);

	}

	public static function woocommerce_is_purchasable ( $purchasable, $product ) {
		global $wp_roles;

		if ( !is_admin() ) {
			$guest = !is_user_logged_in();
			if ( $guest ) {
				if ( $option_guest = get_option( "wrpur-guest", 0 ) ) {
					$purchasable = $purchasable && ( ( $option_guest==0 )?false:true );
				} else {
					$purchasable = 0;
				}
			}
			$user = wp_get_current_user();
			$user_roles = $user->roles;
			foreach ( $user_roles as $role ) {
				if ( $option_role = get_option( "wrpur-" . $role, 0 ) ) {
					$purchasable = $purchasable && ( ( $option_role==0 )?false:true );  
				} else {
					$purchasable = 0;
				}
			}
		}
		return $purchasable;
	}
}
WooRolePurchasable::init();
