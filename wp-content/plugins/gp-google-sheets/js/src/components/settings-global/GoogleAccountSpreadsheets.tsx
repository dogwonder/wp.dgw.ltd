import { useEffect } from 'react';
import AddSpreadsheetButton from './AddSpreadsheetButton';
import { __ } from '@wordpress/i18n';
import { usePluginSettingsStore } from '../../store/plugin-settings';
import { GoogleAccountHealth } from '../../store/slices/plugin-settings/google-accounts-health';

const { gpgs_settings_plugin_strings: strings } = window;

const GoogleAccountSpreadsheets = ({
	googleAccount,
	visible,
}: {
	googleAccount: GoogleAccountHealth;
	visible: Boolean;
}) => {
	const spreadsheets = usePluginSettingsStore(
		(state) => state.spreadsheets?.[googleAccount.googleEmail]
	);

	const rescanIssues = usePluginSettingsStore.use.rescanIssues();
	const refetchSpreadsheets =
		usePluginSettingsStore.use.refetchSpreadsheets();
	const setErrorNotice = usePluginSettingsStore.use.setErrorNotice();
	const setSuccessNotice = usePluginSettingsStore.use.setSuccessNotice();

	const fetchSpreadsheets = usePluginSettingsStore.use.fetchSpreadsheets();

	useEffect(() => {
		if (!visible || typeof spreadsheets !== 'undefined') {
			return;
		}

		fetchSpreadsheets(googleAccount.googleEmail);
	}, [fetchSpreadsheets, googleAccount.googleEmail, visible, spreadsheets]);

	let spreadsheetsContent;

	if (typeof spreadsheets === 'undefined') {
		spreadsheetsContent = (
			<p style={{ marginTop: '1rem' }}>
				{__('Loading spreadsheets…', 'gp-google-sheets')}
			</p>
		);
	} else if (spreadsheets.length === 0) {
		spreadsheetsContent = (
			<p style={{ marginTop: '1rem' }}>
				{__('No spreadsheets found.', 'gp-google-sheets')}
			</p>
		);
	} else if (typeof spreadsheets === 'string') {
		// Error state
		spreadsheetsContent = (
			<p style={{ margin: '1rem 0' }}>{spreadsheets}</p>
		);
	} else {
		spreadsheetsContent = (
			<ul style={{ marginTop: '1rem' }}>
				{spreadsheets.map((spreadsheet) => (
					<li key={spreadsheet.webViewLink}>
						<a
							href={spreadsheet.webViewLink}
							target="_blank"
							rel="noreferrer"
						>
							<span
								className="dashicons dashicons-media-spreadsheet"
								style={{
									textDecoration: 'none',
									marginRight: '.25rem',
								}}
							></span>
							{spreadsheet.name}
						</a>
					</li>
				))}
			</ul>
		);
	}

	return (
		<tr
			className="gpgs-token-spreadsheets gpgs_border_top"
			style={{ display: visible ? 'table-row' : 'none' }}
		>
			<td colSpan={6} className="gpgs_light_grey_background">
				<p>
					{__(
						'The following spreadsheets are available when selecting this account for use in feeds or in fields being populated by Populate Anything using the Google Sheets Object Type.'
					)}
				</p>
				{spreadsheetsContent}
				{
					//We'll allow them to use the picker even if they don't have a license but they do have a token.
				}
				{googleAccount.isLegacyToken && (
					<p style={{ margin: '1rem 0' }}>
						{__(
							'Additional spreadsheets cannot be added to Google Accounts connected using a legacy token.'
						)}
					</p>
				)}
				{!googleAccount.isLegacyToken && (
					<AddSpreadsheetButton
						onError={setErrorNotice}
						onSelection={() => {
							setSuccessNotice(
								__('Spreadsheet added.', 'gp-google-sheets')
							);

							refetchSpreadsheets(googleAccount.googleEmail);
							rescanIssues();
						}}
						token={googleAccount.token as any}
						doesNotHaveLicense={
							!googleAccount.tokenIsHealthy &&
							!strings.gravity_perks_license_id
						}
					/>
				)}
			</td>
		</tr>
	);
};

export default GoogleAccountSpreadsheets;
