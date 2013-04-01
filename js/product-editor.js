jQuery(function($){
	$('#nono-pricing-table-edit').delegate('.add-row', 'click', function(e){
		row = $(this).closest('tr');
		new_row = $(row).clone();
		$(new_row).find('input').val('');
		$(row).after(new_row);
		return false;
	});

	$('#nono-pricing-table-edit').delegate('.delete-row', 'click', function(e){
		row = $(this).closest('tr').remove();
		return false;
	});
});
