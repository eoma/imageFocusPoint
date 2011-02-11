<?php
/*
Plugin Name: Image Focus Point
Plugin URI: https://github.com/eoma/imageFocusPoint
Description: Plugin for setting focus point in a picture, makes it easy to crop at a given point
Version: 0.1
Author: Endre Oma
Author URI: http://endreoma.org
License: GPL2
*/
/*  Copyright 2011  Endre Oma (email : endre.88.oma@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


function ifp_attachment_fields_to_edit ($actions, $post) {
	/*echo "actions:";
	var_dump($actions);
	echo "post:";
	var_dump($post);*/

	if ( substr($post->post_mime_type, 0, 5) != 'image' ) return $actions;

	$a = array();

	$ifp = get_post_meta($post->ID, '_wp_attachment_image_focus_point');
	if (empty($ifp)) $ifp = array('');

	$a['label'] = __('Image focus point');
	$a['input'] = 'html';
	$a['html'] = "<input type='text' value='" . $ifp[0]['x'] . "' name='attachments[" . intval($post->ID) . "][image_focus_point][x]' id='image_focus_point_x' /><input type='text' value='" . $ifp[0]['y'] . "' name='attachments[" . intval($post->ID) . "][image_focus_point][y]' id='image_focus_point_y' /><br /><script type='text/javascript'>image_focus_point(" . intval($post->ID) .")</script>";
	$a['value'] = $ifp[0];

	$actions['image_focus_point'] = $a;

	return $actions;
}

function ifp_attachment_fields_to_save ($post, $attachment) {

	if ( substr($post['post_mime_type'], 0, 5) != 'image' ) return $post;

	$previous = get_post_meta($post['ID'], '_wp_attachment_image_focus_point', true);

	$changes = array_diff_assoc($attachment['image_focus_point'], $previous);

	if (!empty($changes)) {
		update_post_meta( $post['ID'], '_wp_attachment_image_focus_point', $attachment['image_focus_point']);
		$fullsizepath = get_attached_file( $post['ID'] );
	
		// The following will regenerate the thumbnails
		$metadata = wp_generate_attachment_metadata( $post['ID'], $fullsizepath );
		wp_update_attachment_metadata( $post['ID'], $metadata );
	}

	return $post;
}

function ifp_crop ($metadata, $id) {
	global $_wp_additional_image_sizes;
	$imgSizes = $_wp_additional_image_sizes;
	//print_r($metadata);
	
	$orig_h = $metadata['height'];
	$orig_w = $metadata['width'];
	
	// The $ifp contains our focus point, it is thought to be in the
	// middle of the thumbnail
	$ifp = get_post_meta($id, '_wp_attachment_image_focus_point', true);
	
	if (!isset($ifp['x'])) return $metadata;
	
	$file = get_attached_file( $id );
	
	foreach (get_intermediate_image_sizes() as $s) {
		if (isset($imgSizes[$s]) && ($imgSizes[$s]['crop'] == true)) {
			
			$dst_h = $imgSizes[$s]['height'];
			$dst_w = $imgSizes[$s]['width'];
			
			$origRatio = $orig_h / $orig_w;
			$dstRatio = $dst_h / $dst_w;
			
			$orig_x = 0;
			$orig_y = 0;
			
			if ($dstRatio > $origRatio) {
				// We need to operate in the original image's width
				
				$height = $orig_h;
				$width = (int) round($orig_w * $origRatio / $dstRatio);
				
				$orig_x = (int) round($ifp['x'] * ($orig_w - $width));
			} else if ($dstRatio < $origRatio) {
				// We need to operate on the original image's height
				
				$width = $orig_w;
				$height = (int) round($orig_h * $dstRatio / $origRatio);
				
				$orig_y = (int) round($ifp['y'] * ($orig_h - $height));
			}
			
			if ($dstRatio != $origRatio) {
				// No need to resize if ratios are the same
				
				$destfilename = str_replace(basename($file), $metadata['sizes'][$s]['file'], $file);
				
				$resized = wp_crop_image($file, $orig_x, $orig_y, $width, $height, $dst_w, $dst_h, false, $destfilename);
				
				if ($resized == false) { // Something went wrong
				
				}
			}
		}
	}
	
	return $metadata;
}

add_action('wp_generate_attachment_metadata', 'ifp_crop', 5, 2);

if ( is_admin() ) {
	add_action('attachment_fields_to_edit', 'ifp_attachment_fields_to_edit', 10, 2);
	add_action('attachment_fields_to_save', 'ifp_attachment_fields_to_save', 10, 2);
	
	wp_enqueue_script('image_focus_point', plugins_url('/imageFocusPoint.js', __FILE__), array('jquery'));
}
