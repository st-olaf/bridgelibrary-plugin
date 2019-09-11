/* global ajaxurl, jQuery */
'use strict';

(function($) {

	/**
	 * Update messages output before sending Ajax.
	 *
	 * @param {Object} $messages Messages DOM object.
	 *
	 * @returns {void} Updates the DOM.
	 */
	function beforeSend($messages) {
		$messages.removeClass('error success none').addClass('loading').html('<code>Processing…</code>');
	}

	/**
	 * Update messages output on success.
	 *
	 * @param {Object} $messages Messages DOM object.
	 * @param {string} data      Success data.
	 *
	 * @returns {void} Updates the DOM.
	 */
	function displaySuccess($messages, data) {

		if ('object' === typeof data) {
			data = JSON.stringify(data); // eslint-disable-line no-param-reassign
		}

		$messages.removeClass('none loading error').addClass('success').html('<code>' + data + '</code>');
	}

	/**
	 * Update messages output on error.
	 *
	 * @param {Object} $messages Messages DOM object.
	 * @param {string} data      Error data.
	 *
	 * @returns {void} Updates the DOM.
	 */
	function displayError($messages, data) {

		if ('object' === typeof data) {
			data = JSON.stringify(data); // eslint-disable-line no-param-reassign
		}

		$messages.removeClass('none loading success').addClass('error').html('<code>' + data + '</code>');
	}

	$(document).ready(function() {

		/**
		 * Handle ajax buttons.
		 */
		$('a.bridge-library-admin-ajax').on('click', function(e) {
			e.preventDefault();

			var $messages = $(this).parents('td').find('.messages'),
				runAsync = $(this).parents('td').find('.wait-for:checked').val();

			$.ajax({
				url: $(this).attr('href'),
				method: 'GET',
				data: {
					async: runAsync
				},
				beforeSend: function() {
					beforeSend($messages);
				},
				success: function(data, status) {
					if ('success' === status) {
						displaySuccess($messages, data.data);
					}
				},
				error: function(data, status) {
					if (status) {
						displayError($messages, data.responseJSON);
					}
				},
			});
		});

		/**
		 * Handle all settings forms via ajax.
		 */
		$('form.bridge-library-admin-ajax').on('submit', function(e) {
			e.preventDefault();

			var dataArray = $(this).serializeArray(),
				formData = {},
				$messages = $(this).find('.messages');

			$(dataArray).each(function(index, obj) {
				formData[obj.name] = obj.value;
			});

			$.ajax({
				url: ajaxurl,
				data: formData,
				beforeSend: function() {
					beforeSend($messages);
				},
				success: function(data, status) {
					if ('success' === status) {
						displaySuccess($messages, data.data);
					}
				},
				error: function(data, status) {
					if (status) {
						displayError($messages, data.responseJSON);
					}
				},
			});
		});

		/**
		 * Disable ACF fields.
		 */
		if ('undefined' !== typeof disabledAcfFields && disabledAcfFields.length > 0) {
			disabledAcfFields.forEach(function(fieldName) {
				$('.acf-field[data-name=' + fieldName + '] .acf-input').html('<p class="description">This field is disabled for your user role.</p>');
			});
			$('.form-table #password').hide();
		}

		/**
		 * Set active ACF tab.
		 */
		if ($('#acf-group_5cc3245256ec2').length > 0) {
			// If Alma, Primo, or LibGUides ID field is not empty, then this was imported.
			if (
				acf.getField('field_5cc86de3d9f72').val().length > 0 // Alma.
				|| acf.getField('field_5cd5d862935fd').val().length > 0 // Primo.
				|| acf.getField('field_5ce476b9500f7').val().length > 0 // LibGUides.
			) {
				$('.acf-tab-button[data-key="field_5cc8760aabbc2"]').click();
				console.info('Bridge Library: this resource was imported; switching to course matching tab.');
			} else {
				$('.acf-tab-button[data-key="field_5cc87610abbc3"]').click();
				console.info('Bridge Library: this resource was created manually; switching to data tab.');
			}
		}

	});
}(jQuery));
