
/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

function initFormControls()
{
	document.querySelectorAll('.tom-select').forEach((el) => {
		let formName = el.form.dataset.formName;
		let options = {
			plugins: ['dropdown_input'],
			searchField: ['text'],
			labelField: 'text',
			valueField: 'id',
			create: el.dataset['tags'] !== undefined,
			optgroupLabelField: 'group',
			optgroupValueField: 'group',
			optgroupField: 'group',
			render:{
				item: (item, escape) => '<div>' + (item.content || escape(item.text)) + '</div>',
				dropdown: () => '<div class="dropdown-menu"></div>',
				option: (item, escape) => '<div class="dropdown-item">' + (item.content || escape(item.text)) + '</div>',
				option_create: (data, escape) => '<div class="dropdown-item create">Add <strong>' + escape(data.input) + '</strong>&hellip;</div>',
				no_results: (data, escape) => '<div class="dropdown-item disabled">No results found for "' + escape(data.input) + '"</div>',
				loading: () => '<div class="dropdown-item disabled"><i class="fas fa-fw fa-rotate fa-spin"></i> Loading&hellip;</div>'
			}
		};

		if (el.classList.contains('ajax') || el.dataset['ajax-Url'] !== undefined) {
			options.plugins.push('virtual_scroll');
			options.loadThrottle = 150;
			options.preload = 'focus';
			options.firstUrl = function(query) {
				let url = new URL(el.dataset['ajax-Url']);		
				let params = findPrefixedUrlParams(formName);

				Object.entries(params).forEach(([key, value]) => url.searchParams.set(key, value));

				url.searchParams.append(formName+'-term', query);
				url.searchParams.append(formName+'-page', 1);

				return url;
			},

			options.load = function(query, callback) {
				let url = this.getUrl(query);
				naja.makeRequest('GET', url, {}, {history: false})
					.then((json) => {
						if (json.pagination.more){
							url.searchParams.set(formName+'-page', json.pagination.page +1);
							this.setNextUrl(query, url);
						}

						callback(json.results);
					})
					.catch(() => callback());
			};
		}

		let tomSelect = new TomSelect(el, options);
	});

	$('select,input.select2').not('.tom-select,.no-select2,.custom-select,.flatpickr-monthDropdown-months').each(function() {
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
