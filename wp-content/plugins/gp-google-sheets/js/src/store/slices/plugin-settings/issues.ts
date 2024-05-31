const { gpgs_settings_plugin_strings: strings } = window;
import { __, sprintf } from '@wordpress/i18n';
import { StateCreator } from 'zustand';

interface Issue {
	type: 'feed' | 'gppa_object_type';
	code: string;
	spreadsheetUrl: string;
}

interface IssueFeed extends Issue {
	formUrl: string;
	formTitle: string;
	feedName: string;
	feedUrl: string;
}

interface IssueField extends Issue {
	formUrl: string;
	formTitle: string;
	fieldId: number;
	fieldLabel: string;
	populate: 'values' | 'choices';
}

interface IssuesResponse {
	success: boolean;
	data: {
		issues: (IssueFeed | IssueField)[];
		message?: string;
	};
}

export interface IssuesSlice {
	issues: (IssueFeed | IssueField)[] | undefined;
	issuesError: string | undefined;
	scanIssues: () => Promise<void>;
	rescanIssues: () => void;
}

export const createIssuesSlice: StateCreator<IssuesSlice> = (set, get) => ({
	issues: undefined,
	issuesError: undefined,
	scanIssues: async () => {
		try {
			const resp: IssuesResponse = await jQuery.get(strings.ajax_url, {
				action: 'gpgs_scan_issues',
				_ajax_nonce: strings.nonce,
			});

			if (!resp.success) {
				set({ issuesError: resp.data.message, issues: [] });

				return;
			}

			set({ issuesError: undefined, issues: resp.data.issues });
		} catch (e: any) {
			if (e.status >= 400) {
				set({
					issuesError: sprintf(
						// translators: %s is the error message.
						__(
							'An error occurred while scanning for issues. Please try again. If the problem persists, please contact support. Error: %s',
							'gp-google-sheets'
						),
						e.statusText
					),

					issues: [],
				});

				return;
			}

			set({
				issuesError: __(
					'An unknown error occurred while scanning for issues. Please try again. If the problem persists, please contact support.',
					'gp-google-sheets'
				),
				issues: [],
			});
		}
	},
	rescanIssues: async () => {
		set({ issues: undefined, issuesError: undefined });
		await get().scanIssues();
	},
});
