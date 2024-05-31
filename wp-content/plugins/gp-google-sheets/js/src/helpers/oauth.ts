import $ from 'jquery';
// eslint-disable-next-line import/no-unresolved
import { Token } from '../typings/global';
import { GOOGLE_PICKER_DATA_KEY, GOOGLE_TOKEN_KEY } from '../constants';
import { __ } from '@wordpress/i18n';
// eslint-disable-next-line prettier/prettier
import type {
	OAuthResponseData,
	GPGSPluginSettingsStrings,
	GPGSFeedSettingsStrings,
} from '../typings/global';

/**
 * Polls local storage for a specific item.
 *
 * @param {Object}   params
 * @param {number}   params.ms         The number of milliseconds to wait between polls.
 * @param {string}   params.itemKey    The key of the item to poll for.
 * @param {Function} params.handleItem The function to call when the item is found.
 */
export function pollLocalStorage({
	ms = 1000,
	itemKey,
	handleItem,
}: {
	ms?: number;
	itemKey: string;
	handleItem: (item: string) => void;
}) {
	const interval = setInterval(() => {
		const item = localStorage.getItem(itemKey);

		if (item) {
			clearInterval(interval);
			handleItem(item);
		}
	}, ms);
}

/**
 * Helper to create a form with given inputs on the fly and post it in a new tab.
 */
function submitOAuthForm({
	oAuthServiceUrl,
	inputs,
	id = 'gpgs_oauth_form',
}: {
	oAuthServiceUrl: string;
	inputs: [string, string][];
	id?: string;
}) {
	$(
		`<form
			id="${id}"
			action="${oAuthServiceUrl}"
			method="POST"
			target="tab"
		>
			${inputs.map(([name, value]) => {
				const escapedValue = value.replace(/"/g, '&quot;');
				return `<input type="hidden" name="${name}" value="${escapedValue}">`;
			})}
		</form>`
	)
		.appendTo('body')
		.submit();
}

/**
 * Helper to load the Google Picker.
 *
 * @param opts
 * @param opts.token
 * @param opts.showErrorMessage
 * @param opts.onDataFound
 * @param opts.strings
 */
export function showPicker({
	token,
	showErrorMessage,
	onDataFound,
	strings,
}: {
	token: Token;
	showErrorMessage: (message: string) => void;
	onDataFound: (data: OAuthResponseData) => void;
	strings: GPGSPluginSettingsStrings | GPGSFeedSettingsStrings;
}) {
	const randomString = Math.random().toString(36).substring(2, 15);

	const state = {
		oauth_random_string: randomString,
		oauth_validation_token: strings.oauth_validation_token,
		user_id: strings.user_id,
	};

	submitOAuthForm({
		oAuthServiceUrl: `${strings.gwiz_oauth_service_url}/google/file-picker`,
		inputs: [
			['postUrl', `${strings.ajax_url}?action=gpgs_picker_callback`],
			['accessToken', token.access_token],
			['state', JSON.stringify(state)],
		],
		id: 'gpgs-google-sheet-picker',
	});

	const pickerDataLocalStorageKey = `${GOOGLE_PICKER_DATA_KEY}_${randomString}`;
	/**
	 * Start checking local storage for the Google Sheet URL.
	 * This way we know when the user has picked a sheet in the opened picker
	 * tab.
	 */
	pollLocalStorage({
		itemKey: pickerDataLocalStorageKey,
		handleItem: (item) => {
			localStorage.removeItem(pickerDataLocalStorageKey);

			const data: OAuthResponseData = JSON.parse(item);

			if (data.success === '0' || !data.sheet_url) {
				showErrorMessage(
					data.message ||
						__('Error picking spreadsheet.', 'gp-google-sheets')
				);
				return;
			}

			onDataFound(data);
		},
	});
}

export function openOAuthTab({
	strings,
	showErrorMessage,
	onDataFound,
	actionHandlerName,
}: {
	showErrorMessage: (message: string) => void;
	onDataFound: (data: OAuthResponseData) => void;
	strings: GPGSPluginSettingsStrings | GPGSFeedSettingsStrings;
	actionHandlerName: string;
}) {
	const url = `${strings.gwiz_oauth_service_url}/oauth/google`;

	const randomString = Math.random().toString(36).substring(2, 15);

	const state = {
		oauth_random_string: randomString,
		oauth_validation_token: strings.oauth_validation_token,
		user_id: strings.user_id,
	};

	submitOAuthForm({
		oAuthServiceUrl: url,
		inputs: [
			['licenseId', strings.gravity_perks_license_id],
			['licenseHash', strings.gravity_perks_license_hash],
			['redirectUrl', `${strings.ajax_url}?action=${actionHandlerName}`],
			['perkId', strings.slug],
			['siteUrl', strings.site_url],
			['state', JSON.stringify(state)],
		],
	});

	const oauthDataLocalStorageKey = `${GOOGLE_TOKEN_KEY}_${randomString}`;

	/**
	 * Start checking local storage for the Google Token.
	 * This way we know when the user has finished oauthing so that we can
	 * store the token so that it will be saved.
	 */
	pollLocalStorage({
		itemKey: oauthDataLocalStorageKey,
		handleItem: (item) => {
			// remove the item from local storage to prevent it from interfering with future polling.
			localStorage.removeItem(oauthDataLocalStorageKey);

			const data: OAuthResponseData = JSON.parse(item);

			if (data.success === '0' || !data.token) {
				showErrorMessage(data.message);
				return;
			}

			onDataFound(data);
		},
	});
}
