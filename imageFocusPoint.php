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

	$ifp = get_post_meta($post->ID, '_wp_attachment_image_focus_point', true);
	if (empty($ifp)) {
		// Default values;
		$ifp = array(
			'x' => 0.5,
			'y' => 0.5,
		);
	}

	$a['label'] = __('Image focus point (relative coordinates)');
	$a['input'] = 'html';
	$a['html'] = "<input type='text' value='" . $ifp['x'] . "' name='attachments[" . intval($post->ID) . "][image_focus_point][x]' id='image_focus_point_" .  intval($post->ID) . "_x' /><br />"
	           . "<input type='text' value='" . $ifp['y'] . "' name='attachments[" . intval($post->ID) . "][image_focus_point][y]' id='image_focus_point_" .  intval($post->ID) . "_y' />"
	           . "<script type='text/javascript'>imageFocusPoint.init(" . intval($post->ID) .")</script>"
	           . "";
	$a['value'] = $ifp;

	$actions['image_focus_point'] = $a;

	return $actions;
}

/**
 * Saves the focus point data
 **/
function ifp_attachment_fields_to_save ($post, $attachment) {

	if ( substr($post['post_mime_type'], 0, 5) != 'image' ) return $post;

	$previous = get_post_meta($post['ID'], '_wp_attachment_image_focus_point', true);
	if (empty($previous)) $previous = array();

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

/**
 * Will perform the cropping of the image
 */
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

	if (empty($file)) return $metadata;
	
	$info = pathinfo($file);
	$dir = $info['dirname'];
	$ext = $info['extension'];
	$name = wp_basename($file, ".$ext");

	foreach (get_intermediate_image_sizes() as $s) {

		$size = array();

		if ( ! isset($imgSizes[$s]) ) {
			$size = array( 'width' => '', 'height' => '', 'crop' => FALSE );
			$size['width'] = intval(get_option( "{$s}_size_w" )); // For default sizes set in options
			$size['height'] = intval(get_option( "{$s}_size_h" )); // For default sizes set in options
			$size['crop'] = intval(get_option( "{$s}_crop" )); // For default sizes set in options
		} else {
			$size = $imgSizes[$s];
		}

		if ( !empty($size['width']) && !empty($size['height']) && isset($size['crop']) && ($size['crop'] == true) ) {

			$dst_h = $size['height'];
			$dst_w = $size['width'];

			$origRatio = $orig_h / $orig_w;
			$dstRatio = $dst_h / $dst_w;

			$orig_x = 0;
			$orig_y = 0;

			if ($dstRatio > $origRatio) {
				// We need to operate in the original image's width

				$height = $orig_h;
				$width = (int) round($orig_w * $origRatio / $dstRatio);

				$px = $ifp['x'] * $orig_w;

				// $orig_x and $orig_y are the coordinates where the cropping is supposed to take place.
				// It is based around that the image focus point is the center of a box (any)
				// only restricted by the borders of the original image.
				if ($px < $width/2) {
					$orig_x = 0;
				} else if ($px > ($orig_w - $width/2)) {
					$orig_x = $orig_w - $width;
				} else {
					$orig_x = $px - $width/2;
				}
			} else if ($dstRatio < $origRatio) {
				// We need to operate on the original image's height

				$width = $orig_w;
				$height = (int) round($orig_h * $dstRatio / $origRatio);

				$py = $ifp['y'] * $orig_h;

				// $orig_x and $orig_y are the coordinates where the cropping is supposed to take place.
				// It is based around that the image focus point is the center of a box (any)
				// only restricted by the borders of the original image.
				if ($py < $height/2) {
					$orig_y = 0;
				} else if ($py > ($orig_h - $height/2)) {
					$orig_y = $orig_h - $height;
				} else {
					$orig_y = $py - $height/2;
				}
			}

			if ($dstRatio != $origRatio) {
				// No need to resize and crop if ratios are the same

				$metadata['sizes'][$s]['height'] = $dst_h;
				$metadata['sizes'][$s]['width'] = $dst_w;

				if (isset($metadata['sizes'][$s]['file'])) {
					// No need to litter the upload directory with unused thumbnails
					$previousGeneratedFile = str_replace(basename($file), $metadata['sizes'][$s]['file'], $file);
					if (file_exists($previousGeneratedFile)) {
						unlink($previousGeneratedFile);
					}

					// Construct a name for the thumbnail
					$newfilename = explode('-', $metadata['sizes'][$s]['file']);

					$newfilename[count($newfilename) - 1] = "{$dst_w}x{$dst_h}.{$ext}";
					$metadata['sizes'][$s]['file'] = implode('-', $newfilename);

					$destfilename = str_replace(basename($file), $metadata['sizes'][$s]['file'], $file);
				} else {
					// Must construct a name for the thumbnail
					$destfilename = substr($file, 0, strrpos($file, '.'));
					$destfilename .= "-{$dst_w}x{$dst_h}.{$ext}";
				}

				$resized = ifp_wp_crop_image($file, $orig_x, $orig_y, $width, $height, $dst_w, $dst_h, false, $destfilename);

				if ($resized) {
					$metadata['sizes'][$s]['file'] = basename(str_replace(dirname($file), '', $resized));
				}
			}
		}
	}

	return $metadata;
}

/**
 * Crop an Image to a given size.
 * Modified version of the function wp_crop_image located in
 * wp-admin/includes/image.php
 *
 * Mixes in some picture detection and treatment from image_resize in
 * wp-includes/media.php
 *
 * @param string|int $src_file The source file or Attachment ID.
 * @param int $src_x The start x position to crop from.
 * @param int $src_y The start y position to crop from.
 * @param int $src_w The width to crop.
 * @param int $src_h The height to crop.
 * @param int $dst_w The destination width.
 * @param int $dst_h The destination height.
 * @param int $src_abs Optional. If the source crop points are absolute.
 * @param string $dst_file Optional. The destination file to write to.
 * @return string|WP_Error|false New filepath on success, WP_Error or false on failure.
 */
function ifp_wp_crop_image( $src_file, $src_x, $src_y, $src_w, $src_h, $dst_w, $dst_h, $src_abs = false, $dst_file = false ) {
	if ( is_numeric( $src_file ) ) // Handle int as attachment ID
		$src_file = get_attached_file( $src_file );

	$src = wp_load_image( $src_file );

	if ( !is_resource( $src ) )
		return new WP_Error( 'error_loading_image', $src, $src_file );

	/* Part from image_resize */
	$size = @getimagesize( $src_file );
	if ( !$size )
		return new WP_Error('invalid_image', __('Could not read image size'), $file);
	list($orig_w, $orig_h, $orig_type) = $size;
	/* End part from image_resize */

	$dst = wp_imagecreatetruecolor( $dst_w, $dst_h );

	if ( $src_abs ) {
		$src_w -= $src_x;
		$src_h -= $src_y;
	}

	imagecopyresampled( $dst, $src, 0, 0, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h );

	/* Part from image_resize */
	// convert from full colors to index colors, like original PNG.
	if ( (IMAGETYPE_PNG == $orig_type) && function_exists('imageistruecolor') && !imageistruecolor( $src ) )
		imagetruecolortopalette( $dst, false, imagecolorstotal( $src ) );
	/* End part from image_resize */

	imagedestroy( $src ); // Free up memory

	if ( ! $dst_file )
		$dst_file = str_replace( basename( $src_file ), 'cropped-' . basename( $src_file ), $src_file );

	$info_src = pathinfo($src_file);
	$info_dst = pathinfo($dst_file);
	$dir = $info_dst['dirname'];
	$ext = $info_src['extension']; // Keep the source extension
	$name = wp_basename($dst_file, ".{$info_dst['extension']}");

	$dst_file = "{$dir}/{$name}.{$ext}";

	if ( IMAGETYPE_GIF == $orig_type ) {
		$success = imagegif($dst, $dst_file);
	} else if ( IMAGETYPE_PNG == $orig_type ) {
		$success = imagepng($dst, $dst_file);
	} else {
		$dst_file = "{$dir}/{$name}.jpg";
		$success = imagejpeg( $dst, $dst_file, apply_filters( 'jpeg_quality', 90, 'wp_crop_image' ) );
	}

	imagedestroy($dst);

	if ( $success ) {
		/* Part from image_resize */
		// Set correct file permissions
		$stat = stat( dirname( $dst_file ));
		$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
		@ chmod( $dst_file, $perms );
		/* End part from image_resize */

		return $dst_file;
	} else {
		return false;
	}
}

add_action('wp_generate_attachment_metadata', 'ifp_crop', 5, 2);

if ( is_admin() ) {
	add_action('attachment_fields_to_edit', 'ifp_attachment_fields_to_edit', 10, 2);
	add_action('attachment_fields_to_save', 'ifp_attachment_fields_to_save', 10, 2);
	
	wp_enqueue_script('image_focus_point', plugins_url('/imageFocusPoint.js', __FILE__), array('jquery'));
}
