jQuery(function($){
	$('#nono-pricing-table-edit').delegate('.add-row', 'click', function(e){
		row = $(this).closest('tr');
		new_row = $(row).clone();
		$(new_row).find('input').val('');

		nextrow = $(row).next();
		new_sales_row = $(nextrow).clone();
		$(new_sales_row).find('input').val('');

		$(nextrow).after(new_sales_row).after(new_row);

		return false;
	});

	$('#nono-pricing-table-edit').delegate('.delete-row', 'click', function(e){
		row = $(this).closest('tr');
		nextrow = $(row).next();

		$(nextrow).remove();
		$(row).remove();

		return false;
	});

	$('#enable-sales-pricing').change(function(){
		$('.on-sale', '#nono_price_panel').toggle();
	});
	$('#enable-per-unit-pricing').change(function(){
		$('.enable-per-unit-pricing', '#nono_price_panel').toggle();
	});
});
