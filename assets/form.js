
/**
 * @copyright Martin Procházka (c) 2023
 * @license   MIT License
 */

function initFormControls()
{
	$('.modal').on('hidden.bs.modal', () => $(this).children('form').reset?.());

	$('[data-signal]:is(button)').off('click').on('click', (e) => handleSignal(e.target));
	$('[data-signal]:not(button):is([type^="datetime"])').off('blur').on('blur', (e) => handleSignal(e.target));
	$('[data-signal]:not(button):not([type^="datetime"])').off('change').on('change', (e) => handleSignal(e.target));


	$('[data-auto-submit]').off('change').on('change', function(e) {
		let submitButton = $(this).parent().find('[type=submit]');
		return submitButton.click();
	});

	$('a[data-pwd-toggle]').off('click').on('click', function() {
		$('i.fas', this).toggleClass('fa-eye fa-eye-slash');
		$($(this).data('pwd-toggle')).attr('type', function(k, v) {
			return v == 'text' ? 'password' : 'text';
		});
	});

	$('.range-slider input[type="range"]').each(function() {
		let rangeValue = $(this).prev('.range-value');
		rangeValue.html(this.value);

		$(this).on('input', () => rangeValue.html(this.value));
	});

	if ($.fn.areYouSure) {
		$('[data-check-dirty]').areYouSure();

		let clearDirty = () => {
			$('[data-check-dirty]').trigger('reinitialize.areYouSure');
		};

		$('.modal').on('hidden.bs.modal', clearDirty);
		naja.addEventListener('before', clearDirty);
	}

	document.querySelectorAll('select:not(.custom-select,.select2),select.tom-select,input.tom-select').forEach((el) => {
		if (typeof TomSelect !== 'function') {
			return;
		}

		tomSelectInit(el);
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


function handleSignal(el)
{
	if (!('signal' in el.dataset)) {
		return;
	}

	let signalLink = el.dataset.signal.replace(/__?value_?/, el.value);
	let formData = new FormData(el.form); formData.delete('_do');

	// displayRequestSpinner(this);
	naja.makeRequest('POST', signalLink, formData);	// , {history: false}
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


function tomSelectInit(el)
{
	if (el.tomselect !== undefined) {
		return;
	}

	let formName = null;
	let options = {
		plugins: ['dropdown_input'],
		searchField: ['text'],
		labelField: 'text',
		valueField: 'id',
		create: 'tags' in el.dataset,
		createOnBlur: 'createOnBlur' in el.dataset,
		allowEmptyOption: true,
		addPrecedence: true,
		optgroupLabelField: 'group',
		optgroupValueField: 'group',
		optgroupField: 'group',
		render:{
			dropdown: () => '<div class="dropdown-menu"></div>',
			item: (data, escape) => tomSelectFormat('item', data, escape, el.multiple),
			option: (data, escape) => tomSelectFormat('option', data, escape, el.multiple),
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

	if ('pattern' in el.dataset) {
		options.createFilter = el.dataset.pattern;
	}

	if ('delimiter' in el.dataset) {
		options.delimiter = el.dataset.delimiter;
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

	// ? For back compatibility with old Select2 option
	if ('ajax-Url' in el.dataset && !('search' in el.dataset)) {
		el.dataset.search = el.dataset['ajax-Url'];
		delete el.dataset['ajax-Url'];
	}

	if ('search' in el.dataset) {
		options.plugins.push('virtual_scroll');
		options.sortField = [{field:'$order'},{field:'$score'}];
		options.searchField = [];
		options.allowEmptyOption = false;
		options.loadThrottle = 150;
		options.preload = 'focus';
		options.firstUrl = (query) => {
			let searchUrl = el.dataset.search.split('?');
			let url = new URL(window.location.href);
			url.pathname = searchUrl[0];
			url.search = searchUrl[1];
	
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

					// this.clearOptions();
					callback(items);
				})
				.catch(() => callback());
		};
	}

	return new TomSelect(el, options);
}


function tomSelectFormat(type, data, escape, isMultiple)
{
	let html = document.createElement('div');
	html.classList.add('text-truncate');
	html.append(escape(data.text));

	if (type === 'option') {
		html.classList.add('dropdown-item');
	}

	if (type === 'item' && data.group) {
		html.prepend(escape(data.group), ' - ');
	}

	if (type === 'item' && isMultiple && data.color) {
		html.classList.add(data.color.replace('text', 'bg'));

		if (data.content && data.content.includes('badge')) {
			data.content = '';
		}
	}

	if (data.icon && data.icon !== undefined) {
		let icon = document.createElement('i');
		icon.classList.add('fa', 'fa-fw', ... data.icon.split(' '));
		icon.style.marginRight = '2px';
		icon.style.marginTop = '2px';

		if (data.color && (type !== 'item' || !isMultiple)) {
			icon.classList.add(data.color);
		}

		html.prepend(icon, ' ');
	}

	if (data.content && data.content !== undefined) {
		const template = document.createElement('template');
		template.innerHTML = data.content;

		html.replaceChildren(template.content);
	}

	return html.outerHTML;
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
