var $ = require('jquery');

$(document).ready(() => {
    const $handle = $('#form_value')
    const $label = $('[for="'+$handle.attr('id')+'"]')
    const update = () => {
		$label.html($handle.val())
	}

	update()
	$handle.on('input', update)
})
