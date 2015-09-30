<?php
/**
 * Bundled Product Quantity Template.
 *
 * @version 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quantity_min = $bundled_item->get_quantity();
$quantity_max = $bundled_item->get_quantity( 'max' );

$input_name   = $bundle_fields_prefix . 'bundle_quantity_' . $bundled_item->item_id;

ob_start();

	woocommerce_quantity_input( array(
		'input_name'  => $input_name,
		'min_value'   => $quantity_min,
	'max_value'   => $quantity_max,
		'input_value' => isset( $_POST[ $input_name ] ) ? $_POST[ $input_name ] : apply_filters( 'woocommerce_bundled_product_quantity', $quantity_min, $quantity_min, $quantity_max, $bundled_item )
	), $bundled_item->product );

echo str_replace( 'qty text', 'qty text bundled_qty', ob_get_clean() );
