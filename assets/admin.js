/**
 * Admin script for Coming Soon & Maintenance Mode settings page.
 *
 * Media library pickers, color picker init, overlay range display,
 * and repeatable social-link rows.
 */
(function ($) {
	'use strict';

	$(function () {

		/* Color picker. */
		$('.csm-color').wpColorPicker();

		/* Overlay opacity range display. */
		$('.csm-range').on('input', function () {
			$(this).siblings('.csm-range-value').text($(this).val() + '%');
		});

		/* Media library pickers (logo + background image). */
		$('.csm-media-field').each(function () {
			var $field = $(this);
			var $idInput = $field.find('.csm-media-id');
			var $preview = $field.find('.csm-media-preview');
			var $remove = $field.find('.csm-media-remove');
			var frame;

			$field.find('.csm-media-select').on('click', function (e) {
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
		var $rows = $('#csm-social-rows');
		var template = $('#csm-social-template').html();
		var index = $rows.find('.csm-social-row').length;

		$('#csm-social-add').on('click', function (e) {
			e.preventDefault();
			if (index >= 10) {
				return;
			}
			$rows.append(template.replace(/__INDEX__/g, index));
			index++;
		});

		$rows.on('click', '.csm-social-remove', function (e) {
			e.preventDefault();
			$(this).closest('.csm-social-row').remove();
		});

		/* Click-to-select for the readonly bypass URL field. */
		$('.csm-select-all').on('click', function () {
			$(this).select();
		});

		/* Confirm before deleting all notify-me subscribers. */
		$('.csm-delete-all').on('click', function () {
			return window.confirm($(this).data('confirm'));
		});
	});
})(jQuery);
