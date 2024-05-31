import { useFeedSettingsStore } from '../../store/feed-settings';
import { useEffect } from 'react';
import SettingSelect from './SettingSelect';
import { __ } from '@wordpress/i18n';

const SettingGoogleAccount = () => {
	const googleAccount = useFeedSettingsStore.use.googleAccount();
	const googleAccounts = useFeedSettingsStore((state) => {
		// Filter out legacy tokens as the Picker will not work with them among other things and we're not going to be supporting them.
		return state.googleAccounts?.filter((account) => {
			return !account.isLegacyToken;
		});
	});
	const fetchGoogleAccounts = useFeedSettingsStore.use.fetchGoogleAccounts();
	const setGoogleAccount = useFeedSettingsStore.use.setGoogleAccount();
	const addGoogleAccount = useFeedSettingsStore.use.addGoogleAccount();

	const onChange = async (value: string) => {
		if (value === 'add') {
			const token = await addGoogleAccount();

			await fetchGoogleAccounts();

			const googleAccountsRefreshed =
				useFeedSettingsStore.getState().googleAccounts;

			// Find the Google account with the matching token and set it as the selected account.
			const accountToSelect = googleAccountsRefreshed?.find(
				(account) => account.token.access_token === token.access_token
			);

			if (accountToSelect) {
				setGoogleAccount(accountToSelect.googleEmail);
			}

			return;
		}

		setGoogleAccount(value);
	};

	useEffect(() => {
		fetchGoogleAccounts();
	}, [fetchGoogleAccounts]);

	return (
		<SettingSelect
			name="google_sheet_account"
			label="Account"
			value={googleAccount ?? ''}
			onChange={onChange}
			tooltip={__(
				'The Google account to create a new sheet under or to select an existing sheet from.'
			)}
			disabled={typeof googleAccounts === 'undefined'}
			placeholder={
				typeof googleAccounts === 'undefined'
					? __('Loading…', 'gp-google-sheets')
					: __('Select an Account', 'gp-google-sheets')
			}
			options={[
				{
					text: __('Actions', 'gp-google-sheets'),
					options: [
						{
							text: __(
								'Authorize New Account',
								'gp-google-sheets'
							),
							value: 'add',
						},
					],
				},
				{
					text: __('Authorized Accounts', 'gp-google-sheets'),
					options:
						googleAccounts?.map((account) => ({
							text: account.googleEmail,
							value: account.googleEmail,
						})) ?? [],
				},
			]}
		/>
	);
};

export default SettingGoogleAccount;
