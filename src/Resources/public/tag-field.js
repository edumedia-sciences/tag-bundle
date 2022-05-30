window.addEventListener('load', () => {
    document.querySelectorAll('.field-edumedia-tag input[type=text]').forEach($input => {
        const suggestions = JSON.parse($input.dataset.suggestions)
        const options = []
        suggestions.forEach(tag => {
            options.push({value: tag, text: tag})
        })

        new window.TomSelect($input, {
            options,
            items: JSON.parse($input.dataset.values),
            create: true,
            plugins: ['clear_button', 'input_autogrow', 'remove_button'],
        })
    })
})