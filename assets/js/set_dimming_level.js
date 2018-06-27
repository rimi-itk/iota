var $ = require('jquery');

$(document).ready(() => {
	var $handle = $('#set_dimming_level_value'),
		$label = $('[for="'+$handle.attr('id')+'"]'),
		update = () => {
			$label.html($handle.val());
		};

	update();
	$handle.on('input', update);

    $('[data-eui]').on('click', (event) => {
        $('#set_dimming_level_eui').val($(event.target).data('eui'));
    });
})
