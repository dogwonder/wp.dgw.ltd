import { Fragment } from 'react';
import InsertTestRowButton from './InsertTestRow';
import DisconnectButton from './DisconnectButton';
import { useFeedSettingsStore } from '../../store/feed-settings';

const ConnectedCard = () => {
	const sheetUrl = useFeedSettingsStore.use.googleSheetUrl();
	const spreadsheetName = useFeedSettingsStore.use.googleSpreadsheetName();
	const sheetName = useFeedSettingsStore.use.googleSheetName();
	const googleErrorMessage = useFeedSettingsStore.use.googleErrorMessage();

	let linkText = spreadsheetName;

	if (sheetName) {
		linkText = `${linkText} (${sheetName})`;
	}

	const errorMessage = googleErrorMessage && (
		<Fragment>
			<div dangerouslySetInnerHTML={{ __html: googleErrorMessage }} />
			<br />
		</Fragment>
	);

	const spreadsheetLink = (
		<span>
			<a href={sheetUrl} target="_blank" rel="noreferrer">
				{linkText}
			</a>
		</span>
	);

	if (googleErrorMessage) {
		return (
			<p className="alert error">
				{errorMessage}
				{spreadsheetLink}
				<span
					style={{
						alignSelf: 'self-end',
						marginLeft: 'auto !important',
						verticalAlign: 'middle',
					}}
				>
					<DisconnectButton />
				</span>
			</p>
		);
	}

	return (
		<div
			className="alert gforms_note_success"
			style={{ display: 'flex', alignItems: 'center', marginBottom: 0 }}
		>
			{spreadsheetLink}
			<span
				style={{
					alignSelf: 'self-end',
					marginLeft: 'auto',
					verticalAlign: 'middle',
					display: 'flex',
					gap: '1rem',
				}}
			>
				<InsertTestRowButton />
				<DisconnectButton />
			</span>
		</div>
	);
};

export default ConnectedCard;
