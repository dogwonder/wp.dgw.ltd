window.gform.addFilter(
	'gppa_primary_property_computed',
	function (primaryProperty: string, vm: any) {
		if (vm.objectType !== 'gpgs_sheet') {
			return primaryProperty;
		}

		// Split the primary property and ensure we have both the spreadsheet and sheet.
		const primaryPropertySplit = primaryProperty.split('|');

		if (primaryPropertySplit.length !== 2) {
			return null;
		}

		return primaryProperty;
	}
);

window.gform.addFilter(
	'gppa_primary_property_component',
	function (extras: any, vm: any, Vue: any) {
		if (vm.objectType !== 'gpgs_sheet') {
			return extras;
		}

		return Vue.extend({
			data() {
				return {
					sheets: [],
				};
			},
			created() {
				if (this.spreadsheet) {
					this.getSheets();
				}
			},
			methods: {
				getSheets() {
					// @ts-ignore
					this.sheets = [];

					jQuery
						.post(
							window.ajaxurl,
							{
								action: 'gpgs_gppa_get_sheets',
								// @ts-ignore
								spreadsheet_id: this.spreadsheet,
								security: window.GPPA_ADMIN.nonce,
							},
							null,
							'json'
						)
						.done((data) => {
							// @ts-ignore
							this.sheets = data;
						});
				},
			},
			computed: {
				primaryPropertySplit() {
					const primaryProperty = vm.primaryProperty;

					if (primaryProperty) {
						return primaryProperty.split('|');
					}

					return [undefined, undefined];
				},
				spreadsheet() {
					// @ts-ignore
					return this.primaryPropertySplit[0];
				},
				sheet() {
					// @ts-ignore
					return this.primaryPropertySplit[1];
				},
			},
			render(h: any) {
				const elements = [
					h(
						'label',
						{
							class: 'section_label gppa-primary-property-label',
							style: 'margin-top: 15px;',
						},
						'Spreadsheet'
					), // i18n
				];

				const { sheets } = this;

				if (
					!('primary-property' in vm.propertyValues) ||
					!Array.isArray(vm.propertyValues['primary-property'])
				) {
					elements.push(
						h(
							'select',
							{
								key: 'loading',
								class: 'gppa-primary-property',
								attrs: {
									disabled: true,
								},
							},
							[
								h(
									'option',
									{
										attrs: {
											value: '',
											disabled: true,
											selected: true,
										},
									},
									vm.i18nStrings.loadingEllipsis
								),
							]
						)
					);
				} else {
					elements.push(
						h(
							'select',
							{
								class: 'gppa-primary-property',
								domProps: {
									value: this.spreadsheet,
								},
								on: {
									change: (event: any) => {
										const value = event.target.value;
										vm.primaryProperty = value;
										vm.changePrimaryProperty(value);

										this.getSheets();
									},
								},
							},
							[
								h(
									'option',
									{
										attrs: {
											value: '',
											hidden: true,
											disabled: true,
											selected: true,
										},
									},
									vm.i18nStrings.selectAnItem.replace(
										/%s/g,
										vm.objectTypeInstance[
											'primary-property'
										].label
									)
								),
								vm.propertyValues['primary-property'].map(
									(option) => {
										return h(
											'option',
											{
												domProps: {
													value: option.value,
												},
											},
											vm.truncateStringMiddle(
												option.label
											)
										);
									}
								),
							]
						)
					);
				}

				if (this.spreadsheet) {
					elements.push(
						h(
							'label',
							{
								class: 'section_label gppa-primary-property-label',
								style: 'margin-top: 15px;',
							},
							'Sheet'
						)
					); // i18n

					if (sheets.length) {
						elements.push(
							h(
								'select',
								{
									class: 'gppa-primary-property',
									domProps: {
										value: this.sheet,
									},
									on: {
										change: (event: any) => {
											const value =
												this.spreadsheet +
												'|' +
												event.target.value;

											vm.primaryProperty = value;
											vm.changePrimaryProperty(value);
										},
									},
								},
								[
									h(
										'option',
										{
											attrs: {
												value: '',
												hidden: true,
												disabled: true,
												selected: true,
											},
										},
										'Select a Sheet'
									), // i18n
									this.sheets.map((option: any) => {
										return h(
											'option',
											{
												domProps: {
													value: option.value,
												},
											},
											vm.truncateStringMiddle(
												option.label
											)
										);
									}),
								]
							)
						);
					} else {
						elements.push(
							h(
								'select',
								{
									key: 'loading',
									class: 'gppa-primary-property',
									attrs: {
										disabled: true,
									},
								},
								[
									h(
										'option',
										{
											attrs: {
												value: '',
												hidden: true,
												disabled: true,
												selected: true,
											},
										},
										vm.i18nStrings.selectAnItem.replace(
											/%s/g,
											'Sheet'
										)
									),
									h(
										'option',
										{
											attrs: {
												value: '',
												disabled: true,
												selected: true,
											},
										},
										vm.i18nStrings.loadingEllipsis
									),
								]
							)
						);
					}
				}

				return h('div', {}, elements);
			},
		});
	}
);
