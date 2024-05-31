import getSpreadsheetIdFromUrl from '../../helpers/get-spreadsheet-id-from-url';
import { showPicker } from '../../helpers/oauth';
import { useFeedSettingsStore } from '../../store/feed-settings';
import SettingSelect from '../settings/SettingSelect';
import { __ } from '@wordpress/i18n';

const strings = window.gpgs_settings_strings;

const SettingSpreadsheet = () => {
	const setGoogleSpreadsheetId =
		useFeedSettingsStore.use.setGoogleSpreadsheetId();
	const googleSpreadsheetId = useFeedSettingsStore.use.googleSpreadsheetId();
	const setErrorNotice = useFeedSettingsStore.use.setErrorNotice();
	const googleAccount = useFeedSettingsStore.use.googleAccount();
	const googleAccountToken = useFeedSettingsStore((state) => {
		if (!state.googleAccount) {
			return undefined;
		}

		// Find the Google account in state.googleAccounts with the matching email and get the token.
		return state.googleAccounts?.find(
			(account) => account.googleEmail === state.googleAccount
		)?.token;
	});

	const spreadsheets = useFeedSettingsStore((state) => {
		if (!state.googleAccount) {
			return undefined;
		}

		return state.spreadsheets?.[state.googleAccount];
	});

	const refetchSpreadsheets = useFeedSettingsStore.use.refetchSpreadsheets();

	const onChange = (value: string) => {
		setGoogleSpreadsheetId(value);

		if (value === 'select') {
			showPicker({
				token: googleAccountToken as any,
				showErrorMessage: setErrorNotice,
				// eslint-disable-next-line camelcase
				onDataFound: async ({ sheet_url }) => {
					// eslint-disable-next-line camelcase
					if (!sheet_url) {
						return;
					}

					await refetchSpreadsheets(googleAccount!);
					setGoogleSpreadsheetId(getSpreadsheetIdFromUrl(sheet_url)!);
				},
				strings,
			});
		}
	};

	// Error state
	if (typeof spreadsheets === 'string') {
		return (
			<p className="notice notice-error">
				{__(
					'An error occurred while fetching your spreadsheets. Please try again.',
					'gp-google-sheets'
				)}
			</p>
		);
	}

	return (
		<SettingSelect
			name="google_spreadsheet_id"
			label="Spreadsheet"
			value={googleSpreadsheetId ?? ''}
			onChange={onChange}
			tooltip={__(
				'Select the spreadsheet you would like to store Gravity Forms entries in.'
			)}
			placeholder={
				typeof spreadsheets === 'undefined'
					? __('Loading…', 'gp-google-sheets')
					: ''
			}
			disabled={typeof spreadsheets === 'undefined'}
			options={[
				{
					text: __('Actions', 'gp-google-sheets'),
					options: [
						{
							text: __('Add New Spreadsheet', 'gp-google-sheets'),
							value: 'add',
						},
						{
							text: __(
								'Connect Existing Spreadsheet',
								'gp-google-sheets'
							),
							value: 'select',
						},
					],
				},
				{
					text: __('Connected Spreadsheets', 'gp-google-sheets'),
					options:
						typeof spreadsheets === 'object'
							? spreadsheets.map((spreadsheet) => ({
									text: spreadsheet.name,
									value: spreadsheet.id,
							  }))
							: [],
				},
			]}
		/>
	);
};

export default SettingSpreadsheet;
