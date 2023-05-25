
/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

function initFormControls()
{
	if (typeof TomSelect === 'function') document.querySelectorAll('select:not(.custom-select,.select2),select.tom-select,input.tom-select').forEach((el) => {
		if (el.tomselect !== undefined) {
			return;
		}

		let formName = null;
		let options = {
			plugins: ['dropdown_input'],
			searchField: ['text'],
			labelField: 'text',
			valueField: 'id',
			create: el.dataset['tags'] !== undefined,
			allowEmptyOption: true,
			addPrecedence: true,
			optgroupLabelField: 'group',
			optgroupValueField: 'group',
			optgroupField: 'group',
			render:{
				dropdown: () => '<div class="dropdown-menu"></div>',
				item: (data, escape) => tomSelectFormat('item', data, escape),
				option: (data, escape) => tomSelectFormat('option', data, escape),
				option_create: (data, escape) => `<div class="dropdown-item create">Add <strong>${escape(data.input)}</strong>&hellip;</div>`,
				optgroup_header: (data, escape) => `<div class="dropdown-header">${escape(data.text)}</div>`,
				no_results: (data, escape) => `<div class="dropdown-item disabled">No results found for "${escape(data.input)}"</div>`,
				no_more_results: () => `<div class="dropdown-item disabled">No more results</div>`,
				loading_more: () => '<div class="dropdown-item disabled"><i class="fas fa-fw fa-rotate fa-spin"></i> Loading&hellip;</div>',
				loading: () => '<div class="dropdown-item disabled"><i class="fas fa-fw fa-rotate fa-spin"></i> Loading&hellip;</div>',
				not_loading: () => {	
					if (Object.keys(el.tomselect.options).length > 0) {
						return;
					}

					return `<div class="dropdown-item disabled">No results found</div>`;
				}
			}
		};

		if (el.form && 'formName' in el.form.dataset) {
			formName = el.form.dataset.formName + '-';
		}

		if ('noSearch' in el.dataset) {
			options.controlInput = null;
			options.plugins = [];
		}

		if (el.hasAttribute('multiple')) {
			options.plugins = [];
			options.plugins.push('caret_position');
			options.plugins.push('input_autogrow');
			options.plugins.push('remove_button');
		}

		if (el.dataset['ajax-Url'] !== undefined) {
			options.plugins.push('virtual_scroll');
			options.allowEmptyOption = false;
			options.loadThrottle = 150;
			options.preload = 'focus';
			options.firstUrl = function(query) {
				let ajaxUrl = el.dataset['ajax-Url'].split('?');
				let url = new URL(window.location.href);
				url.pathname = ajaxUrl[0];
				url.search = ajaxUrl[1];
	
				let params = findPrefixedUrlParams(formName);
				Object.entries(params).forEach(([key, value]) => {
					url.searchParams.set(key, value);
				});

				url.searchParams.append(formName+'term', query);
				url.searchParams.append(formName+'page', 1);

				return url;
			},

			options.load = function(query, callback) {
				let url = this.getUrl(query);
				naja.makeRequest('GET', url, {}, {history: false})
					.then((json) => {
						if (json.pagination.more){
							url.searchParams.set(formName+'page', json.pagination.page +1);
							this.setNextUrl(query, url);
						}

						let items = json.results.map((item) => {
							if (item.children) {
								this.addOptionGroup(item.text, item);
							}

							return item.children || item;
						});

						callback(items);
					})
					.catch(() => callback());
			};
		}

		let tomSelect = new TomSelect(el, options);
	});

	if ($().select2 !== undefined) $('select,input.select2').not('.tom-select,.custom-select,.flatpickr-monthDropdown-months').each(function() {
		let formName = null;
		let options = {
			minimumResultsForSearch: 20,
			templateSelection: select2Format,
			templateResult: select2Format,
		};

		if (this.form && 'formName' in this.form.dataset) {
			formName = this.form.dataset.formName + '-';
		}

		if (this.closest('.modal')) {
			options.dropdownParent = this.parentNode.parentNode;
		}

		if (this.classList.contains('ajax') || this.dataset['ajax-Url'] !== undefined) {
			options.minimumResultsForSearch = 0;
			options.ajax = {delay: 250, cache: true, transport: (request, done, error) => {
				let params = findPrefixedUrlParams(formName);
				params[formName+'term'] = request.data.term || '';
				params[formName+'page'] = request.data.page || 1;

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

	document.querySelectorAll('a[data-clear-input]').forEach((el) => {
		el.addEventListener('click', ({currentTarget}) => {
			let input = document.querySelector(currentTarget.dataset.clearInput);
			input.value = '';

			if ('tomselect' in input) {
				input.tomselect.setValue('');
			}
	
			input.dispatchEvent(new Event('change'));
		});
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


function tomSelectFormat(type, data, escape)
{
	let content = data.content || escape(data.text);
	let html = '<div class="text-truncate">';

	if (!data.content && data.group && type === 'item') {
		content = escape(data.group) + ' - ' + content;
	}

	if (type === 'option') {
		html = html.replace('">', ' dropdown-item">');
	}

	if (!data.content && data.icon !== undefined) {
		html += `<i class="fa ${data.icon} fa-fw ${data.color || ''}"></i> `;
	}

	return html + content + '</div>';
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
