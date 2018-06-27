var $ = require('jquery');

$(document).ready(() => {
    const $handle = $('#set_dimming_level_value')
    const $label = $('[for="'+$handle.attr('id')+'"]')
    const update = () => {
		$label.html($handle.val())
	}

	update()
	$handle.on('input', update)
})
