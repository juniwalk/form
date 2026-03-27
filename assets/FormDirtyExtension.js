
/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

class FormDirtyExtension
{
	constructor(options = {}) {
		this.selector = options.selector || 'form[data-check-dirty="true"]';
		this.forms = new Map();
	}


	initialize(naja) {
		naja.snippetHandler.addEventListener('afterUpdate', (event) => this.#attach(event.detail.snippet));

		this.#attach(document);


		window.addEventListener('beforeunload', (event) => {
			const isAnyFormDirty = this.forms.keys()
				.some(form => form.dataset.isDirty === 'true');

			if (!isAnyFormDirty) {
				return;
			}

			event.preventDefault();
			event.returnValue = '';
		});
	}


	#attach(snippet) {
		snippet.querySelectorAll(this.selector)
			.forEach((form) => {
				if (!(form instanceof HTMLFormElement)) {
					return;
				}

				form.addEventListener('submit', () => this.#initState(form));
				form.addEventListener('reset', () => this.#initState(form));
				form.addEventListener('change', () => this.#updateState(form));
				form.addEventListener('input', () => this.#updateState(form));

				this.#initState(form);
			});

		snippet.querySelectorAll('.modal')
			.forEach((element) => element.addEventListener('hide.bs.modal', (event) => {
				const form = element.querySelector(this.selector);

				if (form instanceof HTMLFormElement) {
					this.#checkState(form, event);
				}
			}));
	}


	#initState(form) {
		this.forms.set(form, this.#serialize(form));
		form.dataset.isDirty = (false).toString();
	}


	#updateState(form) {
		const current = this.#serialize(form);
		const initial = this.forms.get(form);
		const isDirty = initial !== current;

		form.dataset.isDirty = isDirty.toString();
	}


	#checkState(form, event) {
		if (!form.dataset.isDirty || form.dataset.isDirty === 'false') {
			return;
		}

		if (confirm('Do you want to close the form?')) {
			this.#initState(form);
			return;
		}

		event.preventDefault();
	}


	#serialize(form) {
		return new URLSearchParams(new FormData(form)).toString();
	}
}

// ? Auto register the extension in Naja.js
if (typeof naja !== 'undefined') {
	naja?.registerExtension(new FormDirtyExtension);
}
