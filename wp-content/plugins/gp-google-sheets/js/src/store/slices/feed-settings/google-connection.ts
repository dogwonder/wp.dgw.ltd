import { StateCreator } from 'zustand';
import { SpreadsheetsSlice } from '../spreadsheets';
import { NoticesSlice } from '../notices';
import { __, sprintf } from '@wordpress/i18n';
const { gpgs_settings_strings: strings } = window;

export interface GoogleConnectionSlice {
	isDisconnecting: boolean;
	connected: boolean;
	googleAccount: string | undefined;
	googleSheetUrl: string | undefined;
	googleSpreadsheetId: string | undefined;
	googleSheetId: string | undefined;
	googleSpreadsheetName: string | undefined;
	googleSheetName: string | undefined;
	googleErrorMessage: string;
	disconnectFeed: () => void;
	setGoogleAccount: (account: string) => void;
	setGoogleSpreadsheetId: (id: string) => void;
	setGoogleSheetId: (id: string) => void;
}

interface DisconnectResponse {
	success: boolean;
}

export const createGoogleConnectionSlice: StateCreator<
	GoogleConnectionSlice & SpreadsheetsSlice & NoticesSlice,
	[],
	[],
	GoogleConnectionSlice
> = (set, get) => ({
	isDisconnecting: false,
	googleAccount: undefined,
	connected: !!(
		document.querySelector('input#google_sheet_url') as HTMLInputElement
	)?.value,
	googleSheetUrl: (
		document.querySelector('input#google_sheet_url') as HTMLInputElement
	)?.value,
	googleSpreadsheetId: undefined,
	googleSheetId: (
		document.querySelector('input#google_sheet_id') as HTMLInputElement
	)?.value,
	googleSpreadsheetName: strings.spreadsheet_name,
	googleSheetName: strings.sheet_name,
	googleErrorMessage: strings.error_message,
	disconnectFeed: async () => {
		if (get().isDisconnecting) {
			return;
		}

		get().clearNotices();

		set({
			isDisconnecting: true,
		});

		const errorMessage = __(
			'An error occurred while disconnecting this feed.',
			'gp-google-sheets'
		);

		try {
			const resp: DisconnectResponse = await jQuery.post(
				strings.ajax_url,
				{
					action: 'gpgs_disconnect',
					_ajax_nonce: strings.nonce,
					form_id: strings.form_id,
					feed_id: strings.feed_id,
				}
			);

			if (!resp.success) {
				get().setErrorNotice(errorMessage);

				return;
			}

			set({
				googleSheetUrl: undefined,
				connected: false,
				googleSpreadsheetId: undefined,
				googleSheetId: undefined,
				googleSpreadsheetName: undefined,
				googleSheetName: undefined,
			});

			get().setSuccessNotice(
				__('Successfully disconnected feed.', 'gp-google-sheets')
			);
		} catch (e: any) {
			if (e.status >= 400) {
				get().setErrorNotice(
					sprintf(
						// translators: %s is the error message.
						__(
							'An error occurred while disconnecting this feed. Please try again. If the problem persists, please contact support. Error: %s',
							'gp-google-sheets'
						),
						e.statusText
					)
				);

				return;
			}

			get().setErrorNotice(errorMessage);
		} finally {
			set({
				isDisconnecting: false,
			});
		}
	},
	setGoogleAccount: (account: string) => {
		set({
			googleAccount: account,
			googleSpreadsheetId: undefined,
			googleSheetId: undefined,
		});

		get().fetchSpreadsheets(account);
	},
	setGoogleSpreadsheetId: async (id: string) => {
		set({
			googleSpreadsheetId: id,
			googleSheetId: undefined,
		});

		if (!id || id === 'add' || id === 'select') {
			return;
		}

		await get().fetchSheets(id);

		const sheets = get().sheets[id];

		if (!sheets) {
			return;
		}

		// If a Sheet1 exists, select it.
		const sheet1 = Object.values(sheets).find(
			(sheet) => sheet.title === 'Sheet1'
		);

		if (sheet1) {
			set({ googleSheetId: sheet1.id });
		}

		// Set the URL
		const googleAccount = get().googleAccount;

		if (!googleAccount) {
			return;
		}

		const spreadsheets = get().spreadsheets?.[googleAccount];

		if (!spreadsheets || typeof spreadsheets === 'string') {
			return;
		}

		set({
			googleSheetUrl: spreadsheets.find(
				(spreadsheet: any) => spreadsheet.id === id
			)?.webViewLink,
		});
	},
	setGoogleSheetId: (id: string) => {
		set({ googleSheetId: id });
	},
});
