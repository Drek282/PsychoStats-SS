var total_to_delete = 0;
var delete_warning = 5;
$(document).ready(function(){
	// players.php: toggle all checkboxes on the page
	$('#delete-all').click(function(){
		total = $('input[@name^=del]').attr('checked', this.checked).length;
		total_to_delete = this.checked ? total : 0;
		warn = $('#delete-warning');
		(total_to_delete > delete_warning) ? warn.show() : warn.hide();
	});
	$('input[@name^=del]').click(function(){
		total_to_delete += this.checked ? 1 : -1;
		warn = $('#delete-warning');
		(total_to_delete > delete_warning) ? warn.show() : warn.hide();
	});
	$('#delete-btn').click(function(){
		if (total_to_delete == 0) return false;
		return window.confirm(delete_message);
	});

	// initialize warning incase browser is refreshed
	total_to_delete = $('input[@name^=del]:checked').length;
	warn = $('#delete-warning');
	(total_to_delete > delete_warning) ? warn.show() : warn.hide();

	// players_edit.php: display flag img
	$('#cc').keyup(function(event){
		if (this.value.length != 2) {
			$('#flag-img')[0].src = $('#blank-icon')[0].src;
		} else {
			var url = flags_url + '/' + this.value.toLowerCase() + '.webp';
			// if the img exists then we set the img source to the url.
			// this prevents IE from causing a broken img appearing
			// when an unknown CC is entered.
			$.get(url, function(){
				$('#flag-img')[0].src = url;
			});
		}
	});
});