
/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

class FormTextareaExtension
{
	initialize(naja) {
		if (typeof jQuery === 'undefined') {
			console.log('Missing jQuery framework');
			return;
		}

		if (typeof $.fn.summernote === 'undefined') {
			console.log('Missing Summernote component');
			return;
		}

		naja.snippetHandler.addEventListener('afterUpdate', (event) => this.#attach(event.detail.snippet));

		this.#attach(document);
	}


	#attach(snippet) {
		$('.summernote', snippet).summernote({
			codeviewIframeFilter: false,
			disableDragAndDrop: true,
			inheritPlaceholder: true,
			callbacks: {
				onChange: (code) => $(this).val(code),
			},

			toolbar:
			[
				['style', ['style']],
				['font', ['bold', 'italic', 'underline', 'strikethrough', 'superscript', 'subscript']],
				['color', ['color']],
				['para', ['ul', 'ol', 'paragraph']],
				['insert', ['link', 'picture', 'video', 'hr']],
				['table', ['table']],
				['view', ['clear', 'fullscreen', 'codeview', 'help']]
			]
		});
	}
}

// ? Auto register the extension in Naja.js
if (typeof naja !== 'undefined') {
	naja?.registerExtension(new FormTextareaExtension);
}
