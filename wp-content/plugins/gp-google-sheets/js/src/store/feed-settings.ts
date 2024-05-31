import { create } from 'zustand';
import { NoticesSlice, createNoticesSlice } from './slices/notices';
import {
	GoogleConnectionSlice,
	createGoogleConnectionSlice,
} from './slices/feed-settings/google-connection';
import {
	SpreadsheetsSlice,
	createSpreadsheetsSlice,
} from './slices/spreadsheets';
import {
	GoogleAccountsSlice,
	createGoogleAccountsSlice,
} from './slices/google-accounts';
import { createSelectors } from './helpers/selectors';
import {
	TestRowsSlice,
	createTestRowsSlice,
} from './slices/feed-settings/test-rows';

const useFeedSettingsStoreBase = create<
	NoticesSlice &
		GoogleAccountsSlice &
		GoogleConnectionSlice &
		SpreadsheetsSlice &
		TestRowsSlice
>((...a) => ({
	...createNoticesSlice(...a),
	...createGoogleConnectionSlice(...a),
	...createGoogleAccountsSlice(...a),
	...createSpreadsheetsSlice(...a),
	...createTestRowsSlice(...a),
}));

export const useFeedSettingsStore = createSelectors(useFeedSettingsStoreBase);
