// jquery.xml2json.js
(function($){$.extend({xml2json:xml2json});function xml2json(xml,root){var o={};$(root==undefined?'response':root,xml).children().each(function(){o[this.tagName]=$(this).text()});return o}})(jQuery);

// When the page is loaded this is automatically called
var psss_popups = new Array();
var psss_popup_delay = 0.25;
var jlupdate = null;
$(document).ready(function() {
	// setup handlers for the overall login/search popups
	$('#ps-login-link').click(function(e){  return psss_overall_popup('login'); });
	$('#ps-search-link').click(function(e){ return psss_overall_popup('search'); });
	$('#ps-login-link, #ps-login-popup, #ps-search-link, #ps-search-popup').hover(stop_popup_timer, start_popup_timer);

	// setup handlers for frame collapse/expand divs
	$('div.ps-column-frame, div.ps-table-frame').not('.no-ani').each(function(i) {
		var frame = this;
		// on the frame 'header' apply the onClick handler
		$('div.ps-column-header, div.ps-frame-header', this).click(function() {
			psss_header_handler(this, frame, null, 'slow');
		});
	});

	// global ajax status animations
	$('#ajax-status').ajaxStart(ajax_start);
	$('#ajax-status').ajaxStop(ajax_stop);

	// automatically add an mouseover/out for table rows ...
	$(".ps-table tr:gt(0)").mouseover(function() {$(this).addClass("over");}).mouseout(function() {$(this).removeClass("over");});

	// language handler
	$('select.language').change(function(){ 
		this.form.submit();
	});

	// client time for lastupdate
    document.getElementById('lastupdate').innerHTML = psss_cltime(jlupdate);
});

// popup variables
var active_popup = null;
var timeout_popup = null;
var timeout_seconds = 1.0;

function psss_overall_popup(label) {
	var popup = $('#ps-' + label + '-popup');
	var img = $('#ps-' + label + '-img');
	var o = img.offset({ scroll: false });
	var y = img.height() + o.top + 1;
	var x = Math.floor(img.width() / 2 + o.left) - popup.width() + 20;

	if (active_popup) {
		if (active_popup.attr('id') != popup.attr('id')) {
			close_popup();
		} else {
			close_popup();
			return false;	
		}
	}

	active_popup = popup;
	popup.css({ top: y, left: x });
	popup.fadeIn('fast', function(){ $('input:text', popup).focus() });
//	popup.slideToggle('fast', function(){ $('input:text', popup).focus() });
//	popup.toggle();
//	$('input:text', popup).focus();

	return false;
}

function close_popup() {
	if (!active_popup) return;
//	active_popup.slideUp('fast', stop_popup_timer);
	active_popup.fadeOut('fast');
	active_popup = null;
	timeout_popup = null;
}

function start_popup_timer() {
	if (!active_popup) return;
	if (timeout_popup) stop_popup_timer();
	timeout_popup = setTimeout('close_popup()', timeout_seconds * 1000);
}

function stop_popup_timer() {
	if (timeout_popup) clearTimeout(timeout_popup);
	timeout_popup = null;
}

// frame animation handler
function psss_header_handler(header, frame, display, speed) {
	var hdr = $(header);
	var f = $(frame);
	var content = hdr.next();
	var span = $('span', hdr);
	var visible = content.is(":visible");
	if (display == null) display = !visible;
	if (display == visible) return;				// nothing to do

	// update the icon in the header based on the new display state
	// the image filename must end in 'minus' or 'plus'.
	var img = span.css('backgroundImage');
	if (img.indexOf('minus') != -1) {
		img = img.replace(/minus/, 'plus');
	} else if (img.indexOf('plus') != -1) {
		img = img.replace(/plus/, 'minus');
	}
	span.css('backgroundImage', img);

	// toggle the display of the content
	if (speed) {
		display ? content.slideDown(speed) : content.slideUp(speed);
	} else {
		display ? content.show() : content.hide();
	}

	// update the users session
	var id = content.attr('id');
	if (id) {
		$.post('opt.php', { shade: id, closed: display ? 0 : 1 });
	}
}
	
// convert lastupdate to client time
function psss_cltime(jlupdate) {
    var fjlupdate = new Date(jlupdate * 1000);
    var ye = new Intl.DateTimeFormat('en', { year: 'numeric' }).format(fjlupdate);
    var mo = new Intl.DateTimeFormat('en', { month: '2-digit' }).format(fjlupdate);
    var da = new Intl.DateTimeFormat('en', { day: '2-digit' }).format(fjlupdate);
    var ho = new Intl.DateTimeFormat('en', { hour: '2-digit', hourCycle: 'h23' }).format(fjlupdate);
    var mi = new Intl.DateTimeFormat('en', { minute: '2-digit' }).format(fjlupdate);
    var se = new Intl.DateTimeFormat('en', { second: '2-digit' }).format(fjlupdate);

    fjlupdate = ye+"-"+mo+"-"+da+" "+ho+":"+mi+":"+se;

	return fjlupdate;
}

var ajax_stopped = true;
function ajax_start() {
	if (!ajax_stopped) return;
	ajax_stopped = false;
	$(this).fadeIn('fast');
}

function ajax_stop() {
	ajax_stopped = true;
	$(this).fadeOut('fast');
}

