<?php
/*
Plugin Name: Shipping by City for BETRS
Plugin URI: http://www.bolderelements.net/shipping-city-woocommerce/
Description: Narrow down your WooCommerce shipping zones based on city names when using the Bolder Elements Table Rate Shipping plugin
Author: Bolder Elements
Author URI: http://www.bolderelements.net/
Version: 1.1

	Copyright: © 2017-2018 Bolder Elements (email : info@bolderelements.net)
	License: GPLv2 or later
	License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

add_action('plugins_loaded', 'woocommerce_shipping_cities_init', 123);

function woocommerce_shipping_cities_init() {

	//Check if WooCommerce is active
	if ( ! class_exists( 'WooCommerce' ) ) return;

	// Check if BETRS is active
	if( ! class_exists( 'BE_Table_Rate_WC' ) ) {
		add_action( 'admin_notices', 'betrs_sc_admin_notice' );
		return;
	}

	// Ensure there are not duplicate classes
	if ( class_exists( 'BE_Shipping_Cities_WC' ) ) return;

	class BE_Shipping_Cities_WC {

		/**
		 * Constructor.
		 */
		public function __construct() {

			add_filter( 'woocommerce_shipping_calculator_enable_city', array( $this, 'enable_city_shipping_calculator' ), 999, 0 );
			add_filter( 'woocommerce_shipping_instance_form_fields_betrs_shipping', array( $this, 'add_settings_section' ), 10, 1 );
			add_filter( 'betrs_custom_restrictions', array( $this, 'compare_shipping_city' ), 10, 3 );
			add_filter( 'betrs_custom_restrictions', array( $this, 'enable_stop_calculations' ), 5, 3 );
		}


		/**
		 * Enable the City field in the Cart's Shipping Calculator
		 *
		 * @access public
		 * @return bool
		 */
		function enable_city_shipping_calculator() {
			
			return true;
		}


		/**
		 * Enable the City field in the Cart's Shipping Calculator
		 *
		 * @access public
		 * @return bool
		 */
		function compare_shipping_city( $results, $package, $method ) {
			// do nothing if not enabled
			if( $method->get_instance_option( 'cities_enabled' ) !== 'yes' ) return $results;

			// get shipping city from package and settings page
			$shipping_city = $package['destination']['city'];
			$accepted_cities = explode( "\n", $method->get_instance_option( 'cities' ) );
			$accepted_cities = array_map( 'trim', $accepted_cities );
			$accepted_cities = array_map( 'strtoupper', $accepted_cities );
			$cities_inc_ex = $method->get_instance_option( 'cities_inc_ex' );

			if( $cities_inc_ex == 'excluding' ) {
				if( in_array( strtoupper( $shipping_city ), $accepted_cities ) )
					$results[] = false;
				else
					$results[] = true;
			} elseif( in_array( strtoupper( $shipping_city ), $accepted_cities ) ) {
				$results[] = true;
			} else {
				$results[] = false;
			}

			// enable 'stop' feature if enabled
			if( in_array( true, $results ) && $method->get_instance_option( 'disable_others' ) === 'yes' ) {
				define( 'BETRS_CITY_SHIPPING_STOP', $method->get_instance_id() );
			}

			return $results;
		}


		/**
		 * Add new section to instance settings
		 *
		 * @access public
		 * @return array
		 */
		function add_settings_section( $instance_settings ) {
			
			$instance_settings['cities'] = array(
				'title'		=> __( 'Shipping By City', 'betrs-sc' ),
				'settings'	=> array(
					'cities_enabled' => array(
						'title' 		=> __( 'Enable/Disable', 'woocommerce' ),
						'type' 			=> 'checkbox',
						'description' 	=> __( 'Further restrict this method to specific cities', 'betrs-sc' ),
						'default' 		=> 'no',
						),
					'cities_inc_ex' => array(
						'title' 		=> __( 'Region is...', 'betrs-sc' ),
						'type' 			=> 'select',
						'options'		=> array(
											'including'	=> __( 'Including Cities', 'betrs-sc' ),
											'excluding'	=> __( 'Excluding Cities', 'betrs-sc' ),
											),
						'default'		=> 'including',
					),
					'cities' => array(
						'title' 		=> __( 'Allowed Cities', 'woocommerce' ),
						'type' 			=> 'textarea',
						'description' 	=> __( 'List one city name per line', 'betrs-sc' ),
						'css'			=> 'height: 150px;'
					),
					'disable_others' => array(
						'title' 		=> __( 'Disable Other Methods', 'woocommerce' ),
						'type' 			=> 'checkbox',
						'description' 	=> __( 'Do not process any Table Rate methods that follow this one when a match is found', 'betrs-sc' ),
						'default' 		=> 'no',
						),
				),
				'priority'	=> 5,
			);

			return $instance_settings;
		}


		/**
		 * Stop processing Table Rate methods when enabled.
		 *
		 * @access public
		 * @return array
		 */
		function enable_stop_calculations( $results, $package, $method ) {

			if( defined( 'BETRS_CITY_SHIPPING_STOP' ) && BETRS_CITY_SHIPPING_STOP !== intval( $method->get_instance_id() ) )
				$results[] = false;

			return $results;
		}

	} // end class BE_Shipping_Cities_WC

	return new BE_Shipping_Cities_WC();

	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'be_shipping_cities_plugin_action_links' );
	function be_shipping_cities_plugin_action_links( $links ) {
		return array_merge(
			array(
				'settings' => '<a href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/admin.php?page=wc-settings&tab=shipping">' . __( 'Settings', 'betrs-sc' ) . '</a>',
				'support' => '<a href="http://bolderelements.net/" target="_blank">' . __( 'Bolder Elements', 'be-table-ship' ) . '</a>'
			),
			$links
		);
	}
} // end function: woocommerce_shipping_cities_init


/**
 * Generate dashboard notification when not properly installed
 *
 * @access public
 * @return void
 */
function betrs_sc_admin_notice() {
?>
<div class="notice notice-error">
    <p style="font-weight: bold;"><?php _e( 'The Shipping by City plugin requires the Table Rate Shipping plugin by Bolder Elements', 'betrs-sc' ); ?></p>
    <p><a href="https://codecanyon.net/item/table-rate-shipping-for-woocommerce/3796656" target="_blank" class="button">
    	<?php _e( 'Purchase Table Rate Shipping', 'betrs-sc' ); ?></a></p>
</div>
<?php
}
	