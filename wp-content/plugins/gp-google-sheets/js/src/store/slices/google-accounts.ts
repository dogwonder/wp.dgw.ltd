import { StateCreator } from 'zustand';
import { NoticesSlice } from './notices';
import { openOAuthTab } from '../../helpers/oauth';
import { __ } from '@wordpress/i18n';

let strings = window.gpgs_settings_strings;

if (!strings) {
	strings = window.gpgs_settings_plugin_strings;
}

export interface GoogleAccount {
	tokenIsHealthy: boolean;
	accountId: string;
	googleEmail: string;
	userId: number;
	userDisplayName: string;
	userEditLink: string;
	isLegacyToken: boolean;
	token: {
		access_token: string;
		refresh_token: string;
		token_type: string;
		scope: string;
		expiry_date?: number;
		gwiz_oauth?: boolean;
	};
}

export interface GoogleAccountsSlice {
	googleAccounts: GoogleAccount[] | undefined;
	fetchGoogleAccounts: () => Promise<void>;
	addGoogleAccount: () => Promise<GoogleAccount['token']>;
}

interface GoogleAccountsResponse {
	success: boolean;
	data: {
		google_accounts: GoogleAccount[];
		message?: string;
	};
}

export const createGoogleAccountsSlice: StateCreator<
	NoticesSlice & GoogleAccountsSlice,
	[],
	[],
	GoogleAccountsSlice
> = (set, get) => ({
	googleAccounts: undefined,
	fetchGoogleAccounts: async () => {
		try {
			const resp: GoogleAccountsResponse = await jQuery.get(
				strings.ajax_url,
				{
					action: 'gpgs_get_google_accounts',
					_ajax_nonce: strings.nonce,
				}
			);

			if (!resp.success) {
				set({ googleAccounts: [] });

				return;
			}

			set({
				googleAccounts: resp.data.google_accounts,
			});
		} catch (e: any) {
			if (e.status >= 400) {
				set({ googleAccounts: [] });
			}
		}
	},
	addGoogleAccount: () => {
		return new Promise((resolve, reject) => {
			openOAuthTab({
				showErrorMessage: (message) => {
					get().setErrorNotice(message);
					reject(new Error(message));
				},
				strings,
				onDataFound: ({ token }) => {
					if (!token) {
						get().setErrorNotice(
							__(
								'Failed to get token. Please try again.',
								'gp-google-sheets'
							)
						);

						return;
					}

					get().setSuccessNotice(
						__(
							'Successfully added new Google Account!',
							'gp-google-sheets'
						)
					);

					resolve(token);
				},
				actionHandlerName: 'gpgs_plugin_settings_oauth_callback',
			});
		});
	},
});
