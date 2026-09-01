
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
			options.allowEmptyOption = false;
			options.loadThrottle = 150;
			options.preload = 'focus';

			options.load	 = (query, callback)	=> this.#searchLoad(element.tomselect, formName, query, callback);
			options.firstUrl = (query)				=> this.#searchFirstUrl(element.dataset.search, formName, query);
			options.score	 = () => () => 1;
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


	#searchLoad(tomselect, formName, query, callback) {
		let url = tomselect.getUrl(query);
		let isFirstPage = url.searchParams.get(formName+'page') === '1';

		this.#searchRequestPage(tomselect, formName, url).then(({items, nextUrl}) => {
			if (nextUrl) {
				tomselect.setNextUrl(query, nextUrl);
			}

			if (!isFirstPage || items.length > 7) {
				return callback(items);
			}

			// ? Preload the next page immediately to ensure enough items are available for virtual scrolling
			return this.#searchRequestPage(tomselect, formName, nextUrl).then((page) => {
				if (page.nextUrl) {
					tomselect.setNextUrl(query, page.nextUrl);
				}

				callback(items.concat(page.items));
			})
			.catch(() => callback(items));
		})
		.catch(() => callback());
	}


	#searchRequestPage(tomselect, formName, url) {
		return naja.makeRequest('GET', url, {}, {history: false}).then((json) => {
			let items = json.results.map((item) => {
				if (item.children) {
					tomselect.addOptionGroup(item.text, item);
				}

				return item.children || item;
			});

			let nextUrl = null;

			if (json.pagination.more) {
				nextUrl = new URL(url);
				nextUrl.searchParams.set(formName+'page', json.pagination.page +1);
			}

			return {items, nextUrl};
		});
	}


	#formatOption(type, data, escape, isMultiple) {
		let text = document.createElement('span');
		text.append(escape(data.text));
		text.classList.add('text-truncate');

		let html = document.createElement('div');
		html.classList.add('align-items-center');
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


	#searchFirstUrl(searchUrl, prefix, query) {
		let url = new URL(searchUrl, window.location.href);
		let params = this.#findCurrentUrlParams(prefix);

		// ? Pass current form prefixed url params to search url
		Object.entries(params).forEach(([key, value]) => {
			url.searchParams.set(key, value);
		});

		url.searchParams.append(prefix+'term', query);
		url.searchParams.append(prefix+'page', 1);
		return url;
	}


	#findCurrentUrlParams(prefix) {
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
