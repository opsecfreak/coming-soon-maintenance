/**
 * Admin script for MTSUAV Maintenance Mode settings page.
 *
 * Media library pickers, color picker init, overlay range display,
 * and repeatable social-link rows.
 */
(function ($) {
	'use strict';

	$(function () {

		/* Color picker. */
		$('.mtsuav-mm-color').wpColorPicker();

		/* Overlay opacity range display. */
		$('.mtsuav-mm-range').on('input', function () {
			$(this).siblings('.mtsuav-mm-range-value').text($(this).val() + '%');
		});

		/* Media library pickers (logo + background image). */
		$('.mtsuav-mm-media-field').each(function () {
			var $field = $(this);
			var $idInput = $field.find('.mtsuav-mm-media-id');
			var $preview = $field.find('.mtsuav-mm-media-preview');
			var $remove = $field.find('.mtsuav-mm-media-remove');
			var frame;

			$field.find('.mtsuav-mm-media-select').on('click', function (e) {
				e.preventDefault();
				if (frame) {
					frame.open();
					return;
				}
				frame = wp.media({
					title: 'Choose image',
					library: { type: 'image' },
					button: { text: 'Use this image' },
					multiple: false
				});
				frame.on('select', function () {
					var attachment = frame.state().get('selection').first().toJSON();
					$idInput.val(attachment.id);
					var thumb = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
					$preview.html('<img src="' + thumb + '" alt="" style="max-width:200px;height:auto;" />');
					$remove.show();
				});
				frame.open();
			});

			$remove.on('click', function (e) {
				e.preventDefault();
				$idInput.val('0');
				$preview.empty();
				$remove.hide();
			});
		});

		/* Repeatable social link rows. */
		var $rows = $('#mtsuav-mm-social-rows');
		var template = $('#mtsuav-mm-social-template').html();
		var index = $rows.find('.mtsuav-mm-social-row').length;

		$('#mtsuav-mm-social-add').on('click', function (e) {
			e.preventDefault();
			if (index >= 10) {
				return;
			}
			$rows.append(template.replace(/__INDEX__/g, index));
			index++;
		});

		$rows.on('click', '.mtsuav-mm-social-remove', function (e) {
			e.preventDefault();
			$(this).closest('.mtsuav-mm-social-row').remove();
		});
	});
})(jQuery);
