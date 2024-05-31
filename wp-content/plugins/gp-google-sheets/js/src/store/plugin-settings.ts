import { create } from 'zustand';
import { NoticesSlice, createNoticesSlice } from './slices/notices';
import {
	SpreadsheetsSlice,
	createSpreadsheetsSlice,
} from './slices/spreadsheets';
import {
	IssuesSlice,
	createIssuesSlice,
} from './slices/plugin-settings/issues';

import {
	GoogleAccountsHealthSlice,
	createGoogleAccountsHealthSlice,
} from './slices/plugin-settings/google-accounts-health';
import {
	GoogleAccountsSlice,
	createGoogleAccountsSlice,
} from './slices/google-accounts';
import { createSelectors } from './helpers/selectors';

const usePluginSettingsStoreBase = create<
	GoogleAccountsHealthSlice &
		GoogleAccountsSlice &
		NoticesSlice &
		SpreadsheetsSlice &
		IssuesSlice
>((...a) => ({
	...createGoogleAccountsHealthSlice(...a),
	...createGoogleAccountsSlice(...a),
	...createNoticesSlice(...a),
	...createSpreadsheetsSlice(...a),
	...createIssuesSlice(...a),
}));

export const usePluginSettingsStore = createSelectors(
	usePluginSettingsStoreBase
);
