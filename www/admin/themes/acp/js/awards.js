$(document).ready(function(){
	$('#aw-table a.up, #aw-table a.dn').click(move_row);

	$('#awardtype').change(function(){
		var show = ($(this).val() != 'team');
		var group = $('#groupname');
		if (show) {
			group.slideDown('fast');
		} else {
			group.slideUp('fast');
		}
	});
});
