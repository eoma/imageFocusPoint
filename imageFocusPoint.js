
function image_focus_point (attachment_id) {
	jQuery(document).ready(function(){
		jQuery('#thumbnail-head-' + attachment_id + ' img.thumbnail')
		.first()
		.click(function(e) {

			var el = jQuery(this);

			var x = (e.pageX - el.offset().left) / el.width();
			var y = (e.pageY - el.offset().top) / el.height();

			jQuery('#image_focus_point_x').attr('value', x);
			jQuery('#image_focus_point_y').attr('value', y);
			e.preventDefault();
		});
	});
}
