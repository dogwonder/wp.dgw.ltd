import { StateCreator } from 'zustand';
import { NoticesSlice } from '../notices';
import { __, sprintf } from '@wordpress/i18n';

const { gpgs_settings_strings: strings } = window;

export interface TestRowsSlice {
	isInsertingTestRow: boolean;
	insertTestRow: () => Promise<void>;
}

interface InsertRowsResponse {
	success: boolean;
	data?: {
		message?: string;
	};
}

export const createTestRowsSlice: StateCreator<
	TestRowsSlice & NoticesSlice,
	[],
	[],
	TestRowsSlice
> = (set, get) => ({
	isInsertingTestRow: false,
	async insertTestRow() {
		if (get().isInsertingTestRow) {
			return;
		}

		get().clearNotices();

		set({
			isInsertingTestRow: true,
		});

		try {
			const resp: InsertRowsResponse = await jQuery.post(
				strings.ajax_url,
				{
					action: 'gpgs_insert_test_row',
					_ajax_nonce: strings.nonce,
					form_id: strings.form_id,
					feed_id: strings.feed_id,
				}
			);

			if (!resp.success) {
				get().setErrorNotice(
					resp.data?.message ??
						__(
							'An unknown error occurred while inserting a test row.',
							'gp-google-sheets'
						)
				);

				return;
			}

			get().setSuccessNotice(
				__('Successfully inserted a test row.', 'gp-google-sheets')
			);
		} catch (insertRowsError: any) {
			if (insertRowsError.status >= 400) {
				get().setErrorNotice(
					sprintf(
						// translators: %s is the error message.
						__(
							'An error occurred while adding a test row. Please try again. If the problem persists, please contact support. Error: %s',
							'gp-google-sheets'
						),
						insertRowsError.statusText
					)
				);

				return;
			}

			get().setErrorNotice(
				__(
					'An unknown error occurred while scanning for issues. Please try again. If the problem persists, please contact support.',
					'gp-google-sheets'
				)
			);
		} finally {
			set({
				isInsertingTestRow: false,
			});
		}
	},
});
