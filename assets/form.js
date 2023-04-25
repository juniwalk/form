
/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

function initFormControls()
{
	$('select,input.select2').not('.custom-select,.flatpickr-monthDropdown-months').each(function() {
		let formName = this.form.dataset.formName;
		let options = {
			minimumResultsForSearch: 20,
			templateSelection: select2Format,
			templateResult: select2Format,
		};

		if (this.closest('.modal')) {
			options.dropdownParent = this.parentNode.parentNode;
		}

		if (this.classList.contains('ajax') || this.dataset['ajax-Url'] !== undefined) {
			options.minimumResultsForSearch = 0;
			options.ajax = {delay: 250, cache: true, transport: (request, done, error) => {
				let params = findPrefixedUrlParams(formName);
				params[formName+'-term'] = request.data.term || '';
				params[formName+'-page'] = request.data.page || 1;

				naja.makeRequest(request.type, request.url, params, {history: false})
					.then(done).catch(error);
			}};
		}

		$(this).select2(options).on('select2:open', () => {
			document.querySelector('.select2-container--open .select2-search__field').focus();
		});
	});

	$('[data-signal]').off('click change').on('click change', function(e) {
		if (!this.matches('BUTTON') && e.type == 'click') {
			return;
		}

		let signalLink = this.dataset.signal.replace(/__?value_?/, this.value);
		let formData = new FormData(this.form); formData.delete('_do');

		displayRequestSpinner(this);
		naja.makeRequest('POST', signalLink, formData);
	});

	$('a[data-pwd-toggle]').off('click').on('click', function() {
		$('i.fas', this).toggleClass('fa-eye fa-eye-slash');
		$($(this).data('pwd-toggle')).attr('type', function(k, v) {
			return v == 'text' ? 'password' : 'text';
		});
	});

	$('a[data-clear-input]').off('click').on('click', function() {
		$($(this).data('clear-input')).val('').trigger('change');
	});
}


function findPrefixedUrlParams(prefix)
{
	let url = new URL(document.location);		
	let urlSearch = new URLSearchParams(url.search);
	let params = {};
	
	for (let key of urlSearch.keys()) {
		if (!key.startsWith(prefix)) {
			continue;
		}

		params[key] = urlSearch.get(key);
	}

	return params;
}


/**
 * @see https://stackoverflow.com/a/11077016
 */
function insertAtCursor(input, value)
{
	// IE support
	if (document.selection) {
		input.focus();
		sel = document.selection.createRange();
		sel.text = value;

	// MOZILLA and others
	} else if (input.selectionStart || input.selectionStart == '0') {
		let textStart = input.value.substring(0, input.selectionStart);
		let textEnd = input.value.substring(input.selectionEnd);

		input.value = textStart + value + textEnd;

	} else {
		input.value += value;
	}

	input.value = input.value.trim();
}


function select2Format(state)
{
	let $option = $(state.element);
	let $value = null;

	if (($value = $option.data('content')) || ($value = state.content)) {
		return $('<span>').html($value);
	}

	if (($value = $option.data('icon')) || ($value = state.icon)) {
		let $color = ($option.data('color')) || (state.color);
		return $('<span><i class="fa '+ $value +' fa-fw '+ $color +'"></i> '+ state.text +'</span>');
	}

	return state.text;
}


$(function() {
	'use strict'

	naja.snippetHandler.addEventListener('afterUpdate', () => initFormControls());
	naja.addEventListener('init', () => initFormControls());
	naja.addEventListener('success', ({detail}) => {
		let input = document.getElementById(detail.payload.control);
		let snippet = detail.payload.snippet;

		if (!input || !snippet) {
			return;
		}

		let cursorAfter = input.selectionStart + snippet.length;

		insertAtCursor(input, snippet);

		input.selectionEnd = cursorAfter;
		input.focus();
	});

});
