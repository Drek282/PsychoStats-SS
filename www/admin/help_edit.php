<?php
/**
 *	This file is part of PsychoStats.
 *
 *	Written by Jason Morriss
 *	Copyright 2008 Jason Morriss
 *
 *	PsychoStats is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public Licenhelpse as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	PsychoStats is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with PsychoStats.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	Version: $Id: help_edit.php $
 */
define("PSYCHOSTATS_PAGE", true);
define("PSYCHOSTATS_ADMIN_PAGE", true);
include("../includes/common.php");
include("./common.php");
$cms->theme->assign('page', 'help');

$validfields = array('ref','id','del','upload','ajax','delhimg','message','submit','cancel');
$cms->theme->assign_request_vars($validfields, true);

$msg_not_writeable = '';
$uploaded_himg = '';
$cms->theme->assign_by_ref('msg_not_writeable', $msg_not_writeable);
$cms->theme->assign_by_ref('uploaded_himg', $uploaded_himg);

$message = '';
$cms->theme->assign_by_ref('message', $message);

if ($cancel) {
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'help.php' )));
}

// load the matching help if an ID was given
$help = array();
if (is_numeric($id)) {
	$help = $ps->db->fetch_row(1, "SELECT * FROM $ps->t_config_help WHERE id=" . $ps->db->escape($id));
	if (!$help['id']) {
		$data = array('message' => $cms->trans("Invalid help ID Specified"));
		$cms->full_page_err(basename(__FILE__, '.php'), $data);
		exit();		
	}
} elseif (!empty($id)) {
	$data = array('message' => $cms->trans("Invalid help ID Specified"));
	$cms->full_page_err(basename(__FILE__, '.php'), $data);
	exit();		
}

// Delete the help image, if specified.
if ($delhimg) {
	$res = 'success';
	$file = catfile($ps->conf['theme']['himgs_dir'], basename($delhimg));
	$ps->db->update($ps->t_config_help, array( 'img' => '' ), 'id', $id);
	if (@file_exists($file)) {
		if (!@unlink($file)) {
			$res = !is_writeable($file) ? $cms->message('error', array(
					'message_title'	=> $cms->trans("Unable to Delete Image"),
					'message'	=> $cms->trans("Permission denied")))
				: $cms->message('error', array(
					'message_title'	=> $cms->trans("Unable to Delete Image"),
					'message'	=> $cms->trans("Unknown error while deleting file")
				));
		}
	} else {
		$res = $cms->message('error', array(
				'message_title'	=> $cms->trans("Unable to Delete Image"),
				'message'	=> $cms->trans("Help image '%s' does not exist", basename($file))
			));
	}

	// if $ajax is true this was an AJAX request.
	if ($ajax) {
		print $res;
		exit();
	} else {
		$message = $res == 'success' ? $cms->message('success', array(
					'message_title'	=> $cms->trans("Image Deleted"),
					'message'	=> $cms->trans("Help image '%s' deleted successfully", basename($file))))
				: $res;
		$help['img'] = '';
	}
	//previouspage(psss_url_wrapper('help_edit.php?id=' . $id));
}

// delete entry, if asked to
if ($del and $help['id'] == $id) {
	$ps->db->delete($ps->t_config_help, 'id', $id);
	$res = 'success';
	$file = catfile($ps->conf['theme']['himgs_dir'], basename($help['img']));
	if (@file_exists($file)) {
		if (!@unlink($file)) {
			$res = !is_writeable($file) ? $cms->trans("Permission denied") : $cms->trans("Unknown error while deleting file");
		}
	} else {
		$res = $cms->trans("Help image '%s' does not exist", basename($file));
	}
	previouspage(psss_url_wrapper(array( '_amp' => '&', '_base' => 'help.php' )));
}

// load the help image part 1
$help['img'] ??= null;
$himg = array();
$ext = $ps->conf['theme']['images']['search_ext'];
if (empty($ext)) $ext = 'png, jpg, gif, webp';
$list = explode(',',$ext);
$list = array_map('trim', $list);
$match = '\\.(' . implode('|', $list) . ')$';

// create the form variables
$form = $cms->new_form();
$form->default_modifier('trim');
$form->field('enabled');
$form->field('title','blank');
$form->field('img');
$form->field('content','blank');
$form->field('url');

// process an image upload request (either from a file or a remote URL)
$errors = array();
$file = array();

// process the form if submitted
$valid = true;
if ($submit) {
	$form->validate();
	$input = $form->values();
	$valid = !$form->has_errors();

	// protect against CSRF attacks
	if ($ps->conf['main']['security']['csrf_protection']) $valid = ($valid and $form->key_is_valid($cms->session));
//	$form->field('file');
	$input = $form->values();

	if ($upload) {

		// first determine where we're fetching the url from 
		$from = null;
		if ($cms->file['file']['size']) {
			$from = $cms->file['file'];
		} elseif ($input['url'] and $input['url'] != 'http://') {
			$from = $input['url'];
		}

		$err = '';
		if (is_array($from)) {	// upload file
			$file = $from;
			if (!is_uploaded_file($file['tmp_name'])) {
				$err = $cms->trans("Uploaded help image is invalid");
			}
		} elseif ($from) {	// fetch file from URL
			$file = array();
			if (!preg_match('|^\w+://|', $from)) {	// make sure a http:// prefex is present
				$from = "http://" . $from;
			}

			if (($tmpname = @tempnam('/tmp', 'himg_')) === FALSE) {
				$err = $cms->trans("Unable to create temporary file for download");
			} else {
				$file['tmp_name'] = $tmpname;
				$url = parse_url(rawurldecode($from));
				$file['name'] = basename($url['path']);
				if (empty($file['name'])) $file['name'] = $url['host'];
				$file['size'] = 0;
				// open the URL for reading ... 
				if (!($dl = @fopen($from, 'rb'))) {
					$err = $cms->trans("Unable to download file from server");
//					if (isset($php_errormsg)) $err .= "<br/>\n" . $php_errormsg;
				}
				// open the tmp file for writting ... 
				if ($dl and !($fh = @fopen($file['tmp_name'], 'wb'))) {
					$err = $cms->trans("Unable to process download");
//					if (isset($php_errormsg)) $err .= "<br/>\n" . $php_errormsg;
				}

				// get the headers from the request
				$hdr = $http_response_header;	// built in PHP variable (hardly documented, php 4 and 5)

				// find the Content-Type and Size
				foreach ($hdr as $h) {
					if (preg_match('/:/', $h)) {
						list($key, $str) = explode(":", $h, 2);
						$str = trim($str);
						if ($key == 'Content-Length') {
							if ($str > $ps->conf['theme']['himgs']['max_size']) {
								$err = $cms->trans("File download is too large") . " (" . abbrnum($str) . " > " . abbrnum($ps->conf['theme']['himgs']['max_size']) . ")";
							}
							break;
						}
					}
				}

				// read the contents of the URL into the tmp file ... 
				if (!$err and $dl and $fh) {
					// make sure the URL file is a valid image type before we download it
					if (!preg_match("/$match/", $file['name'])) {
						$err = $cms->trans("Image type of URL must be one of the following:") . " <b>" . $ps->conf['theme']['images']['search_ext'] . "</b>";
					} else {
						$total = 0;
						while (!feof($dl) and $total < $ps->conf['theme']['himgs']['max_size']) {
							$total += fwrite($fh, fread($dl, 8192));
						}
						// if it's not the EOF then the file was too large ... 
						if (!feof($dl)) {
							$err = $cms->trans("File download is too large") . " (" . abbrnum($file['size']) . " > " . abbrnum($ps->conf['theme']['himgs']['max_size']) . ")";
						}
					}
					fclose($dl);
					fclose($fh);
					$file['size'] = filesize($file['tmp_name']);
				}
			}
		}
		$file['info'] = array();
		$file['tmp_name'] ??= null;
		if ($file['tmp_name']) $file['info'] = @getimagesize($file['tmp_name']);
		if (!$err) {
			$res = validate_img($file);
			if ($res !== true) {
				$err = $res;
			}
		}

		// still no error? we can now try and copy the file from the tmp location to the help images dir
		if (!$err) {
			$newfile = catfile($ps->conf['theme']['himgs_dir'], $file['name']);
			$overwrote = file_exists($newfile);
			$ok = @rename_file($file['tmp_name'], $newfile);
			if (!$ok) {
				$err = $cms->trans("Error copying new image to help image directory!");
//				$err .= is_writeable(dirname($newfile)) ? "<br/>" . $cms->trans("Permission Denied") : '';
//				if (isset($php_errormsg)) $err .= "<br/>\n" . $php_errormsg;
			} else {
				$message = $cms->trans("File '%s' uploaded successfully!", $file['name']);
				if ($overwrote) $message .= " (" . $cms->trans("original file was overwritten") . ")";
				$uploaded_himg = $file['name'];
				$message = $cms->message('success', array(
						'message_title'	=> $cms->trans("Image Uploaded"),
						'message'	=> $message
					));
				@chmod(catfile($ps->conf['theme']['himgs_dir'], $file['name']), 0644);
				$input['img'] = $uploaded_himg;
				$help['img'] = $uploaded_himg;
				unset($input['url']);
				$ps->db->update($ps->t_config_help, $input, 'id', $id);
			}
		}

		if ($err) {
			$form->error('fatal',$err);
		}

		// don't care if this fails
		@unlink($file['tmp_name']);
	}

	unset($input['url']);

	$valid = ($valid and !$form->has_errors());
	if ($valid && !$uploaded_himg) {
		$ok = false;
		if ($id) {
			$input['img'] = $help['img'];
			$ok = $ps->db->update($ps->t_config_help, $input, 'id', $id);
			if (!$ok) {
				$form->error('fatal', "Error updating database: " . $ps->db->errstr);
			} else {
				$message = $cms->message('success', array(
						'message_title'	=> $cms->trans("Help Entry Updated"),
						'message'	=> $cms->trans("Help entry '%s' updated successfully", basename($input['title']))
					));
			}
		} else {
			$input['id'] = $ps->db->next_id($ps->t_config_help);
			$ok = $ps->db->insert($ps->t_config_help, $input);
			if (!$ok) {
				$form->error('fatal', "Error updating database: " . $ps->db->errstr);
			} else {
				$message = $cms->message('success', array(
						'message_title'	=> $cms->trans("Help Entry Created"),
						'message'	=> $cms->trans("Help entry '%s' created successfully", basename($input['title']))
					));
				$id = $input['id'];
				//previouspage(psss_url_wrapper('help_edit.php?id=' . $id));
			}
		}
	}

} else {
	// fill in defaults
	if ($id) {
		$form->input($help);
	} else {
		// new help should default to being enabled
		$form->input['enabled'] = 1;
	}
}

// load the help image part 2
$dir = $ps->conf['theme']['himgs_dir'];
$full = $dir . "/" . $help['img'];
$himg = array(
	'filename' 	=> $help['img'],
	'fullfile' 	=> $full,
	'size'		=> @filesize($full),
	'is_writeable'	=> is_writeable($full) || is_writeable(rtrim(dirname($full), '/\\')),
	'basename'	=> basename($help['img'] ?? ''),
	'path'		=> $dir
);

if (!is_writeable($dir)) {
	$msg_not_writeable = $cms->message('not_writeable', array(
		'message_title'	=> $cms->trans("Permissions Error!"),
		'message'	=> $cms->trans("The help images directory is not writeable.") . ' ' . $cms->trans("You can not upload any new help images until the permissions are corrected."),
	));
	$message = $msg_not_writeable;
}

$cms->crumb('Manage', psss_url_wrapper('manage.php'));
$cms->crumb('Help', psss_url_wrapper('help.php'));
$cms->crumb('Edit');

// save a new form key in the users session cookie
// this will also be put into a 'hidden' field in the form
if ($ps->conf['main']['security']['csrf_protection']) $cms->session->key($form->key());

$tokens ??= null;
$cms->theme->assign(array(
	'help'		=> $help,
	'himg'		=> $himg,
	'himgs_url'	=> $ps->conf['theme']['himgs_url'],
	'form'		=> $form ? $form->values() : array('url' => null,),
	'errors'	=> $form ? $form->errors() : array('fatal' => null,),
	'form_key'	=> $ps->conf['main']['security']['csrf_protection'] ? $cms->session->key() : '',
	'tokens'	=> $tokens,
));

// display the output
$basename = basename(__FILE__, '.php');
$cms->theme->add_css('css/forms.css');
$cms->theme->add_css('css/himgs.css');
$cms->theme->add_js('js/jquery.interface.js'); // needed for color animation
$cms->theme->add_js('js/forms.js');
$cms->theme->add_js('js/help.js');
$cms->theme->add_js('js/message.js');
$cms->full_page($basename, $basename, $basename.'_header', $basename.'_footer', '');

function validate_img($file) {
	global $form, $cms, $ps;
	$c = $ps->conf['theme']['himgs'];
	$ext = $ps->conf['theme']['images']['search_ext'];
	if (empty($ext)) $ext = 'png, jpg, gif, webp';
	$list = explode(',',$ext);
	$list = array_map('trim', $list);
	$match = '\\.(' . implode('|', $list) . ')$';
	$res = true;
	$file['name'] ??= null;
	if (!preg_match("/$match/", $file['name'])) {
		return $cms->trans("Image type must be one of the following:") . ' <b>' . implode(', ', $list) . '</b>';
#	} elseif ($file['info'][2] > 3) {
#		return $cms->trans("Image type is invalid");		
	} elseif ($c['max_size'] and $file['size'] > $c['max_size']) {
		return $cms->trans("Image size is too large") . " (" . abbrnum($file['size']) . " > " . abbrnum($c['max_size']) . ")";
	} elseif ($file['info'][0] > $c['max_width'] or $file['info'][1] > $c['max_height']) {
		return $cms->trans("Image dimensions are too big") . " ({$file['info'][0]}x{$file['info'][1]} > " . $c['max_width'] . "x" . $c['max_height'] . ")";
	} elseif (substr($file['name'], 0, 1) == '.') { 
		return $cms->trans("Image name can not start with a period");
	}
	return $res;
}

// shuwdown function; delete temp file
function sd_del_file($file) {
//	global $file;
	print "unlink(" . $file['tmp_name'] . ")";
	if ($file['tmp_name'] and @is_file($file['tmp_name'])) {
		@unlink($file['tmp_name']);
	}
}

?>
