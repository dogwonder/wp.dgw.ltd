import { Fragment, useEffect } from 'react';
import { __ } from '@wordpress/i18n';
import SettingSelect from '../settings/SettingSelect';
import ConnectedCard from './ConnectedCard';
import Notices from '../Notices';
import { useFeedSettingsStore } from '../../store/feed-settings';
import {
	disableColumnMappingSection,
	enableColumnMappingSection,
	updateColumnMappingSection,
} from '../../helpers/feed-sections';
import SettingSpreadsheet from '../settings/SettingSpreadsheet';
import SettingGoogleAccount from '../settings/SettingGoogleAccount';

const GoogleSheetSettings = () => {
	const googleAccount = useFeedSettingsStore.use.googleAccount();
	const notices = useFeedSettingsStore.use.notices();
	const setErrorNotice = useFeedSettingsStore.use.setErrorNotice();
	const sheetUrl = useFeedSettingsStore.use.googleSheetUrl();
	const googleSpreadsheetId = useFeedSettingsStore.use.googleSpreadsheetId();
	const googleSheetId = useFeedSettingsStore.use.googleSheetId();
	const setGoogleSheetId = useFeedSettingsStore.use.setGoogleSheetId();
	const connected = useFeedSettingsStore.use.connected();
	const googleErrorMessage = useFeedSettingsStore.use.googleErrorMessage();

	const sheets = useFeedSettingsStore((state) => {
		if (!state.googleSpreadsheetId) {
			return undefined;
		}

		return state.sheets?.[state.googleSpreadsheetId];
	});

	useEffect(() => {
		if (
			(typeof googleSpreadsheetId === 'undefined' ||
				googleSpreadsheetId === 'add') &&
			googleAccount
		) {
			enableColumnMappingSection();
		} else if (googleSpreadsheetId) {
			disableColumnMappingSection();

			if (
				googleSpreadsheetId === 'select' ||
				googleSheetId === '' ||
				googleSheetId === undefined ||
				typeof sheets === 'string' ||
				!sheetUrl
			) {
				return;
			}

			(async () => {
				try {
					await updateColumnMappingSection(
						'from_current_feed_mappings',
						{
							sheetId: googleSheetId,
							sheetName: sheets?.find(
								(sheet: any) => sheet.id === googleSheetId
							)?.title!,
							sheetUrl,
						}
					);
				} catch (error: any) {
					setErrorNotice(
						error.message || 'Failed to get column mappings.'
					);
				}
			})();
		} else if (!sheetUrl || googleErrorMessage) {
			disableColumnMappingSection();
		}
	}, [
		sheetUrl,
		googleSpreadsheetId,
		googleSheetId,
		googleAccount,
		sheets,
		googleErrorMessage,
		setErrorNotice,
	]);

	// Sync updates to the hidden inputs for Gravity Forms.
	useEffect(() => {
		const $sheetUrl: HTMLInputElement = document.querySelector(
			'input#google_sheet_url'
		)!;

		const $sheetId: HTMLInputElement = document.querySelector(
			'input#google_sheet_id'
		)!;

		$sheetUrl.value = sheetUrl ?? '';
		$sheetId.value = googleSheetId ?? '';
	}, [sheetUrl, googleSheetId]);

	// If already connected, show the connected card.
	if (connected) {
		return (
			<Fragment>
				<Notices notices={notices} />
				<ConnectedCard />
			</Fragment>
		);
	}

	return (
		<Fragment>
			<Notices notices={notices} />
			<SettingGoogleAccount />
			{googleAccount && (
				<Fragment>
					<SettingSpreadsheet />
					{googleSpreadsheetId &&
						googleSpreadsheetId !== 'select' &&
						googleSpreadsheetId !== 'add' && (
							<a
								href={sheetUrl}
								target="_blank"
								rel="noreferrer"
								style={{
									marginBottom: 10,
									display: 'inline-block',
								}}
							>
								{__('View Spreadsheet', 'gp-google-sheets')}
							</a>
						)}
					{googleSpreadsheetId &&
						googleSpreadsheetId !== 'select' &&
						googleSpreadsheetId !== 'add' && (
							<SettingSelect
								name="google_sheet_id_selector"
								label="Sheet"
								value={googleSheetId ?? ''}
								onChange={setGoogleSheetId}
								tooltip={__(
									'Select the specific sheet in the spreadsheet you would like to store Gravity Forms entries in.'
								)}
								disabled={typeof sheets === 'undefined'}
								placeholder={
									typeof sheets === 'undefined'
										? __('Loading…', 'gp-google-sheets')
										: __(
												'Select a Sheet',
												'gp-google-sheets'
										  )
								}
								options={
									typeof sheets === 'object'
										? sheets.map((sheet) => ({
												text: sheet.title,
												value: sheet.id,
										  }))
										: []
								}
							/>
						)}
				</Fragment>
			)}
		</Fragment>
	);
};

export default GoogleSheetSettings;
