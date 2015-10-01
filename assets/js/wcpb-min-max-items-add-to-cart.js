/* global wcpb_min_max_items_params; */

;( function ( $, window, document, undefined ) {

	$( document.body )

		.on( 'woocommerce-product-bundle-initialized', function( event, bundle ) {

			var min = bundle.$bundle_form.find( '.min_max_items' ).data( 'min' );
			var max = bundle.$bundle_form.find( '.min_max_items' ).data( 'max' );

			if ( typeof( min ) !== 'undefined' && typeof( max ) !== 'undefined' ) {

				bundle.min_max_validation = {

					min: min,
					max: max,

					bind_validation_handler: function() {

						var min_max_validation = this;

						bundle.$bundle_data.on( 'woocommerce-product-bundle-validate', function( event, bundle ) {

							var total_qty         = 0;
							var qty_error_status  = '';
							var qty_error_prompt  = '';
							var passed_validation = true;

							// count items
							$.each( bundle.bundled_items, function( index, bundled_item ) {
								if ( bundled_item.is_selected() ) {
									total_qty += parseInt( bundled_item.get_quantity() );
								}
							} );

							// validate
							if ( min_max_validation.min !== '' && total_qty < parseInt( min_max_validation.min ) ) {

								passed_validation = false;

								if ( min_max_validation.min === 1 ) {

									if ( min_max_validation.min === min_max_validation.max ) {
										qty_error_prompt = wcpb_min_max_items_params.i18n_min_max_qty_error_singular;
									} else {
										qty_error_prompt = wcpb_min_max_items_params.i18n_min_qty_error_singular;
									}

								} else {

									if ( min_max_validation.min === min_max_validation.max ) {
										qty_error_prompt = wcpb_min_max_items_params.i18n_min_max_qty_error_plural;
									} else {
										qty_error_prompt = wcpb_min_max_items_params.i18n_min_qty_error_plural;
									}

									qty_error_prompt = qty_error_prompt.replace( '%s', parseInt( min_max_validation.min ) );
								}

							} else if ( min_max_validation.max !== '' && total_qty > parseInt( min_max_validation.max ) ) {

								passed_validation = false;

								if ( min_max_validation.max === 1 ) {

									if ( min_max_validation.min === min_max_validation.max ) {
										qty_error_prompt = wcpb_min_max_items_params.i18n_min_max_qty_error_singular;
									} else {
										qty_error_prompt = wcpb_min_max_items_params.i18n_max_qty_error_singular;
									}

								} else {

									if ( min_max_validation.min === min_max_validation.max ) {
										qty_error_prompt = wcpb_min_max_items_params.i18n_min_max_qty_error_plural;
									} else {
										qty_error_prompt = wcpb_min_max_items_params.i18n_max_qty_error_plural;
									}

									qty_error_prompt = qty_error_prompt.replace( '%s', parseInt( min_max_validation.max ) );
								}
							}

							// add notice
							if ( ! passed_validation ) {

								if ( total_qty === 0 ) {
									qty_error_status = '';
								} else if ( total_qty === 1 ) {
									qty_error_status = wcpb_min_max_items_params.i18n_qty_error_singular;
								} else {
									qty_error_status = wcpb_min_max_items_params.i18n_qty_error_plural;
								}

								qty_error_status = qty_error_status.replace( '%s', total_qty );

								bundle.add_validation_message( wcpb_min_max_items_params.i18n_qty_error.replace( '%1$s', qty_error_status ).replace( '%2$s', qty_error_prompt ) );
							}

						} );
					},

				};

				bundle.min_max_validation.bind_validation_handler();
			}
		} );

} ) ( jQuery, window, document );
