<?php

class Nono_ProductAdmin {

	function __construct(){
		add_action('woocommerce_product_write_panel_tabs', array($this, 'add_panel') );
		add_action('woocommerce_product_write_panels', array($this, 'show_panel'));

		add_action('woocommerce_process_product_meta_bulk', array($this, 'save_pricing_info'), 10 ,1);

		add_filter('product_type_selector', array($this, 'modify_product_selector') );
	}

	function modify_product_selector($product_selections){
		$product_selections['bulk'] = __('Bulk Priced product', 'nono-per-unit');

		return $product_selections;
	}

	/**
	 *	Insert Custom Write Panel in Woo Product Editor
	 */
	function add_panel(){
		printf('<li class="nono-price-panel nono-pricing-option hide_if_grouped show_if_bulk hide_if_virtual hide_if_external hide_if_simple hide_if_variable"><a href="#nono_price_panel">%s</a></li>', __( 'Price Tables', 'nono-per-unit' ) );
	}
	function show_panel(){
		global $post;

		$prices = get_post_meta($post->ID, NonoPrintPricing::$pricing_table_key, true);
		if( empty($prices) ){
			$prices = array(
				'enabled_sale' => false,
				'regular' => array(
					'empty1' => array('price' => '', 'label' => __('Digital', 'nono-per-unit')),
					'empty2' => array('price' => '', 'label' => __('Offset', 'nono-per-unit'))
				),
				'sale' => array(
					'empty1' => array('price' => '', 'label' => __('Digital', 'nono-per-unit')),
					'empty2' => array('price' => '', 'label' => __('Offset', 'nono-per-unit'))
				),
			);
		}

		$show_sales = ($prices['enabled_sale']) ? "table-row" : "none";

		echo '<div id="nono_price_panel" class="panel woocommerce_options_panel">';

			echo '<div class="options_group nono-price-table">';

				//	On Sale Fields
				echo '<div class="options_group enable-per-unit-pricing" >';
					echo '<p class="form-field">';
						echo '<label for="enable-sales-pricing">';
						_e('Show Sale Pricing', 'nono-per-unit');
						echo '</label>';
					printf('<input type="checkbox" class="checkbox" id="enable-sales-pricing" name="per_unit_on_sale" value="1" %s /><span class="description">%s</span>', checked($prices['enabled_sale'], '1', false), '' );
					printf('<a href="#" class="sale_schedule">%s</a>', __( 'Schedule', 'woocommerce' ));
					echo '</p>';

					// Special Price date range
					$sale_price_dates_from 	= ( $date = get_post_meta( $post->ID, '_sale_price_dates_from', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';
					$sale_price_dates_to 	= ( $date = get_post_meta( $post->ID, '_sale_price_dates_to', true ) ) ? date_i18n( 'Y-m-d', $date ) : '';

					echo '<p class="form-field sale_price_dates_fields" class="on-sale" style="display:none;">';
						echo '<label for="sales-pricing-from">';
						_e('Sale Price Dates:', 'nono-per-unit');
						echo '</label>';
						printf('<input type="text" class="short sale_price_dates_from" name="per_unit_sales_pricing_from" id="per-unit-sales-pricing-from" value="%s" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />', $sale_price_dates_from);
						printf('<input type="text" class="short" name="per_unit_sales_pricing_thru" id="per-unit-sales-pricing-thru" value="%s" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />', $sale_price_dates_to);
						printf('<a href="#" class="cancel_sale_schedule">%s</a></label>', __( 'Cancel', 'woocommerce' ));
					echo '</p>';

				echo '</div>';
				// Show Pricing Table
				echo '<div class="options_group enable-per-unit-pricing">';
					echo '<table id="nono-pricing-table-edit">';
						printf('<thead><td class="price-min">%s</td><td class="price-each">%s</td><td class="price-addl">%s</td><td class="price-label">%s</td><td class="price-row-action">&nbsp;</td></thead>',
							__('Min Qty', 'nono-per-unit'),
							__('Price Each', 'nono-per-unit'),
							__('Price Add&rsquo;l', 'nono-per-unit'),
							__('Label', 'nono-per-unit')
						);
						foreach( $prices['regular'] as $min => $price_row){
							// handle the case where pricing hasn't been set yet (new products etc.)
							if( ! is_numeric( $min ) ){
								$min = 0;
							}

							echo '<tr>';
							printf('<td><input type="text" class="value-field" value="%d" size="8"  name="nono_price_table_qty[]"></td>', $min);
							printf('<td><input type="text" class="value-field" value="%s" size="10" name="nono_price_table_price[]"></td>', number_format ( $price_row['price'], 2, ',', '.') );
							printf('<td><input type="text" class="value-field" value="%s" size="10" name="nono_price_table_addl[]"></td>', number_format ( $price_row['addl'], 2, ',', '.') );
							printf('<td><input type="text" value="%s" size="20" name="nono_price_table_label[]"></td>', $price_row['label']);
							echo '<td><a href="#" class="add-row action-icon" /><a href="#" class="delete-row action-icon" /></td>';
							echo '</tr>';

							printf('<tr class="on-sale" style="display:%s;">', $show_sales) ;
							printf('<td>%s</td>', __('Sale Prices', 'nono-per-unit'));
							printf('<td><input type="text" class="value-field" value="%s" size="10" name="nono_price_table_saleprice[]"></td>', number_format ( $prices['sale'][$min]['price'], 2, ',', '.') );
							printf('<td><input type="text" class="value-field" value="%s" size="10" name="nono_price_table_saleaddl[]"></td>', number_format ( $prices['sale'][$min]['addl'], 2, ',', '.') );
							echo '<td></td>';
							echo '</tr>';
						}
					echo '</table>';
				echo '</div>';
			echo '</div>';

			if ( get_option( 'woocommerce_calc_taxes' ) == 'yes' ) {

				echo '<div class="options_group show_if_bulk">';

					// Tax
					woocommerce_wp_select( array( 'id' => '_tax_status_bulk', 'label' => __( 'Tax Status', 'woocommerce' ), 'options' => array(
						'taxable' 	=> __( 'Taxable', 'woocommerce' ),
						'shipping' 	=> __( 'Shipping only', 'woocommerce' ),
						'none' 		=> __( 'None', 'woocommerce' )
					) ) );

					$tax_classes = array_filter( array_map( 'trim', explode( "\n", get_option( 'woocommerce_tax_classes' ) ) ) );
					$classes_options = array();
					$classes_options[''] = __( 'Standard', 'woocommerce' );
		    		if ( $tax_classes )
		    			foreach ( $tax_classes as $class )
		    				$classes_options[ sanitize_title( $class ) ] = esc_html( $class );

					woocommerce_wp_select( array( 'id' => '_tax_class_bulk', 'label' => __( 'Tax Class', 'woocommerce' ), 'options' => $classes_options ) );

					do_action( 'woocommerce_product_options_tax' );

				echo '</div>';

			}

		echo '</div>';
	}


	function save_pricing_info($post_id){

		if( !isset($_POST) || !isset($_POST['nono_price_table_qty']) ) return; // Not a Bulk product with pricing table

		$product_type = wp_get_object_terms($post_id, 'product_type', 'names');

		$pricing = $sales_pricing = array();
		for( $i=0;$i<count($_POST['nono_price_table_qty']);$i++ ){
			$index = absint(str_replace(',', '.', str_replace('.', '', $_POST['nono_price_table_qty'][$i])));
			if( 0 == $index ) continue; // zero quantities are not allowed

			$price = floatval(str_replace(',', '.', str_replace('.', '', $_POST['nono_price_table_price'][$i])));
			$addl = floatval(str_replace(',', '.', str_replace('.', '', $_POST['nono_price_table_addl'][$i])));
			$pricing[$index] = array(
				'price' => $price,
				'addl'	=> $addl,
				'label'	=> stripslashes ( $_POST['nono_price_table_label'][$i] )
			);

			$sale_price = floatval(str_replace(',', '.', str_replace('.', '', $_POST['nono_price_table_saleprice'][$i])));
			$sale_addl  = floatval(str_replace(',', '.', str_replace('.', '', $_POST['nono_price_table_saleaddl'][$i])));
			$sales_pricing[$index] = array(
				'price'	=> $sale_price,
				'addl'	=> $sale_addl
			);
		}
		ksort($pricing, SORT_NUMERIC);
		ksort($sales_pricing, SORT_NUMERIC);

		$date_from = !empty($_POST['per_unit_sales_pricing_from']) ? date('Y-m-d', strtotime($_POST['per_unit_sales_pricing_from']) ) : '';
		$date_to   = !empty($_POST['per_unit_sales_pricing_thru']) ? date('Y-m-d', strtotime($_POST['per_unit_sales_pricing_thru']) ) : '';

		// Dates
		if ( $date_from )
			update_post_meta( $post_id, '_sale_price_dates_from', strtotime( $date_from ) );
		else
			update_post_meta( $post_id, '_sale_price_dates_from', '' );

		if ( $date_to )
			update_post_meta( $post_id, '_sale_price_dates_to', strtotime( $date_to ) );
		else
			update_post_meta( $post_id, '_sale_price_dates_to', '' );

		if ( $date_to && ! $date_from )
			update_post_meta( $post_id, '_sale_price_dates_from', strtotime( 'NOW', current_time( 'timestamp' ) ) );

		if ( $date_to && strtotime( $date_to ) < strtotime( 'NOW', current_time( 'timestamp' ) ) ) {
			update_post_meta( $post_id, '_sale_price_dates_from', '');
			update_post_meta( $post_id, '_sale_price_dates_to', '');
		}


		$per_unit_table = array(
			'enabled_sale' => !empty($_POST['per_unit_on_sale']),
			'regular'	=> $pricing,
			'sale'		=> $sales_pricing,
		);
		update_post_meta( $post_id, NonoPrintPricing::$pricing_table_key, $per_unit_table );

		update_post_meta( $post_id, '_tax_status', stripslashes( $_POST['_tax_status_bulk'] ) );
		update_post_meta( $post_id, '_tax_class', stripslashes( $_POST['_tax_class_bulk'] ) );

		return;
	}

}
new Nono_ProductAdmin();