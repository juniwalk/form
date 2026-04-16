
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

		naja.addEventListener('success', (event) => {
			let element = document.getElementById(event.detail.payload.control);
			let snippet = event.detail.payload.snippet;
			this.#insertAtCursor(element, snippet);
		});

		naja.addEventListener('success', () => {
			document.querySelectorAll('.tooltip.show, .popover.show')
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
					element.querySelector('form')?.reset?.();
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


		snippet.querySelectorAll('[data-target][data-insert]')
			.forEach((element) => {
				let input = document.getElementById(element.dataset.target);
				let snippet = element.dataset.insert;

				if (!(input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement)) {
					return;
				}

				element.addEventListener('click', () => this.#insertAtCursor(input, snippet));
			});


		snippet.querySelectorAll('[data-target][data-count]')
			.forEach((element) => {
				let input = document.getElementById(element.dataset.target);
				let targetCount = element.dataset.count;

				if (!(input instanceof HTMLInputElement || input instanceof HTMLTextAreaElement)) {
					return;
				}

				if (targetCount === 'maxlength') {
					targetCount = input.getAttribute('maxlength');
				} else {
					targetCount = parseInt(targetCount, 10);
				}

				input.addEventListener('input', () => {
					let currentLength = input.value.length;
					let targetPercent = currentLength / targetCount * 100;

					element.classList.remove('text-bg-success', 'text-bg-warning', 'text-bg-danger', 'text-bg-secondary');
					element.textContent = targetCount - currentLength;

					if (targetPercent === 100) {
						element.classList.add('text-bg-secondary');

					} else if (targetPercent > 75) {
						element.classList.add('text-bg-danger');

					} else if (targetPercent > 50) {
						element.classList.add('text-bg-warning');

					} else {
						element.classList.add('text-bg-success');
					}
				});

				input.dispatchEvent(new Event('input'));
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


		snippet.querySelectorAll('.range-slider input[type="range"]')
			.forEach((element) => {
				let rangeValue = element.closest('.range-slider')
					.querySelector('.range-value');

				element.addEventListener('input', () => rangeValue.innerHTML = element.value);
				element.dispatchEvent(new Event('input'));
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
			element.tomselect?.wrapper.classList.add('is-invalid');
			element.classList.add('is-invalid');
		}

		element.removeAttribute('data-invalid');
		element.addEventListener('input', () => {
			element.setCustomValidity('');
			element.tomselect?.wrapper.classList.remove('is-invalid');
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


	#insertAtCursor(element, snippet) {
		if (!snippet?.length || !(element instanceof HTMLInputElement || element instanceof HTMLTextAreaElement)) {
			return;
		}

		const start = element.selectionStart;
		const end = element.selectionEnd;

		// ! Firefox does not save this into history so it cannot be undone with Ctrl+Z
		element.setRangeText(snippet, start, end, 'end');
		element.focus();
	}
}

// ? Auto register the extension in Naja.js
if (typeof naja !== 'undefined') {
	naja?.registerExtension(new FormExtension);
}
