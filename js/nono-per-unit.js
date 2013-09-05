jQuery(function($){

	$('form .quantity .qty').on('change', function(){
		qty = $(this).val();

		var all_set 			= true;
		var any_set 			= false;
		var current_settings 	= {};
		var all_variations		= $variation_form.data( 'product_variations' );

		$('.variations select').each( function() {

			if ( $(this).val().length == 0 ) {
				all_set = false;
			} else {
				any_set = true;
			}

			// Encode entities
			value = $(this).val();

			// Add to settings array
			current_settings[ $(this).attr('name') ] = value;

		});

		var matching_variations = $.fn.wc_variation_form.find_matching_variations( all_variations, current_settings );

		if ( all_set ) {

			var variation = matching_variations.pop();

			if ( variation ) {

				the_break = price_index(qty, Object.keys(variation.price_breaks));
				console.log(variation.price_breaks[the_break], the_break, variation, matching_variations);
				console.log(accounting.formatMoney(4999.99, "€", 2, ".", ","));

			}
		}

		//$variation_form.find('.single_variation').html( variation.price_html + variation.availability_html );
	});
});

var price_index = function(qty, break_points){

	index = break_points[0];
	for(i=0; i<break_points.length; i++){
		if( qty >= break_points[i]) index = break_points[i];
	}
	return index;
};

var qty_breaks = Object.keys(nono_bulk_prices).sort(function(a, b) {
    return b - a; // force numerical ascending
});

var nono_price_lookup = function(qty){

    for(i=0;i<qty_breaks.length;i++){
        if( qty >= parseInt(qty_breaks[i]) ){
            the_price = nono_bulk_prices[qty_breaks[i]];

            price = ( parseInt(qty_breaks[i]) * the_price['price'] ) + ( (qty - parseInt(qty_breaks[i])) * the_price['addl'] );

            return price.toFixed(2);
        }
    }
}
