import { StateCreator } from 'zustand';
import { NoticesSlice } from '../notices';
import { __, sprintf } from '@wordpress/i18n';
import { IssuesSlice } from './issues';
import { GoogleAccount } from '../google-accounts';
import { openOAuthTab } from '../../../helpers/oauth';

const { gpgs_settings_plugin_strings: strings } = window;

export interface GoogleAccountHealth extends GoogleAccount {
	connectedFeeds: Array<{
		feed_id: number;
		spreadsheet_id: string;
		form_title: string;
		feed_name: string;
		form_id: number;
		feed_url: string;
	}>;
}

export interface GoogleAccountHealthResponse {
	success: boolean;
	data: {
		token_data: GoogleAccountHealth[];
		message?: string;
	};
}

export interface DeleteGoogleAccountResponse {
	success: boolean;
	data: {
		message?: string;
	};
}

export interface GoogleAccountsHealthSlice {
	tokens: undefined | GoogleAccountHealth[];
	tokensError: null | string;
	fetchTokens: () => Promise<void>;
	refetchTokens: () => Promise<void>;
	deleteGoogleAccount: (accountId: string) => Promise<void>;
	reconnectAccount: () => void;
}

export const createGoogleAccountsHealthSlice: StateCreator<
	GoogleAccountsHealthSlice & NoticesSlice & IssuesSlice,
	[],
	[],
	GoogleAccountsHealthSlice
> = (set, get) => ({
	tokens: undefined,
	tokensError: null,
	fetchTokens: async () => {
		try {
			const resp: GoogleAccountHealthResponse = await jQuery.get(
				strings.ajax_url,
				{
					action: 'gpgs_google_accounts',
					_ajax_nonce: strings.nonce,
				}
			);

			if (!resp.success) {
				set({
					tokensError: resp.data.message,
					tokens: undefined,
				});

				return;
			}

			const tokens = resp.data.token_data;

			tokens.sort((a, b) => {
				if (a.userDisplayName === null) {
					return 1;
				}

				if (b.userDisplayName === null) {
					return -1;
				}

				return 0;
			});

			set({
				tokens,
				tokensError: null,
			});
		} catch (e: any) {
			if (e.status >= 400) {
				set({
					tokensError: sprintf(
						// translators: %s is the error message.
						__(
							'An error occurred while listing accounts. Please try again. If the problem persists, please contact support. Error: %s',
							'gp-google-sheets'
						),
						e.statusText
					),
					tokens: undefined,
				});

				return;
			}

			set({
				tokensError: __(
					'An unknown error occurred while listing accounts. Please try again. If the problem persists, please contact support.',
					'gp-google-sheets'
				),
				tokens: undefined,
			});
		}
	},
	refetchTokens: async () => {
		set({
			tokens: undefined,
			tokensError: null,
		});

		await get().fetchTokens();
	},
	deleteGoogleAccount: async (accountId: string) => {
		const unknownError = __(
			'An unknown error occurred while removing the account from GP Google Sheets. Please try again. If the problem persists, please contact support.',
			'gp-google-sheets'
		);

		try {
			const resp: DeleteGoogleAccountResponse = await jQuery.post(
				strings.ajax_url,
				{
					action: 'gpgs_delete_google_account',
					_ajax_nonce: strings.nonce,
					account_id: accountId,
				}
			);

			if (!resp.success) {
				get().setErrorNotice(resp.data.message ?? unknownError);

				return;
			}

			get().setSuccessNotice(
				__(
					'Successfully removed Google Account from GP Google Sheets.',
					'gp-google-sheets'
				)
			);

			get().refetchTokens();
			get().rescanIssues();
		} catch (e: any) {
			if (e.status >= 400) {
				get().setErrorNotice(
					sprintf(
						// translators: %s is the error message.
						__(
							'An error occurred while removing the account from GP Google Sheets. Please try again. If the problem persists, please contact support. Error: %s',
							'gp-google-sheets'
						),
						e.statusText
					)
				);

				return;
			}

			get().setErrorNotice(unknownError);
		}
	},
	reconnectAccount: () => {
		openOAuthTab({
			showErrorMessage: get().setErrorNotice,
			strings,
			onDataFound: ({ token }) => {
				if (!token) {
					get().setErrorNotice(
						__(
							'Failed to get token for Google Account. Please try again.',
							'gp-google-sheets'
						)
					);

					return;
				}

				get().setSuccessNotice(
					__(
						'Successfully reconnected to Google Sheets!',
						'gp-google-sheets'
					)
				);

				get().refetchTokens();
				get().rescanIssues();
			},
			actionHandlerName: 'gpgs_plugin_settings_oauth_callback',
		});
	},
});
