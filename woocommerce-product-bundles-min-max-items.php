<?php
/*
* Plugin Name: WooCommerce Product Bundles - Min/Max Items
* Plugin URI: http://www.woothemes.com/products/composite-products/
* Description: WooCommerce Product Bundles plugin that allows you to define min/max bundled item count constraints.
* Version: 1.0.2
* Author: SomewhereWarm
* Author URI: http://somewherewarm.net/
* Developer: Manos Psychogyiopoulos
* Developer URI: http://somewherewarm.net/
*
* Text Domain: woocommerce-product-bundles-min-max-items
* Domain Path: /languages/
*
* Requires at least: 3.8
* Tested up to: 4.3
*
* Copyright: © 2009-2015 Manos Psychogyiopoulos.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_PB_Min_Max_Items {

	public static $version        = '1.0.2';
	public static $req_pb_version = '4.11.5';

	public static function plugin_url() {
		return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
	}

	public static function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	public static function init() {

		// Lights on
		add_action( 'plugins_loaded', __CLASS__ . '::load_plugin' );
	}

	/**
	 * Lights on.
	 */

	public static function load_plugin() {

		global $woocommerce_bundles;

		if ( ! empty( $woocommerce_bundles ) && version_compare( $woocommerce_bundles->version, self::$req_pb_version ) < 0 ) {
			add_action( 'admin_notices', __CLASS__ . '::pb_admin_notice' );
			return false;
		}

		// Display min/max qty settings in "Bundled Products" tab
		add_action( 'woocommerce_bundled_products_admin_config', __CLASS__ . '::min_max_count_admin_option' );

		// Save min/max qty settings
		add_action( 'woocommerce_process_product_meta_bundle', __CLASS__ . '::min_max_count_meta' );

		// Validation script
		add_action( 'woocommerce_bundle_add_to_cart', __CLASS__ . '::scripts' );
		add_action( 'woocommerce_composite_show_composited_product_bundle', __CLASS__ . '::scripts' );

		// Add min/max data to template for use by validation script
		add_action( 'woocommerce_before_bundled_items', __CLASS__ . '::min_max_script_data' );
		add_action( 'woocommerce_before_composited_bundled_items', __CLASS__ . '::min_max_script_data' );

		// Cart validation
		add_action( 'woocommerce_add_to_cart_bundle_validation', __CLASS__ . '::min_max_cart_validation', 10, 3 );

		// Change bundled item quantities for min price calculations in PPP mode
		add_filter( 'woocommerce_bundled_item_required_quantities', __CLASS__ . '::min_max_bundled_item_required_quantities', 10, 2 );

		// When min/max qty constraints are present, require input
		add_filter( 'woocommerce_bundle_requires_input', __CLASS__ . '::min_max_bundle_requires_input', 10, 2 );
	}

	/**
	 * PB version check notice.
	 */

	public static function pb_admin_notice() {
	    echo '<div class="error"><p>' . sprintf( __( '&quot;WooCommerce Product Bundles &ndash; Min/Max Items&quot; requires at least Product Bundles version %s in order to function. Please upgrade WooCommerce Product Bundles.', 'woocommerce-product-bundles' ), self::$req_pb_version ) . '</p></div>';
	}

	/**
	 * Admin min/max settings display / save.
	 */

	public static function min_max_count_admin_option() {

		?><div class="options_group"><?php
			woocommerce_wp_text_input( array( 'id' => '_wcpb_min_qty_limit', 'type' => 'number', 'label' => __( 'Min Items', 'woocommerce' ), 'desc_tip' => 'false', 'description' => __( 'Minimum allowed quantity of items in the bundle.', 'woocommerce-product-bundles-min-max-items' ) ) );
			woocommerce_wp_text_input( array( 'id' => '_wcpb_max_qty_limit', 'type' => 'number', 'label' => __( 'Max Items', 'woocommerce' ), 'desc_tip' => 'false', 'description' => __( 'Maximum allowed quantity of items in the bundle.', 'woocommerce-product-bundles-min-max-items' ) ) );
		?></div><?php
	}

	public static function min_max_count_meta( $post_id ) {

		if ( ! empty( $_POST[ '_wcpb_min_qty_limit' ] ) && is_numeric( $_POST[ '_wcpb_min_qty_limit' ] ) ) {
			$min = stripslashes( $_POST[ '_wcpb_min_qty_limit' ] );
			update_post_meta( $post_id, '_wcpb_min_qty_limit', $min );
		} else {
			delete_post_meta( $post_id, '_wcpb_min_qty_limit' );
		}

		if ( ! empty( $_POST[ '_wcpb_max_qty_limit' ] ) && is_numeric( $_POST[ '_wcpb_max_qty_limit' ] ) ) {
			$max = stripslashes( $_POST[ '_wcpb_max_qty_limit' ] );
			update_post_meta( $post_id, '_wcpb_max_qty_limit', $max );
		} else {
			delete_post_meta( $post_id, '_wcpb_max_qty_limit' );
		}
	}

	/**
	 * Apply tabular template modifications.
	 */

	public static function scripts( $the_product = false ) {

		global $product;

		if ( ! $the_product ) {
			$the_product = $product;
		}

		if ( is_object( $the_product ) && $the_product->product_type === 'bundle' ) {
			self::script();
		}
	}

	/**
	 * Validation script.
	 */

	public static function script() {

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'wcpb-min-max-items-add-to-cart', self::plugin_url() . '/assets/js/wcpb-min-max-items-add-to-cart' . $suffix . '.js', array( 'wc-add-to-cart-bundle' ), self::$version );
		wp_enqueue_script( 'wcpb-min-max-items-add-to-cart' );

		$params = array(
			'i18n_min_qty_error_plural'       => __( 'Please select at least %s items.', 'woocommerce-product-bundles-min-max-items' ),
			'i18n_min_qty_error_singular'     => __( 'Please select at least 1 item.', 'woocommerce-product-bundles-min-max-items' ),
			'i18n_max_qty_error_plural'       => __( 'Please select at most %s items.', 'woocommerce-product-bundles-min-max-items' ),
			'i18n_max_qty_error_singular'     => __( 'Please select at most 1 item.', 'woocommerce-product-bundles-min-max-items' ),
			'i18n_min_max_qty_error_plural'   => __( 'Please select %s items.', 'woocommerce-product-bundles-min-max-items' ),
			'i18n_min_max_qty_error_singular' => __( 'Please select 1 item.', 'woocommerce-product-bundles-min-max-items' ),
			'i18n_qty_error_plural'           => __( 'You have selected %s items.', 'woocommerce-product-bundles-min-max-items' ),
			'i18n_qty_error_singular'         => __( 'You have selected 1 item.', 'woocommerce-product-bundles-min-max-items' ),
			'i18n_qty_error_none'             => __( 'You have not selected any items.', 'woocommerce-product-bundles-min-max-items' ),
			'i18n_qty_error'                  => __( '%1$s %2$s', 'validation error: status, resolution', 'woocommerce-product-bundles-min-max-items' ),
		);

		wp_localize_script( 'wcpb-min-max-items-add-to-cart', 'wcpb_min_max_items_params', $params );
	}

	public static function min_max_script_data( $the_product = false ) {

		global $product;

		if ( ! $the_product ) {
			$the_product = $product;
		}

		if ( is_object( $the_product ) && $the_product->product_type === 'bundle' ) {

			$min = get_post_meta( $the_product->id, '_wcpb_min_qty_limit', true );
			$max = get_post_meta( $the_product->id, '_wcpb_max_qty_limit', true );

			?><div class="min_max_items" data-min="<?php echo $min > 0 ? esc_attr( absint( $min ) ) : ''; ?>" data-max="<?php echo $max > 0 ? esc_attr( absint( $max ) ) : ''; ?>"></div><?php
		}
	}

	/**
	 * Cart validation.
	 */

	public static function min_max_cart_validation( $result, $bundle_id, $stock_data ) {

		if ( $result ) {

			$items_qty = 0;

			$min_meta  = get_post_meta( $bundle_id, '_wcpb_min_qty_limit', true );
			$max_meta  = get_post_meta( $bundle_id, '_wcpb_max_qty_limit', true );

			$items_min = $min_meta > 0 ? absint( $min_meta ) : '';
			$items_max = $max_meta > 0 ? absint( $max_meta ) : '';

			$items     = $stock_data->get_items();

			foreach ( $items as $item ) {
				$items_qty += $item->quantity;
			}

			$resolution = '';

			if ( $items_min !== '' && $items_qty < $items_min ) {

				$result = false;

				if ( $items_min === 1 ) {

					if ( $items_min === $items_max ) {
						$resolution = __( 'you must select 1 item.', 'woocommerce-product-bundles-min-max-items' );
					} else {
						$resolution = __( 'you must select at least 1 item.', 'woocommerce-product-bundles-min-max-items' );
					}

				} else {

					if ( $items_min === $items_max ) {
						$resolution = __( 'you must select %s items.', 'woocommerce-product-bundles-min-max-items' );
					} else {
						$resolution = __( 'you must select at least %s items.', 'woocommerce-product-bundles-min-max-items' );
					}

					$resolution = sprintf( $resolution, $items_min );
				}

			} else if ( $items_max !== '' && $items_qty > $items_max ) {

				$result = false;

				if ( $items_max === 1 ) {

					if ( $items_min === $items_max ) {
						$resolution = __( 'you must select 1 item.', 'woocommerce-product-bundles-min-max-items' );
					} else {
						$resolution = __( 'you must select at most 1 item.', 'woocommerce-product-bundles-min-max-items' );
					}

				} else {

					if ( $items_min === $items_max ) {
						$resolution = __( 'you must select %s items.', 'woocommerce-product-bundles-min-max-items' );
					} else {
						$resolution = __( 'you must select at most %s items.', 'woocommerce-product-bundles-min-max-items' );
					}

					$resolution = sprintf( $resolution, $items_max );
				}
			}

			if ( ! $result ) {

				$action = sprintf( __( 'To purchase &quot;%s&quot;', 'woocommerce-product-bundles-min-max-items' ), get_the_title( $bundle_id ) );

				if ( $items_qty === 0 ) {
					$status = __( 'You have not selected any items.', 'woocommerce-product-bundles-min-max-items' );
				} elseif ( $items_qty === 1 ) {
					$status = __( 'You have selected 1 item.', 'woocommerce-product-bundles-min-max-items' );
				} else {
					$status = sprintf( __( 'You have selected %s items.', 'woocommerce-product-bundles-min-max-items' ), $items_qty );
				}

				$error = sprintf( __( '%1$s %2$s %3$s', 'validation error: action, resulution, status', 'woocommerce-product-bundles-min-max-items' ), $action, $resolution, $status );

				wc_add_notice( $error, 'error' );
			}
		}

		return $result;
	}

	/**
	 * Change bundled item quantities for min price calculations in PPP mode.
	 */
	public static function min_max_bundled_item_required_quantities( $quantities, $bundle ) {

		if ( $bundle->is_priced_per_product() ) {

			$min_qty = get_post_meta( $bundle->id, '_wcpb_min_qty_limit', true );

			if ( $min_qty ) {

				$pricing_data = array();

				if ( ! empty( $bundle->bundled_items ) ) {
					foreach ( $bundle->bundled_items as $bundled_item ) {
						$pricing_data[ $bundled_item->item_id ][ 'price' ]         = $bundled_item->get_bundled_item_price();
						$pricing_data[ $bundled_item->item_id ][ 'regular_price' ] = $bundled_item->get_bundled_item_regular_price();
					}

					// slots filled so far
					$filled_slots = 0;

					foreach ( $quantities[ 'min' ] as $item_min_qty ) {
						$filled_slots += $item_min_qty;
					}

					if ( $filled_slots < $min_qty ) {

						// sort by cheapest
						uasort( $pricing_data, array( __CLASS__, 'sort_by_price' ) );

						// fill additional slots
						foreach ( $pricing_data as $bundled_item_id => $data ) {

							$slots_to_fill = $min_qty - $filled_slots;
							$items_to_use  = min( $quantities[ 'max' ][ $bundled_item_id ] - $quantities[ 'min' ][ $bundled_item_id ] , $slots_to_fill );

							$filled_slots += $items_to_use;

							$quantities[ 'min' ][ $bundled_item_id ] += $items_to_use;

							if ( $filled_slots >= $min_qty ) {
								break;
							}
						}
					}
				}
			}
		}

		return $quantities;
	}

	/**
	 * When min/max qty constraints are present, require input.
	 */
	public static function min_max_bundle_requires_input( $requires_input, $bundle ) {

		$min_qty = get_post_meta( $bundle->id, '_wcpb_min_qty_limit', true );
		$max_qty = get_post_meta( $bundle->id, '_wcpb_max_qty_limit', true );

		if ( $min_qty || $max_qty ) {

			$requires_input = true;
		}

		return $requires_input;
	}

	/**
	 * Sort array data by price.
	 *
	 * @param  array $a
	 * @param  array $b
	 * @return -1|0|1
	 */
	private static function sort_by_price( $a, $b ) {

		if ( $a[ 'price' ] == $b[ 'price' ] ) {
			return 0;
		}

		return ( $a[ 'price' ] < $b[ 'price' ] ) ? -1 : 1;
	}
}

WC_PB_Min_Max_Items::init();
