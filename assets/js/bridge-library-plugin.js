/* global bridgeLibrary, jQuery */
'use strict';

(function($) {
	$(document).ready(function() {

		/**
		 * Handle favorites.
		 */
		$('.favorite').on('click', function () {
			var $element = $(this),
				favorited = $element.parent('.card').hasClass('favorited'),
				action = '';

			if (favorited) {
				action = 'bridge_library_remove_user_favorite';
			} else {
				action = 'bridge_library_add_user_favorite';
			}

			$.ajax({
				url: bridgeLibrary.adminAjax,
				method: 'POST',
				data: {
					action: action,
					nonce: $(this).data('nonce'),
					id: $(this).data('id'),
				}
			}).done(function(data) {
				console.log(data);
				$element.parent('.card').toggleClass('favorited');
			}).fail(function(error) {
				console.error(error);
			});
		});
	});
}(jQuery));
