
var imageFocusPoint;

(function ($) {
imageFocusPoint = {
	x : null,
	y : null,
	poi : null,
	attachment_id : null,

	init : function (attachment_id) {
		var t = this;

		t.attachment_id = attachment_id;
		t.x = jQuery('#image_focus_point_' + t.attachment_id + '_x');
		t.y = jQuery('#image_focus_point_' + t.attachment_id + '_y');

		jQuery('#image-preview-' + t.attachment_id).live('mouseenter', function(e) {
			// This is an ugly hack to be able to see the focus point when
			// the picture is loaded. The event load does not trigger.
			t.setVisualPoint();
		});

		jQuery('#image-preview-' + t.attachment_id).live('click', function(e) {
			t.computeRelativePoint(e);
			t.setVisualPoint();
		});
	},

	computeRelativePoint : function (e) {
		var t = this;
		var img = jQuery(e.target);

		var x = (e.pageX - img.offset().left) / img.width();
		var y = (e.pageY - img.offset().top) / img.height();

		t.x.attr('value', x);
		t.y.attr('value', y);
	},

	setVisualPoint : function () {
		var t = this;
		var poi = jQuery('#image_focus_point_poi');

		if (poi.length == 0) {
			//alert('not created');
		
			jQuery('#imgedit-crop-' + t.attachment_id)
			.append("<img id='image_focus_point_poi' src='../wp-content/plugins/imageFocusPoint/poi.png' style='display:none;' height='16' width='16' />");

			poi = jQuery('#image_focus_point_poi');
			poi.css('display', 'block');
			poi.css('position', 'absolute');
		}

		var img = jQuery('#image-preview-' + t.attachment_id);

		poi.css('zIndex', img.css('zIndex') + 1);

		var _top = img.offset().top + t.y.val() * img.height() - (poi.height() / 2);
		var _left = img.offset().left + t.x.val() * img.width() - (poi.width() / 2);

		poi.offset({top: _top, left: _left});

		// For some reason, the element will not be positioned until
		// the second call to poi.offset()
		if ( typeof(t.called) == 'undefined' ) {
			poi.offset({top: _top, left: _left});
			t.called = true;
		}
	}
}
})(jQuery);
