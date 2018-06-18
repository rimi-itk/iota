require('../css/app.scss');

var $ = require('jquery');
require('bootstrap-sass');

$(document).ready(() => {
		var $handle = $('#form_value'),
				$label = $('[for="'+$handle.attr('id')+'"]'),
				update = () => {
						$label.html($handle.val());
				};

		update();
		$handle.on('input', update);
})
