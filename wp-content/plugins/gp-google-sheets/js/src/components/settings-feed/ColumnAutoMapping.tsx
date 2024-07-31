import { useFeedSettingsStore } from '../../store/feed-settings';
import {
	updateColumnMappingSection,
	disableColumnMappingSection,
} from '../../helpers/feed-sections';

function ActionSection({
	title,
	description,
	buttonText,
	handleUpdateColumnMappingSection,
}: {
	title: string;
	description: string;
	buttonText: string;
	handleUpdateColumnMappingSection: () => void;
}) {
	return (
		<div
			className="alert gforms_note_warning"
			role="alert"
			style={{ display: 'flex' }}
		>
			<p style={{ flex: 2 }}>
				<b>{title}</b>
				<br />
				{description}
			</p>
			<button
				className="button secondary"
				onClick={(event) => {
					event.preventDefault();
					disableColumnMappingSection();
					handleUpdateColumnMappingSection();
				}}
			>
				{buttonText}
			</button>
		</div>
	);
}

function ColumnAutoMapping() {
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const googleSheetId = useFeedSettingsStore.use.googleSheetId();
	const setErrorNotice = useFeedSettingsStore.use.setErrorNotice();
	const sheets = useFeedSettingsStore((state) => {
		if (!state.googleSpreadsheetId) {
			return undefined;
		}

		return state.sheets?.[state.googleSpreadsheetId];
	});
	// eslint-disable-next-line @wordpress/no-unused-vars-before-return
	const sheetUrl = useFeedSettingsStore.use.googleSheetUrl();

	if (
		googleSheetId !== undefined &&
		sheetUrl &&
		Array.isArray(sheets) &&
		sheets.length > 0
	) {
		return (
			<ActionSection
				title="Existing spreadsheet?"
				description="Use this option to automatically map each column to the nearest matching field."
				buttonText="Map All Columns"
				handleUpdateColumnMappingSection={async () => {
					try {
						await updateColumnMappingSection('from_sheet_columns', {
							sheetId: googleSheetId,
							sheetName: sheets?.find(
								(sheet: any) => sheet.id === googleSheetId
							)?.title!,
							sheetUrl,
						});
					} catch (error: any) {
						setErrorNotice(
							error.message || 'Failed to get column mappings.'
						);
					}
				}}
			/>
		);
	}

	return (
		<ActionSection
			title="New spreadsheet?"
			description="Use this option to create a column for each field and automatically map it."
			buttonText="Map All Fields"
			handleUpdateColumnMappingSection={async () => {
				try {
					await updateColumnMappingSection('from_form_fields');
				} catch (error: any) {
					setErrorNotice(
						error.message || 'Failed to get column mappings.'
					);
				}
			}}
		/>
	);
}

export default ColumnAutoMapping;
