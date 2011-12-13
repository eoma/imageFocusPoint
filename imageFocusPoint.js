
var imageFocusPoint;

(function ($) {
imageFocusPoint = {
	x : null,
	y : null,
	poi : null,
	attachment_id : null,
	img : null,

	init : function (attachment_id) {
		var t = this;

		t.attachment_id = attachment_id;
		t.x = jQuery('#image_focus_point_' + t.attachment_id + '_x');
		t.y = jQuery('#image_focus_point_' + t.attachment_id + '_y');
		t.getPreviewImage();

		function eventSetVisualPoint() {
			t.setVisualPoint();
		}
		// This is an ugly hack to be able to see the focus point when
		// the picture is loaded. The event load does not trigger.
		t.img.live('mouseenter', eventSetVisualPoint);

		t.img.live('click', function(e) {
			t.computeRelativePoint(e);
			t.setVisualPoint();
		});

		// The point should be updated when points are manually input.
		t.x.live('keyup', eventSetVisualPoint);
		t.y.live('keyup', eventSetVisualPoint);
	},

	/**
	 * This method will, if t.img is null, update
	 * t.img with the correct reference to the preview image
	 */
	getPreviewImage : function () {
		var t = this;
		if (t.img === null || t.img.length == 0) {
			console.log("t.img was null or zero length");
			t.img = $('#image-preview-' + t.attachment_id);
		}
	},

	computeRelativePoint : function (e) {
		var t = this;
		t.getPreviewImage();

		var x = (e.pageX - t.img.offset().left) / t.img.width();
		var y = (e.pageY - t.img.offset().top) / t.img.height();

		t.x.attr('value', x);
		t.y.attr('value', y);
	},

	setVisualPoint : function () {
		var t = this;
		var poi = jQuery('#image_focus_point_poi');

		t.getPreviewImage();

		if (poi.length == 0) {
			//alert('not created');
		
			jQuery('#imgedit-crop-' + t.attachment_id)
			.append("<img id='image_focus_point_poi' src='" + ifp_base_url + "/poi.png' style='display:none;' height='16' width='16' />");

			poi = jQuery('#image_focus_point_poi');
			poi.css('display', 'block');
			poi.css('position', 'absolute');
		}

		poi.css('zIndex', t.img.css('zIndex') + 1);

		var _top = t.img.offset().top + t.y.val() * t.img.height() - (poi.height() / 2);
		var _left = t.img.offset().left + t.x.val() * t.img.width() - (poi.width() / 2);

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
