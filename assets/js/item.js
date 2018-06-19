require('../css/item.scss');

import JSONFormatter from 'json-formatter-js'

window.addEventListener('load', () => {
		Array.prototype.slice.call(document.querySelectorAll('[data-json]')).forEach((item) => {
				const json = JSON.parse(item.getAttribute('data-json'));
				const formatter = new JSONFormatter(json);

				while (item.firstChild) {
						item.removeChild(item.firstChild);
				}
				item.appendChild(formatter.render());
		})
})
