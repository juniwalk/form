
/**
 * @copyright Martin Procházka (c) 2026
 * @license   MIT License
 */

class FormSelectExtension
{
	initialize(naja) {
		if (typeof TomSelect !== 'function') {
			return;
		}

		naja.snippetHandler.addEventListener('afterUpdate', (event) => this.#attach(event.detail.snippet));

		this.#attach(document);
	}


	#attach(snippet) {
		snippet.querySelectorAll('select:not(.custom-select),select.tom-select,input.tom-select')
			.forEach((element) => this.#createTomSelect(element));
	}


	#createTomSelect(element) {
		// ? TomSelect is already attached to element
		if (element.tomselect !== undefined) {
			return;
		}

		let formName = null;
		let options = {
			plugins: ['dropdown_input'],
			searchField: ['text', 'group'],
			labelField: 'text',
			valueField: 'id',
			create: 'tags' in element.dataset,
			createOnBlur: 'createOnBlur' in element.dataset,
			allowEmptyOption: true,
			addPrecedence: true,
			optgroupLabelField: 'group',
			optgroupValueField: 'group',
			optgroupField: 'group',
			render: {
				dropdown: () => '<div class="dropdown-menu"></div>',
				item: (data, escape) => this.#formatOption('item', data, escape, element.multiple),
				option: (data, escape) => this.#formatOption('option', data, escape, element.multiple),
				option_create: (data, escape) => `<div class="dropdown-item create">Add <strong>${escape(data.input)}</strong>&hellip;</div>`,
				optgroup_header: (data, escape) => `<div class="dropdown-header">${escape(data.group)}</div>`,
				no_results: (data, escape) => `<div class="dropdown-item disabled">No results found for "${escape(data.input)}"</div>`,
				no_more_results: () => `<div class="dropdown-item disabled">No more results</div>`,
				loading_more: () => '<div class="dropdown-item disabled"><i class="fas fa-fw fa-rotate fa-spin"></i> Loading&hellip;</div>',
				loading: () => '<div class="dropdown-item disabled"><i class="fas fa-fw fa-rotate fa-spin"></i> Loading&hellip;</div>',
				not_loading: () => {
					if (Object.keys(element.tomselect.options).length > 0) {
						return;
					}

					return `<div class="dropdown-item disabled">No results found</div>`;
				}
			}
		};

		if (element.form && 'formName' in element.form.dataset) {
			formName = element.form.dataset.formName + '-';
		}

		if ('pattern' in element.dataset) {
			options.createFilter = element.dataset.pattern;
		}

		if ('delimiter' in element.dataset) {
			options.delimiter = element.dataset.delimiter;
		}

		if ('noSearch' in element.dataset) {
			options.controlInput = null;
			options.plugins = [];
		}

		if (element.hasAttribute('multiple')) {
			options.plugins = [];
			options.plugins.push('caret_position');
			options.plugins.push('input_autogrow');
			options.plugins.push('remove_button');
		}

		if ('search' in element.dataset) {
			options.plugins.push('virtual_scroll');
			options.sortField = [{field:'$order'},{field:'$score'}];
			options.searchField = [];
			options.allowEmptyOption = false;
			options.loadThrottle = 150;
			options.preload = 'focus';

			// todo: move into private handler
			options.firstUrl = (query) => {
				let searchUrl = element.dataset.search.split('?');
				let url = new URL(window.location.href);
				url.pathname = searchUrl[0];
				url.search = searchUrl[1];

				let params = this.#findPrefixedUrlParams(formName);
				Object.entries(params).forEach(([key, value]) => {
					url.searchParams.set(key, value);
				});

				url.searchParams.append(formName+'term', query);
				url.searchParams.append(formName+'page', 1);
				return url;
			},

			// todo: move into private handler
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

		// Allow dropup if there is no space for dropdown
		if (typeof Popper !== 'function') {
			options.onInitialize = function() {
				this.popper = Popper.createPopper(this.control, this.dropdown);
			};

			options.onDropdownOpen = function() {
				this.popper.update();
			};
		}

		return new TomSelect(element, options);
	}


	#formatOption(type, data, escape, isMultiple) {
		let text = document.createElement('span');
		text.append(escape(data.text));
		text.classList.add('text-truncate');

		let html = document.createElement('div');
		html.classList.add('d-flex');
		html.classList.add('gap-2');
		html.append(text);

		if (type === 'option') {
			html.classList.add('dropdown-item');
		}

		if (type === 'item' && data.group) {
			let group = document.createElement('span');
			group.append(escape(data.group));

			html.prepend(group, ' - ');
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
			icon.style.marginRight = '6px';
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


	#findPrefixedUrlParams(prefix) {
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
}

// ? Auto register the extension in Naja.js
if (typeof naja !== 'undefined') {
	naja?.registerExtension(new FormSelectExtension);
}
