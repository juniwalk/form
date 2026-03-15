
/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

class FormExtension
{
	initialize(naja) {
		const validateControl = Nette.validateControl.bind(Nette);

		Nette.validateControl = (...args) => {
			const result = validateControl(...args);
			return this.#validState(args[0], result);
		};


		naja.snippetHandler.addEventListener('afterUpdate', (event) => this.#attach(event.detail.snippet));
		naja.addEventListener('success', (event) => this.#insertAtCursor(event));
		naja.addEventListener('success', () => {
			document.querySelectorAll('.tooltip.show')
				.forEach(element => element.remove());
		});

		this.#attach(document);
	}


	#attach(snippet) {
		snippet.querySelectorAll('[data-invalid]')
			.forEach(element => this.#validState(element, false));


		snippet.querySelectorAll('.modal')
			.forEach((element) => {
				element.addEventListener('hidden.bs.modal', () => {
					element.querySelector('form').reset?.();
				});
			});


		snippet.querySelectorAll('[data-signal]')
			.forEach((element) => {
				// ? Handle signal on click
				if (element.matches(':is(button)')) {
					element.addEventListener('click', (event) => this.#handleSignal(event, element));
				}

				// ? Handle signal on blur
				else if (element.matches(':is([type^="datetime"])')) {
					element.addEventListener('blur', (event) => this.#handleSignal(event, element));
				}

				// ? Handle signal on change
				else {
					element.addEventListener('change', (event) => this.#handleSignal(event, element));
				}
			});


		snippet.querySelectorAll('input[type=password][data-toggle="password"]')
			.forEach(element => {
				let group = element.closest('.input-group');

				if (!group) {
					group = document.createElement('div');
					group.className = 'input-group';

					element.parentNode.insertBefore(group, element);
					group.appendChild(element);
				}

				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'btn btn-outline-secondary';
				button.innerHTML = '<i class="fa-solid fa-eye fa-fw"></i>';

				group.appendChild(button);

				button.addEventListener('click', () => {
					const isPassword = element.type === 'password';
					element.type = isPassword ? 'text' : 'password';

					button.classList.toggle('active', isPassword);

					const icon = button.querySelector('i');
					icon.classList.toggle('fa-eye', !isPassword);
					icon.classList.toggle('fa-eye-slash', isPassword);
				});
			});


		snippet.querySelectorAll('[data-toggle="clear"]')
			.forEach(function(element) {
				const group = element.closest('.form-group');
				if (!group) return;

				let toolbar = group.querySelector('.btn-toolbar');

				if (!toolbar) {
					toolbar = document.createElement('div');
					toolbar.className = 'btn-toolbar float-end gap-2';
					group.insertBefore(toolbar, group.firstChild);
				}

				const button = document.createElement('button');
				button.type = 'button';
				button.className = 'btn btn-outline-danger btn-xs d-none';
				button.innerHTML = '<i class="fa-solid fa-times fa-fw"></i>';

				button.addEventListener('click', function() {
					element.tomselect?.setValue('');
					element.value = '';

					element.dispatchEvent(new Event('input'));
					toggleClearButton();
				});

				toolbar.appendChild(button);

				function toggleClearButton() {
					if (element.value !== '') {
						button.classList.remove('d-none');
						button.classList.add('d-inline-block');
					} else {
						button.classList.remove('d-inline-block');
						button.classList.add('d-none');
					}
				}

				element.tomselect?.on('change', toggleClearButton);
				element.addEventListener('input', toggleClearButton);
				element.addEventListener('change', toggleClearButton);

				toggleClearButton();
			});


		snippet.querySelectorAll('input, select, textarea')
			.forEach((element) => {
				if (!element.hasAttribute('required')) {
					return;
				}

				const label = element.labels?.[0];

				if (label && !label.querySelector('.required-indicator')) {
					const indicator = document.createElement('span');
					indicator.className = 'required-indicator sr-only';
					indicator.textContent = ' (required)';
					label.append(indicator);
				}
			});
	}


	#validState(element, isValid = false) {
		if (!isValid) {
			element.setCustomValidity('Invalid field');
			element.classList.add('is-invalid');
		}

		element.removeAttribute('data-invalid');
		element.addEventListener('input', () => {
			element.setCustomValidity('');
			element.classList.remove('is-invalid');
		});

		return isValid;
	}


	#handleSignal(event, element) {
		if (!('signal' in element.dataset)) {
			return;
		}

		let url = element.dataset.signal.replace(/__?value_?/, element.value);
		let formData = new FormData(element.form); formData.delete('_do');

		naja.makeRequest('POST', url, formData);	// , {history: false}
	}


	#insertAtCursor(event) {
		let element = document.getElementById(event.detail.payload.control);
		let snippet = event.detail.payload.snippet;

		if (!element || !snippet) {
			return;
		}

		let endPosition = element.selectionStart + value.length;

		if (element.selectionStart || element.selectionStart == '0') {
			let textStart = element.value.substring(0, element.selectionStart);
			let textEnd = element.value.substring(element.selectionEnd);

			element.value = textStart + value + textEnd;

		} else {
			element.value += value;
		}

		element.value = element.value.trim();
		element.selectionEnd = endPosition;
		element.focus();
	}
}

// ? Auto register the extension in Naja.js
if (typeof naja !== 'undefined') {
	naja?.registerExtension(new FormExtension);
}
