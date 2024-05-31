// Create some sort of table that contains any feeds that point to a spreadsheet that are inaccessible. Could look something like

// Feed | Spreadsheet URL | Error
// -----------------------------------------
// Feed 1 | https://docs.google.com/spreadsheets/d/1/edit | Spreadsheet not found
// Feed 2 | https://docs.google.com/spreadsheets/d/2/edit | Access denied

import { useEffect, Fragment } from 'react';
import SettingsSection from '../SettingsSection';
import { __ } from '@wordpress/i18n';
import { usePluginSettingsStore } from '../../store/plugin-settings';
import IconNoIssues from '../../svgs/green-checkmark.svg';

const { gpgs_settings_plugin_strings: strings } = window;

const getIssueMessageFromCode = (code: string): string => {
	if (!code) {
		return '';
	}

	let errorMessage: string;

	switch (code) {
		case 'missing_spreadsheet':
			errorMessage = __('No spreadsheet selected.', 'gp-google-sheets');
			break;
		case 'spreadsheet_not_accessible':
			errorMessage = __(
				'Spreadsheet not accessible.',
				'gp-google-sheets'
			);
			break;
		default:
			errorMessage = __('Unknown error.', 'gp-google-sheets');
			break;
	}

	return errorMessage;
};

const SectionIssues = () => {
	const scanIssues = usePluginSettingsStore.use.scanIssues();
	const issues = usePluginSettingsStore.use.issues();
	const issuesError = usePluginSettingsStore.use.issuesError();

	useEffect(() => {
		scanIssues();
	}, [scanIssues]);

	let issuesOutput = null;

	const hasFeedIssues = issues?.some((issue: any) => issue.type === 'feed');

	const hasGPPAIssues = issues?.some(
		(issue: any) => issue.type === 'gppa_object_type'
	);

	if (issuesError) {
		issuesOutput = <div className="error">{issuesError}</div>;
	} else if (typeof issues === 'undefined') {
		issuesOutput = <p>{__('Scanning…', 'gp-google-sheets')}</p>;
	} else if (issues.length === 0) {
		issuesOutput = (
			<p
				style={{
					display: 'flex',
					alignItems: 'center',
					gap: '0.875rem',
					margin: '0',
				}}
			>
				<IconNoIssues /> {__('No issues found.', 'gp-google-sheets')}
			</p>
		);
	} else {
		issuesOutput = (
			<Fragment>
				{hasFeedIssues && (
					<Fragment>
						<h3>{__('Feeds', 'gp-google-sheets')}</h3>
						<p
							style={{
								marginTop: 'calc( 0.875rem / 2 )',
							}}
						>
							{__(
								'The following GP Google Sheets feeds are experiencing issues accessing their configured spreadsheet.',
								'gp-google-sheets'
							)}
						</p>
						<table className="gform-table gform-table--responsive">
							<thead>
								<tr>
									<th>{__('Form', 'gp-google-sheets')}</th>
									<th>{__('Feed', 'gp-google-sheets')}</th>
									<th>
										{__('Spreadsheet', 'gp-google-sheets')}
									</th>
									<th>{__('Error', 'gp-google-sheets')}</th>
								</tr>
							</thead>
							<tbody>
								{issues.map((issue: any, index: number) => {
									if (issue.type !== 'feed') {
										return null;
									}

									const errorMessage =
										getIssueMessageFromCode(issue.code);

									return (
										<tr key={index}>
											<td>
												<a
													href={issue.formUrl}
													target="_blank"
													rel="noreferrer"
												>
													{issue.formTitle}
												</a>
											</td>
											<td>
												<a
													href={issue.feedUrl}
													target="_blank"
													rel="noreferrer"
												>
													{issue.feedName}
												</a>
											</td>
											<td>
												{issue.spreadsheetUrl ? (
													<a
														href={
															issue.spreadsheetUrl
														}
														target="_blank"
														rel="noreferrer"
													>
														{__(
															'Open Spreadsheet',
															'gp-google-sheets'
														)}
													</a>
												) : (
													<span>&ndash;</span>
												)}
											</td>
											<td>{errorMessage}</td>
										</tr>
									);
								})}
							</tbody>
						</table>
					</Fragment>
				)}

				{strings.gppa_activated && hasGPPAIssues && (
					<Fragment>
						<h3 style={{ marginTop: '1.5rem' }}>
							{__('Form Fields', 'gp-google-sheets')}
						</h3>
						<p
							style={{
								marginTop: 'calc( 0.875rem / 2 )',
							}}
						>
							{__(
								'The following form fields are populated from Google Sheets via GP Populate Anything and experiencing issues accessing the configured spreadsheet.',
								'gp-google-sheets'
							)}
						</p>

						<table className="gform-table gform-table--responsive">
							<thead>
								<tr>
									<th>{__('Form', 'gp-google-sheets')}</th>
									<th>{__('Field', 'gp-google-sheets')}</th>
									<th>
										{__('Spreadsheet', 'gp-google-sheets')}
									</th>
									<th>{__('Error', 'gp-google-sheets')}</th>
								</tr>
							</thead>
							<tbody>
								{issues.map((issue: any, index: number) => {
									if (issue.type !== 'gppa_object_type') {
										return null;
									}

									const errorMessage =
										getIssueMessageFromCode(issue.code);

									return (
										<tr key={index}>
											<td>
												<a
													href={issue.formUrl}
													target="_blank"
													rel="noreferrer"
												>
													{issue.formTitle}
												</a>
											</td>
											<td>
												{issue.fieldLabel} (
												{issue.populate === 'values'
													? __(
															'Values',
															'gp-google-sheets'
													  )
													: __(
															'Choices',
															'gp-google-sheets'
													  )}
												)
											</td>
											<td>
												{issue.spreadsheetUrl ? (
													<a
														href={
															issue.spreadsheetUrl
														}
														target="_blank"
														rel="noreferrer"
													>
														{__(
															'Open Spreadsheet',
															'gp-google-sheets'
														)}
													</a>
												) : (
													<span>&ndash;</span>
												)}
											</td>
											<td>{errorMessage}</td>
										</tr>
									);
								})}
							</tbody>
						</table>
					</Fragment>
				)}
			</Fragment>
		);
	}

	return (
		<SettingsSection title="Issues" id="issues">
			{issuesOutput}
		</SettingsSection>
	);
};

export default SectionIssues;
