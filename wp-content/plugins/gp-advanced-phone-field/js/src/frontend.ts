import intlTelInput from 'intl-tel-input';

const $ = window.jQuery!;

interface GPAdvancedPhoneFieldArgs {
	fieldId: number;
	formId: number;
	defaultCountry?: string;
	preferredCountries?: string[];
	countriesAction?: 'all' | 'include' | 'exclude';
	countries?: string[];
	geoIPFallbackCountry?: string;
	geoIPCacheDuration?: number;
	ipInfoAPIToken?: string;
}

interface GPAdvancedPhoneField extends GPAdvancedPhoneFieldArgs {}

class GPAdvancedPhoneField implements GPAdvancedPhoneField {
	public $telInput!: HTMLInputElement;

	public $hiddenInput?: HTMLInputElement;

	public iti!: intlTelInput.Plugin;

	constructor(args: GPAdvancedPhoneFieldArgs) {
		Object.assign(this, args);

		this.init();
		this.bindGPPAListener();
	}

	init = () => {
		this.$telInput = document.querySelector<HTMLInputElement>(
			`#input_${this.formId}_${this.fieldId}`
		)!;

		// Do not double-init
		if ($(this.$telInput).closest('.iti').length) {
			return;
		}

		if (!this.$telInput) {
			return;
		}

		/* Save value POSTed to input, so we can preserve it when navigating multi-page forms. */
		const postedValue = $(this.$telInput).val();

		this.disableMask();

		let initialCountry = this.defaultCountry ?? 'auto';

		// If we have a posted number, set the initial country to US to fix issues with region-less numbers.
		if (postedValue) {
			initialCountry = 'us';
		}

		const intlTelInputOptions: intlTelInput.Options = {
			initialCountry: initialCountry.toLowerCase(),
			geoIpLookup:
				initialCountry === 'auto' ? this.geoIpLookup : undefined,
			preferredCountries: this.preferredCountries ?? [],
			localizedCountries: window.GPAPF?.localizedCountries,
			separateDialCode: true,
			nationalMode: false,
			hiddenInput: 'input_' + this.fieldId, // Generate a hidden input that contains the full number including dialing code.
			utilsScript:
				window.GPAPF.pluginUrl +
				'/js/built/gpapf-intl-tel-input-utils.js',
		};

		if (this.countriesAction === 'exclude') {
			intlTelInputOptions.excludeCountries =
				this.countriesAction === 'exclude' ? this.countries : undefined;
		} else if (this.countriesAction === 'include') {
			intlTelInputOptions.onlyCountries =
				this.countriesAction === 'include' ? this.countries : undefined;
		}

		// Change ID and name for current phone field so it conflicts less with hidden field.
		$(this.$telInput)
			.attr('id', `input_${this.formId}_${this.fieldId}_raw`)
			.attr('name', ``);

		// Update the label to point to the new raw input.
		$(this.$telInput)
			.closest('.gfield')
			.find('label.gfield_label')
			.attr('for', `input_${this.formId}_${this.fieldId}_raw`);

		/**
		 * Filter the options passed to [intl-tel-input](https://intl-tel-input.com/) during initialization.
		 *
		 * @param {intlTelInput.Options} intlTelInputOptions The intlTelInput options. See https://github.com/jackocnr/intl-tel-input#initialisation-options
		 *                                                   for a full list of the allowed options.
		 * @param {number}               formId              The ID of the current form.
		 * @param {number}               fieldId             The ID of the current field.
		 * @param {GPAdvancedPhoneField} instance            The current instance of GPAdvancedPhoneField.
		 *
		 * @since 1.0.12
		 */
		this.iti = intlTelInput(
			this.$telInput,
			window.gform.applyFilters(
				'gpapf_intltelinput_options',
				intlTelInputOptions,
				this.formId,
				this.fieldId,
				this
			)
		);

		// Prevent Gravity Forms Theme Framework from resetting styles on iti.
		// @ts-ignore
		$(this.$telInput)
			.closest('.ginput_container_phone')
			.addClass('gform-theme__no-reset--children');

		// @todo Explore adding RTL support for the Country List. For now, force LTR to avoid RTL weirdness.
		// @ts-ignore
		this.iti.countryList.setAttribute('dir', 'ltr');

		// Add an ID to the hidden field.
		this.$hiddenInput = $(this.$telInput).siblings('[type="hidden"]')[0];

		$(this.$hiddenInput).attr('id', `input_${this.formId}_${this.fieldId}`);

		this.$telInput.addEventListener('keyup', this.formatInputVal);
		this.$telInput.addEventListener('input', this.formatInputVal);

		this.$telInput.addEventListener('keyup', this.updateHiddenInputValue);
		this.$telInput.addEventListener('input', this.updateHiddenInputValue);
		this.$telInput.addEventListener(
			'countrychange',
			this.updateHiddenInputValue
		);

		/* Parent merge tag replacement occurs after Advanced Phone Field has initialized on the frontend. */
		$(this.$hiddenInput)
			.add(this.$telInput)
			.on('gpnfUpdatedFromParentMergeTag', this.formatInputVal);

		/*
		 * Immediately copy value to hidden input to prevent value from being lost on multi-page forms.
		 * We don't use updateHiddenInputValue() as it requires the phone utils and may not be ready yet.
		 */
		if (postedValue) {
			$(this.$hiddenInput).val(postedValue);
		}

		/**
		 * Do something after the phone field is initialized.
		 *
		 * @since 1.0.24
		 *
		 * @param {number}               formId   The ID of the current form.
		 * @param {number}               fieldId  The ID of the current field.
		 * @param {GPAdvancedPhoneField} instance The current instance of GPAdvancedPhoneField.
		 */
		window.gform.doAction(
			'gpapf_post_init',
			this.formId,
			this.fieldId,
			this
		);
	};

	geoIpLookup = (callback: (countryCode: string) => void): void => {
		const fallbackCountry = this.geoIPFallbackCountry ?? 'us';
		const cacheKey = `gpapf_geoip[fallback=${fallbackCountry}]`;
		const cachedCountryCode = localStorage.getItem(cacheKey);

		if (cachedCountryCode) {
			try {
				const { countryCode, expires } = JSON.parse(cachedCountryCode);

				// Check expiry of the value. Delete and disregard the value if it has passed.
				if (expires > Date.now()) {
					callback(countryCode);
					return;
				}
			} catch (e) {
				// Do nothing, we'll delete later.
			}

			localStorage.removeItem(cacheKey);
		}

		const headers: { [header: string]: string } = {};

		const token = this.ipInfoAPIToken;

		if (token) {
			headers.Authorization = `Bearer ${token}`;
		}

		const cacheDuration = this.geoIPCacheDuration ?? 60 * 60 * 24; // 24 hours in seconds

		jQuery.ajax({
			url: 'https://ipinfo.io',
			type: 'GET',
			dataType: 'json',
			headers,
			success(resp) {
				const countryCode = resp?.country ?? fallbackCountry;

				callback(countryCode);
				localStorage.setItem(
					cacheKey,
					JSON.stringify({
						countryCode,
						expires: Date.now() + 1000 * cacheDuration,
					})
				);
			},
			error() {
				callback(fallbackCountry);
			},
		});
	};

	/**
	 * If for some reason the Phone input is masked and not international, remove the mask.
	 */
	disableMask = () => {
		$(this.$telInput).trigger('unmask');
	};

	formatInputVal = () => {
		const currentNumber = this.getFormattedNumber();

		if (currentNumber) {
			// sometimes the currentText is an object
			this.iti.setNumber(currentNumber);
		}
	};

	getFormattedNumber = (): string | undefined => {
		if (typeof intlTelInputUtils === 'undefined') {
			// eslint-disable-next-line no-console
			console.debug('intlTelInputUtils is not loaded.');

			return undefined;
		}

		return this.iti.getNumber(intlTelInputUtils.numberFormat.E164);
	};

	/**
	 * Update hidden input value as it's normally only done on submission. This allows access by plugins such as
	 * Copy Cat and Populate Anything.
	 */
	updateHiddenInputValue = (): void => {
		if (!this.$hiddenInput) {
			return;
		}

		const formattedNumber = this.getFormattedNumber();

		if (formattedNumber) {
			this.$hiddenInput.value = formattedNumber;
			$(this.$hiddenInput).trigger('change');
		}
	};

	/**
	 * Bind a listener to gppa_updated_batch_fields jQuery event that will reinitialize Advanced Phone when
	 * the Phone field markup is replaced.
	 */
	bindGPPAListener = () => {
		$(document).on(
			'gppa_updated_batch_fields',
			(event, formId, updatedFieldIds) => {
				if (parseInt(formId) !== this.formId) {
					return;
				}

				updatedFieldIds = updatedFieldIds.map((fieldId: string) =>
					parseInt(fieldId)
				);

				if (updatedFieldIds.indexOf(this.fieldId) === -1) {
					return;
				}

				this.init();
			}
		);
	};
}

window.GPAdvancedPhoneField = GPAdvancedPhoneField;
