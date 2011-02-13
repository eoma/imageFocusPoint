

/**
 * Will place a small point the current focus point (or point of interest)
 */
function ifp_set_visual_point (attachment_id) {
	var x = jQuery('#image_focus_point_' + attachment_id + '_x').attr('value');
	var y = jQuery('#image_focus_point_' + attachment_id + '_y').attr('value');

	var poi = jQuery('#image_focus_point_poi');
	
	if (poi.length == 0) {
		//alert('not created');
		
		jQuery('#imgedit-crop-' + attachment_id)
		.append("<img id='image_focus_point_poi' src='../wp-content/plugins/imageFocusPoint/poi.png' style='display:none;' height='16' width='16' />");
		
		poi = jQuery('#image_focus_point_poi');
	}
	
	var img = jQuery('#image-preview-' + attachment_id);

	poi.css('display', 'block');
	poi.css('position', 'absolute');
	poi.css('zIndex', img.css('zIndex') + 1);

	var _top = img.offset().top + y * img.height() - (poi.height() /2);// - poi.offset().top;
	var _left = img.offset().left + x * img.width() - (poi.width() / 2);// - poi.offset().left;
	
	poi.offset({top: _top, left: _left});

	//alert(poi.offset().top + ' ' + poi.offset().left);
}

function ifp_set_point (attachment_id) {
	jQuery(document).ready(function(){
		jQuery('#image-preview-' + attachment_id).load( function(e) {
			ifp_set_visual_point(attachment_id);
		});

		jQuery('#image-preview-' + attachment_id).live('click', function(e) {
			var img = jQuery(this);

			var x = (e.pageX - img.offset().left) / img.width();
			var y = (e.pageY - img.offset().top) / img.height();

			jQuery('#image_focus_point_' + attachment_id + '_x').attr('value', x);
			jQuery('#image_focus_point_' + attachment_id + '_y').attr('value', y);

			ifp_set_visual_point(attachment_id);
		});

	});
}
