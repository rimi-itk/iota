const $ = require('jquery');

$(document).ready(() => {
    $('[data-data]').on('click', (event) => {
        const data = $(event.target).data('data')
        const $form = $(event.target).closest('form')
        for (const [key, value] of Object.entries(data)) {
            $form.find('[name="form['+key+']"]').val(value)
        }
    })
})
