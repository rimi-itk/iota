require('../css/app.scss');

var $ = require('jquery');
require('bootstrap');

$(document).ready(() => {
		var $handle = $('#form_value'),
				$label = $('[for="'+$handle.attr('id')+'"]'),
				update = () => {
						$label.html($handle.val());
				};

		update();
		$handle.on('input', update);

		$('#broadcast_form').submit();

    $('[data-form-eui]').on('click', (event) => {
        $('#form_eui').val($(event.target).data('form-eui'));
    });
})
