<?php
/*
* Plugin Name: WooCommerce Product Bundles - Min/Max Items
* Plugin URI: http://woocommerce.com/products/product-bundles/
* Description: WooCommerce Product Bundles plugin that allows you to define min/max bundled item quantity constraints.
* Version: 1.2.0
* Author: SomewhereWarm
* Author URI: http://somewherewarm.gr/
*
* Text Domain: woocommerce-product-bundles-min-max-items
* Domain Path: /languages/
*
* Requires at least: 4.1
* Tested up to: 4.8
*
* Copyright: © 2017 SomewhereWarm SMPC.
* License: GNU General Public License v3.0
* License URI: http://www.gnu.org/licenses/gpl-3.0.html
*/

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_PB_Min_Max_Items {

	public static $version        = '1.2.0';
	public static $req_pb_version = '5.5';

	public static function plugin_url() {
		return plugins_url( basename( plugin_dir_path(__FILE__) ), basename( __FILE__ ) );
	}

	public static function plugin_path() {
		return untrailingslashit( plugin_dir_path( __FILE__ ) );
	}

	public static function init() {
		add_action( 'plugins_loaded', __CLASS__ . '::load_plugin' );
	}

	/**
	 * Lights on.
	 */
	public static function load_plugin() {

		if ( ! function_exists( 'WC_PB' ) || version_compare( WC_PB()->version, self::$req_pb_version ) < 0 ) {
			add_action( 'admin_notices', __CLASS__ . '::pb_admin_notice' );
			return false;
		}

		// Display min/max qty settings in "Bundled Products" tab.
		add_action( 'woocommerce_bundled_products_admin_config', __CLASS__ . '::display_options', 15 );

		// Save min/max qty settings.
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_meta' ) );

		// Validation script.
		add_action( 'woocommerce_bundle_add_to_cart', __CLASS__ . '::script' );
		add_action( 'woocommerce_composite_add_to_cart', __CLASS__ . '::script' );

		// Add min/max data to template for use by validation script.
		add_action( 'woocommerce_before_bundled_items', __CLASS__ . '::script_data' );
		add_action( 'woocommerce_before_composited_bundled_items', __CLASS__ . '::script_data' );

		// Cart validation.
		add_action( 'woocommerce_add_to_cart_bundle_validation', __CLASS__ . '::cart_validation', 10, 4 );

		// Change bundled item quantities.
		add_filter( 'woocommerce_bundled_item_quantity', __CLASS__ . '::bundled_item_quantity', 10, 3 );
		add_filter( 'woocommerce_bundled_item_quantity_max', __CLASS__ . '::bundled_item_quantity_max', 10, 3 );

		// When min/max qty constraints are present, require input.
		add_filter( 'woocommerce_bundle_requires_input', __CLASS__ . '::min_max_bundle_requires_input', 10, 2 );

		// Cart validation.
		add_action( 'init', __CLASS__ . '::localize_plugin' );
	}

	/**
	 * Load textdomain.
	 *
	 * @return void
	 */
	public static function localize_plugin() {
		load_plugin_textdomain( 'woocommerce-product-bundles-min-max-items', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * PB version check notice.
	 */
	public static function pb_admin_notice() {
	    echo '<div class="error"><p>' . sprintf( __( '<strong>WooCommerce Product Bundles &ndash; Min/Max Items</strong> requires Product Bundles <strong>v%s</strong> or higher.', 'woocommerce-product-bundles-min-max-items' ), self::$req_pb_version ) . '</p></div>';
	}

	/**
	 * Admin min/max settings.
	 */
	public static function display_options() {

		?><div class="options_group"><?php
			woocommerce_wp_text_input( array(
				'id'            => '_wcpb_min_qty_limit',
				'wrapper_class' => 'bundled_product_data_field',
				'type'          => 'number',
				'label'         => __( 'Quantity Min', 'woocommerce-product-bundles-min-max-items' ),
				'desc_tip'      => 'false',
				'description'   => __( 'Minimum required quantity of bundled items.', 'woocommerce-product-bundles-min-max-items' )
			) );
			woocommerce_wp_text_input( array(
				'id'            => '_wcpb_max_qty_limit',
				'wrapper_class' => 'bundled_product_data_field',
				'type'          => 'number',
				'label'         => __( 'Quantity Max', 'woocommerce-product-bundles-min-max-items' ),
				'desc_tip'      => 'false',
				'description'   => __( 'Maximum allowed quantity of bundled items.', 'woocommerce-product-bundles-min-max-items' ) ) );
		?></div><?php
	}

	/**
	 * Save meta.
	 *
	 * @param  WC_Product  $product
	 * @return void
	 */
	public static function save_meta( $product ) {

		if ( ! empty( $_POST[ '_wcpb_min_qty_limit' ] ) && is_numeric( $_POST[ '_wcpb_min_qty_limit' ] ) ) {
			$product->add_meta_data( '_wcpb_min_qty_limit', stripslashes( $_POST[ '_wcpb_min_qty_limit' ] ), true );
		} else {
			$product->delete_meta_data( '_wcpb_min_qty_limit' );
		}

		if ( ! empty( $_POST[ '_wcpb_max_qty_limit' ] ) && is_numeric( $_POST[ '_wcpb_max_qty_limit' ] ) ) {
			$product->add_meta_data( '_wcpb_max_qty_limit', stripslashes( $_POST[ '_wcpb_max_qty_limit' ] ), true );
		} else {
			$product->delete_meta_data( '_wcpb_max_qty_limit' );
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
			'i18n_qty_error'                  => _x( '%1$s %2$s', 'validation error: status, resolution', 'woocommerce-product-bundles-min-max-items' ),
		);

		wp_localize_script( 'wcpb-min-max-items-add-to-cart', 'wcpb_min_max_items_params', $params );
	}

	/**
	 * Pass min/max container values to the single-product script.
	 *
	 * @param  WC_Product  $product
	 * @return void
	 */
	public static function script_data( $the_product = false ) {

		global $product;

		if ( ! $the_product ) {
			$the_product = $product;
		}

		if ( is_object( $the_product ) && $the_product->is_type( 'bundle' ) ) {

			$min = $the_product->get_meta( '_wcpb_min_qty_limit', true );
			$max = $the_product->get_meta( '_wcpb_max_qty_limit', true );

			?><div class="min_max_items" data-min="<?php echo $min > 0 ? esc_attr( absint( $min ) ) : ''; ?>" data-max="<?php echo $max > 0 ? esc_attr( absint( $max ) ) : ''; ?>"></div><?php
		}
	}

	/**
	 * Cart validation.
	 *
	 * @param  bool                 $result
	 * @param  int                  $bundle_id
	 * @param  WC_PB_Stock_Manager  $stock_data
	 * @param  array                $configuration
	 */
	public static function cart_validation( $result, $bundle_id, $stock_data, $configuration = array() ) {

		if ( $result ) {

			$items_qty = 0;
			$bundle    = wc_get_product( $bundle_id );

			$min_meta  = $bundle->get_meta( '_wcpb_min_qty_limit', true );
			$max_meta  = $bundle->get_meta( '_wcpb_max_qty_limit', true );

			$items_min = $min_meta > 0 ? absint( $min_meta ) : '';
			$items_max = $max_meta > 0 ? absint( $max_meta ) : '';

			$items     = $stock_data->get_items();

			foreach ( $items as $item ) {
				$item_id    = isset( $item->bundled_item ) && $item->bundled_item ? $item->bundled_item->item_id : false;
				$item_qty   = $item_id && isset( $configuration[ $item_id ] ) && isset( $configuration[ $item_id ][ 'quantity' ] ) ? $configuration[ $item_id ][ 'quantity' ] : $item->quantity;
				$items_qty += $item_qty;
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

				$error = sprintf( _x( '%1$s %2$s %3$s', 'validation error: action, resulution, status', 'woocommerce-product-bundles-min-max-items' ), $action, $resolution, $status );

				wc_add_notice( $error, 'error' );
			}
		}

		return $result;
	}

	/**
	 * Filter bundled item min quantities used in sync/price context.
	 *
	 * @param  int              $qty
	 * @param  WC_Bundled_Item  $bundled_item
	 * @param  array            $args
	 * @return int
	 */
	public static function bundled_item_quantity( $qty, $bundled_item, $args = array() ) {

		if ( isset( $args[ 'context' ] ) && in_array( $args[ 'context' ], array( 'sync', 'price' ) ) ) {

			$bundle  = $bundled_item->get_bundle();
			$min_qty = $bundle ? $bundle->get_meta( '_wcpb_min_qty_limit', true ) : '';

			if ( $min_qty ) {

				if ( 'sync' === $args[ 'context' ] ) {
					$quantities = self::get_min_required_quantities( $bundle );
				} elseif ( 'price' === $args[ 'context' ] ) {
					$quantities = self::get_min_price_quantities( $bundle );
				}

				if ( isset( $quantities[ $bundled_item->get_id() ] ) ) {
					$qty = $quantities[ $bundled_item->get_id() ];
				}
			}
		}

		return $qty;
	}

	/**
	 * Filter bundled item max quantities used in sync/price context.
	 *
	 * @param  int              $qty
	 * @param  WC_Bundled_Item  $bundled_item
	 * @param  array            $args
	 * @return int
	 */
	public static function bundled_item_quantity_max( $qty, $bundled_item, $args = array() ) {

		if ( isset( $args[ 'context' ] ) && in_array( $args[ 'context' ], array( 'sync', 'price' ) ) ) {

			$bundle  = $bundled_item->get_bundle();
			$min_qty = $bundle ? $bundle->get_meta( '_wcpb_min_qty_limit', true ) : '';

			if ( $min_qty ) {

				if ( 'price' === $args[ 'context' ] ) {
					$quantities = self::get_max_price_quantities( $bundle );
				}

				if ( isset( $quantities[ $bundled_item->get_id() ] ) ) {
					$qty = $quantities[ $bundled_item->get_id() ];
				}
			}
		}

		return $qty;
	}

	/**
	 * Find the price-optimized AND availability-constrained set of bundled item quantities that meet the min item count constraint while honoring the initial min/max item quantity constraints.
	 *
	 * @param  WC_Product  $product
	 * @return array
	 */
	public static function get_min_required_quantities( $bundle ) {

		$result = WC_PB_Helpers::cache_get( 'min_required_quantities_' . $bundle->get_id() );

		if ( is_null( $result ) ) {

			$quantities = array(
				'min' => array(),
				'max' => array()
			);

			$pricing_data  = array();
			$bundled_items = $bundle->get_bundled_items();

			if ( ! empty( $bundled_items ) ) {

				$min_qty = $bundle->get_meta( '_wcpb_min_qty_limit', true );

				foreach ( $bundled_items as $bundled_item ) {
					$pricing_data[ $bundled_item->get_id() ][ 'price' ]         = $bundled_item->get_price();
					$pricing_data[ $bundled_item->get_id() ][ 'regular_price' ] = $bundled_item->get_regular_price();
					$quantities[ 'min' ][ $bundled_item->get_id() ]             = $bundled_item->get_quantity( 'min', array( 'check_optional' => true ) );
					$quantities[ 'max' ][ $bundled_item->get_id() ]             = $bundled_item->get_quantity( 'max' );
				}

				// Slots filled so far.
				$filled_slots = 0;

				foreach ( $quantities[ 'min' ] as $item_min_qty ) {
					$filled_slots += $item_min_qty;
				}

				// Fill in the box with items that are in stock, giving preference to cheapest available.
				if ( $filled_slots < $min_qty ) {

					// Sort by cheapest.
					uasort( $pricing_data, array( __CLASS__, 'sort_by_price' ) );

					// Fill additional slots.
					foreach ( $pricing_data as $bundled_item_id => $data ) {

						$slots_to_fill = $min_qty - $filled_slots;

						if ( $filled_slots >= $min_qty ) {
							break;
						}

						$bundled_item = $bundled_items[ $bundled_item_id ];

						if ( false === $bundled_item->is_purchasable() ) {
							continue;
						}

						if ( false === $bundled_item->is_in_stock() ) {
							continue;
						}

						$max_stock    = $bundled_item->get_max_stock();
						$max_item_qty = $quantities[ 'max' ][ $bundled_item_id ];

						if ( '' === $max_item_qty ) {
							$max_items_to_use = $max_stock;
						} elseif ( '' === $max_stock ) {
							$max_items_to_use = $max_item_qty;
						} else {
							$max_items_to_use = min( $max_item_qty, $max_stock );
						}

						$min_items_to_use = $quantities[ 'min' ][ $bundled_item_id ];

						$items_to_use = '' !== $max_items_to_use ? min( $max_items_to_use - $min_items_to_use, $slots_to_fill ) : $slots_to_fill;

						$filled_slots += $items_to_use;

						$quantities[ 'min' ][ $bundled_item_id ] += $items_to_use;
					}
				}

				// If there are empty slots, then bundled items do not have sufficient stock to fill the minimum box size.
				// In this case, ignore stock constraints and return the optimal price quantities, forcing the bundle to show up as out of stock.

				if ( $min_qty > $filled_slots ) {
					$quantities[ 'min' ] = self::get_min_price_quantities( $bundle );
				}
			}

			$result = $quantities[ 'min' ];
			WC_PB_Helpers::cache_set( 'min_required_quantities_' . $bundle->get_id(), $result );
		}

		return $result;
	}

	/**
	 * Find the price-optimized set of bundled item quantities that meet the min item count constraint while honoring the initial min/max item quantity constraints.
	 *
	 * @param  WC_Product  $product
	 * @return array
	 */
	public static function get_min_price_quantities( $bundle ) {

		$result = WC_PB_Helpers::cache_get( 'min_price_quantities_' . $bundle->get_id() );

		if ( is_null( $result ) ) {

			$quantities = array(
				'min' => array(),
				'max' => array()
			);

			$pricing_data  = array();
			$bundled_items = $bundle->get_bundled_items();

			if ( ! empty( $bundled_items ) ) {
				foreach ( $bundled_items as $bundled_item ) {
					$pricing_data[ $bundled_item->get_id() ][ 'price' ] = $bundled_item->get_price();
					$quantities[ 'min' ][ $bundled_item->get_id() ] = $bundled_item->get_quantity( 'min', array( 'check_optional' => true ) );
					$quantities[ 'max' ][ $bundled_item->get_id() ] = $bundled_item->get_quantity( 'max' );
				}
			}

			if ( ! empty( $pricing_data ) ) {

				$min_qty = $bundle->get_meta( '_wcpb_min_qty_limit', true );

				// Slots filled due to item min quantities.
				$filled_slots = 0;

				foreach ( $quantities[ 'min' ] as $item_min_qty ) {
					$filled_slots += $item_min_qty;
				}

				// Fill in the remaining box slots with cheapest combination of items.
				if ( $filled_slots < $min_qty ) {

					// Sort by cheapest.
					uasort( $pricing_data, array( __CLASS__, 'sort_by_price' ) );

					// Fill additional slots.
					foreach ( $pricing_data as $bundled_item_id => $data ) {

						$slots_to_fill = $min_qty - $filled_slots;

						if ( $filled_slots >= $min_qty ) {
							break;
						}

						$bundled_item = $bundled_items[ $bundled_item_id ];

						if ( false === $bundled_item->is_purchasable() ) {
							continue;
						}

						$max_items_to_use = $quantities[ 'max' ][ $bundled_item_id ];
						$min_items_to_use = $quantities[ 'min' ][ $bundled_item_id ];

						$items_to_use = '' !== $max_items_to_use ? min( $max_items_to_use - $min_items_to_use, $slots_to_fill ) : $slots_to_fill;

						$filled_slots += $items_to_use;

						$quantities[ 'min' ][ $bundled_item_id ] += $items_to_use;
					}
				}
			}

			$result = $quantities[ 'min' ];
			WC_PB_Helpers::cache_set( 'min_price_quantities_' . $bundle->get_id(), $result );
		}

		return $result;
	}

	/**
	 * Find the worst-price set of bundled item quantities that meet the max item count constraint while honoring the initial min/max item quantity constraints.
	 *
	 * @param  WC_Product  $product
	 * @return array
	 */
	public static function get_max_price_quantities( $bundle ) {

		$result = WC_PB_Helpers::cache_get( 'max_price_quantities_' . $bundle->get_id() );

		/*
		 * Max items count defined: Put the min quantities in the box, then keep adding items giving preference to the most expensive ones, while honoring their max quantity constraints.
		 */
		if ( is_null( $result ) ) {

			$quantities = array(
				'min' => array(),
				'max' => array()
			);

			$pricing_data  = array();
			$bundled_items = $bundle->get_bundled_items();

			if ( ! empty( $bundled_items ) ) {
				foreach ( $bundled_items as $bundled_item ) {
					$pricing_data[ $bundled_item->get_id() ][ 'price' ] = $bundled_item->get_price();
					$quantities[ 'min' ][ $bundled_item->get_id() ]     = $bundled_item->get_quantity( 'min', array( 'check_optional' => true ) );
					$quantities[ 'max' ][ $bundled_item->get_id() ]     = $bundled_item->get_quantity( 'max' );
				}
			}

			$max_qty = $bundle->get_meta( '_wcpb_max_qty_limit', true );

			if ( ! empty( $pricing_data ) ) {

				// Sort by most expensive.
				uasort( $pricing_data, array( __CLASS__, 'sort_by_price' ) );
				$reverse_pricing_data = array_reverse( $pricing_data, true );

				// Slots filled due to item min quantities.
				$filled_slots = 0;

				foreach ( $quantities[ 'min' ] as $item_min_qty ) {
					$filled_slots += $item_min_qty;
				}
			}

			// Fill in the remaining box slots with most expensive combination of items.
			if ( $filled_slots < $max_qty ) {

				// Fill additional slots.
				foreach ( $reverse_pricing_data as $bundled_item_id => $data ) {

					$slots_to_fill = $max_qty - $filled_slots;


					if ( $filled_slots >= $max_qty ) {
						$quantities[ 'max' ][ $bundled_item_id ] = $quantities[ 'min' ][ $bundled_item_id ];
						continue;
					}

					$bundled_item = $bundled_items[ $bundled_item_id ];

					if ( false === $bundled_item->is_purchasable() ) {
						continue;
					}

					$max_items_to_use = $quantities[ 'max' ][ $bundled_item_id ];
					$min_items_to_use = $quantities[ 'min' ][ $bundled_item_id ];

					$items_to_use = '' !== $max_items_to_use ? min( $max_items_to_use - $min_items_to_use, $slots_to_fill ) : $slots_to_fill;

					$filled_slots += $items_to_use;

					$quantities[ 'max' ][ $bundled_item_id ] = $quantities[ 'min' ][ $bundled_item_id ] + $items_to_use;
				}
			}

			$result = $quantities[ 'max' ];
			WC_PB_Helpers::cache_set( 'max_price_quantities_' . $bundle->get_id(), $result );
		}

		return $result;
	}

	/**
	 * When min/max qty constraints are present, require input.
	 *
	 * @param  bool               $requires_input
	 * @param  WC_Product_Bundle  $bundle
	 */
	public static function min_max_bundle_requires_input( $requires_input, $bundle ) {

		$min_qty = $bundle->get_meta( '_wcpb_min_qty_limit', true );
		$max_qty = $bundle->get_meta( '_wcpb_max_qty_limit', true );

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
