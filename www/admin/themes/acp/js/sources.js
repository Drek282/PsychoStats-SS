$(document).ready(function(){
	$('#protocol').change(change_proto).keyup(change_proto);
	$('#blank').click(change_blank);
	$('#ls-table a.up, #ls-table a.dn').click(move_row);
	$('table .toggle').click(click_toggle).attr('title','Click to enable or disable');

	change_proto();
	change_blank();
});

function change_proto(e) {
	var proto = $('#protocol')[0];
	if (!proto) return;
	var value = proto.options[ proto.selectedIndex ].value;

	$('div[@id^=ls-]', this.form).show();
	if (proto.selectedIndex < 1 || value == '' || value == 'html') {
		$('#ls-source').hide();
	}
}

function confirm_del(e) {
	return window.confirm("Are you sure you want to delete this league page source?");
}
