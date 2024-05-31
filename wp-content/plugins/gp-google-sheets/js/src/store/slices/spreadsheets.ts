import { StateCreator } from 'zustand';

let strings = window.gpgs_settings_strings;

if (!strings) {
	strings = window.gpgs_settings_plugin_strings;
}

interface SpreadsheetsResponse {
	success: boolean;
	data: {
		spreadsheets: Spreadsheet[];
		message?: string;
	};
}

export interface Spreadsheet {
	name: string;
	webViewLink: string;
	id: string;
}

type SheetsResponse =
	| {
			success: false;
			data: {
				message: string;
			};
	  }
	| {
			success: true;
			data: {
				sheets: Sheet[];
			};
	  };

export interface Sheet {
	id: string;
	title: string;
}

export interface SpreadsheetsSlice {
	spreadsheets: { [accountId: string]: Spreadsheet[] | string | undefined };
	sheets: { [spreadsheetId: string]: Sheet[] | string | undefined };
	fetchSpreadsheets: (email: string) => Promise<void>;
	refetchSpreadsheets: (email: string) => Promise<void>;
	fetchSheets: (spreadsheetId: string) => Promise<void>;
}

export const createSpreadsheetsSlice: StateCreator<SpreadsheetsSlice> = (
	set,
	get
) => ({
	spreadsheets: {},
	sheets: {},
	fetchSpreadsheets: async (email: string) => {
		const resp: SpreadsheetsResponse = await jQuery.get(strings.ajax_url, {
			action: 'gpgs_get_spreadsheets',
			_ajax_nonce: strings.nonce,
			google_email: email,
		});

		if (!resp.success) {
			set({
				spreadsheets: {
					...get().spreadsheets,
					[email]: resp.data.message as string,
				},
			});

			return;
		}

		set({
			spreadsheets: {
				...get().spreadsheets,
				[email]: resp.data.spreadsheets,
			},
		});
	},
	refetchSpreadsheets: async (email: string) => {
		// Clear out the spreadsheets for this email before refetching.
		set({
			spreadsheets: {
				...get().spreadsheets,
				[email]: undefined,
			},
		});

		await get().fetchSpreadsheets(email);
	},
	fetchSheets: async (spreadsheetId: string) => {
		const resp: SheetsResponse = await jQuery.post(strings.ajax_url, {
			action: 'gpgs_get_sheets',
			_ajax_nonce: strings.nonce,
			spreadsheet_id: spreadsheetId,
		});

		if (!resp.success) {
			set({
				sheets: {
					...get().sheets,
					[spreadsheetId]: resp.data.message as string,
				},
			});

			return;
		}

		set({
			sheets: {
				...get().sheets,
				[spreadsheetId]: resp.data.sheets,
			},
		});
	},
});
