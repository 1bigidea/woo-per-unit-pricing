<?php

class Nono_ProductAdmin {

	function __construct(){
		add_action('woocommerce_product_write_panel_tabs', array($this, 'add_panel') );
		add_action('woocommerce_product_write_panels', array($this, 'show_panel'));

		add_action('woocommerce_process_product_meta_simple', array($this, 'save_pricing_info'), 10 ,1);
	}

	/**
	 *	Insert Custom Write Panel in Woo Product Editor
	 */
	function add_panel(){
		printf('<li class="nono-price-panel nono-pricing-option"><a href="#nono_price_panel">%s</a></li>', __( 'Price Tables', 'nono-per-unit' ) );
	}
	function show_panel(){
		global $post;

		$prices = get_post_meta($post->ID, NonoPrintPricing::$pricing_table_key, true);
		if( empty($prices) ){
			$prices = array(
				'enabled' => false,
				'enabled_sale' => false,
				'regular' => array(
					'empty1' => array('price' => '', 'label' => 'Digital'),
					'empty2' => array('price' => '', 'label' => 'Offset')
				),
				'sale' => array(
					'empty1' => array('price' => '', 'label' => 'Digital'),
					'empty2' => array('price' => '', 'label' => 'Offset')
				),
				'sale_dates' => array( 'from' => '', 'thru' => '')
			);
		}

		$show_per_unit = ($prices['enabled'] ) ? "visible" : "none";
		$show_sales    = ($prices['enabled_sale']) ? "visible" : "none";

		echo '<div id="nono_price_panel" class="panel woocommerce_options_panel">';

			echo '<div class="options_group nono-price-table">';

				echo '<div class="options_group">';
					echo '<p class="form-field">';
						echo '<label for="enable-per-unit-pricing">';
						_e('Enable Per Unit Pricing', 'nono-per-unit');
						echo '</label>';
					printf('<input type="checkbox" class="checkbox" id="enable-per-unit-pricing" name="enable_per_unit_pricing" value="1" %s /><span class="description">%s</span>', checked($prices['enabled'], '1', false), __('Use Qty-based Pricing', 'nono-per-unit') );
					echo '</p>';

				echo '</div>';

				//	On Sale Fields
				printf ('<div class="options_group enable-per-unit-pricing" style="visibility:%s;">', $show_per_unit);
					echo '<p class="form-field">';
						echo '<label for="enable-sales-pricing">';
						_e('Show Sale Pricing', 'nono-per-unit');
						echo '</label>';
					printf('<input type="checkbox" class="checkbox" id="enable-sales-pricing" name="per_unit_on_sale" value="1" %s /><span class="description">%s</span>', checked($prices['enabled_sale'], '1', false), '' );
					echo '</p>';
					printf('<div class="on-sale" style="visibility:%s;">', $show_sales);
						echo '<p class="form-field sale_price_dates_fields">';
						echo '<label for="sales-pricing-from">';
						_e('Sale Price Dates:', 'nono-per-unit');
						echo '</label>';
						printf('<input type="text" class="short sale_price_dates_from" name="per_unit_sales_pricing_from" id="per-unit-sales-pricing-from" value="%s" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />', $prices['sale_dates']['from']);

						printf('<input type="text" class="short" name="per_unit_sales_pricing_thru" id="per-unit-sales-pricing-thru" value="%s" maxlength="10" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" />', $prices['sale_dates']['thru']);
						echo '</p>';
					echo '</div>';
				echo '</div>';
				// Show Pricing Table
				printf ('<div class="options_group enable-per-unit-pricing" style="visbility:%s;">', $show_per_unit);
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

							printf('<tr class="on-sale" style="visbility:%s;">', $show_sales) ;
							printf('<td>%s</td>', __('Sale Prices', 'nono-per-unit'));
							printf('<td><input type="text" class="value-field" value="%s" size="10" name="nono_price_table_saleprice[]"></td>', number_format ( $prices['sale'][$min]['price'], 2, ',', '.') );
							printf('<td><input type="text" class="value-field" value="%s" size="10" name="nono_price_table_saleaddl[]"></td>', number_format ( $prices['sale'][$min]['addl'], 2, ',', '.') );
							echo '<td></td>';
							echo '</tr>';
						}
					echo '</table>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	}


	function save_pricing_info($post_id){

		if( !isset($_POST) || !isset($_POST['nono_price_table_qty']) ) return; // Not a Variable product with pricing table
		if( !isset($_POST['enable_per_unit_pricing']) ) { // Per-unit pricing is unchecked
			$per_unit_table = get_post_meta($post_id, NonoPrintPricing::$pricing_table_key, true);
			if( is_array($per_unit_table) ){ // There is something stored here
				$per_unit_table['enabled'] = false;
				update_post_meta($post_id, NonoPrintPricing::$pricing_table_key, $per_unit_table);
			}
			return;
		}

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

		$eff_date = $thru_date = '';
		$on_sale = (isset($_POST['per_unit_on_sale']) ) ? true : false;
		$eff_date  = date('Y-m-d', strtotime($_POST['per_unit_sales_pricing_from']) ); // sanitize date fields
		$thru_date = date('Y-m-d', strtotime($_POST['per_unit_sales_pricing_thru']) );

		$per_unit_table = array(
			'enabled'	=> true,
			'enabled_sale' => $on_sale,
			'regular'	=> $pricing,
			'sale'		=> $sales_pricing,
			'sale_dates'=> array(
				'from'	=> $eff_date,
				'thru'	=> $thru_date
			)
		);


		update_post_meta($post_id, NonoPrintPricing::$pricing_table_key, $per_unit_table);
kickout('save_pricing_info', $_POST, $post_id, $per_unit_table);

		return;
	}

}
new Nono_ProductAdmin();