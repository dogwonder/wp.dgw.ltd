import { useEffect } from 'react';
import SettingsSection from '../SettingsSection';
import { __ } from '@wordpress/i18n';
import GoogleAccountRow from './GoogleAccountRow';
import AddGoogleAccountButton from './AddGoogleAccountButton';
import { usePluginSettingsStore } from '../../store/plugin-settings';

const SectionGoogleAccountsHealth = () => {
	const fetchTokens = usePluginSettingsStore.use.fetchTokens();
	const tokens = usePluginSettingsStore.use.tokens();
	const tokensError = usePluginSettingsStore.use.tokensError();

	useEffect(() => {
		fetchTokens();
	}, [fetchTokens]);

	if (tokensError) {
		return (
			<SettingsSection
				title={__('Google Accounts', 'gp-google-sheets')}
				id="gpgs-token-health"
			>
				<div className="error">{tokensError}</div>
			</SettingsSection>
		);
	}

	if (tokens === undefined) {
		return (
			<SettingsSection
				title={__('Google Accounts', 'gp-google-sheets')}
				id="gpgs-token-health"
			>
				{__('Loading…', 'gp-google-sheets')}
			</SettingsSection>
		);
	}

	const tokensTable = (
		<table className="gform-table gform-table--responsive gform-table--no-outer-border gform-table--token-health">
			<thead>
				<tr>
					<th></th>
					<th>{__('User', 'gp-google-sheets')}</th>
					<th>{__('Google Email', 'gp-google-sheets')}</th>
					<th>{__('Feeds', 'gp-google-sheets')}</th>
					<th>{__('Spreadsheets', 'gp-google-sheets')}</th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				{tokens.map((token) => (
					<GoogleAccountRow
						googleAccount={token}
						key={`${token.googleEmail}-${token.userEditLink}`}
					/>
				))}
			</tbody>
		</table>
	);

	return (
		<SettingsSection
			title={__('Google Accounts', 'gp-google-sheets')}
			id="gpgs-token-health"
		>
			{tokensTable}

			<p>
				<AddGoogleAccountButton />
			</p>
		</SettingsSection>
	);
};

export default SectionGoogleAccountsHealth;
