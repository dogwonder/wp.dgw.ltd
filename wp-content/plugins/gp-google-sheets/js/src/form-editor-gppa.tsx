import { useState, useEffect } from 'react';
import $ from 'jquery';

window.gform.addFilter(
	'gppa_primary_property_computed',
	function (primaryProperty: string, store: any) {
		if (store.objectType !== 'gpgs_sheet') {
			return primaryProperty;
		}

		// Split the primary property and ensure we have both the spreadsheet and sheet.
		const primaryPropertySplit = primaryProperty.split('|');

		if (primaryPropertySplit.length !== 2) {
			return null;
		}

		return primaryProperty;
	}
);

const GPGSPrimaryPropertySelect = ({
	populate,
	propertyValues,
	primaryProperty,
	setPrimaryProperty,
}: any) => {
	const [sheets, setSheets] = useState([]);
	const primaryPropertySplit = primaryProperty
		? primaryProperty.split('|')
		: [undefined, undefined];
	const spreadsheet = primaryPropertySplit[0];
	const sheet = primaryPropertySplit[1];
	const gppaStrings = (window as any).GPPA_ADMIN.strings;

	const getSheets = () => {
		setSheets([]);
		$.post(
			window.ajaxurl,
			{
				action: 'gpgs_gppa_get_sheets',
				spreadsheet_id: spreadsheet,
				security: window.GPPA_ADMIN.nonce,
			},
			null,
			'json'
		).done((data) => {
			setSheets(data);
		});
	};

	useEffect(() => {
		if (spreadsheet) {
			getSheets();
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [spreadsheet]);

	const handleSpreadsheetChange = (event: any) => {
		const value = event.target.value;
		setPrimaryProperty(value);
		getSheets();
	};

	const handleSheetChange = (event: any) => {
		const value = spreadsheet + '|' + event.target.value;
		setPrimaryProperty(value);
	};

	return (
		<div>
			<label
				className="section_label gppa-primary-property-label"
				style={{ marginTop: '15px' }}
				htmlFor={`gppa-${populate}-primary-property`}
			>
				Spreadsheet
			</label>

			{!('primary-property' in propertyValues) ? (
				<select
					className="gppa-primary-property"
					disabled
					id={`gppa-${populate}-primary-property`}
					value=""
				>
					<option value="" disabled>
						{gppaStrings.loadingEllipsis}
					</option>
				</select>
			) : (
				<select
					className="gppa-primary-property"
					value={spreadsheet}
					onChange={handleSpreadsheetChange}
					id={`gppa-${populate}-primary-property`}
				>
					{!primaryProperty && (
						<option value="" hidden disabled>
							{gppaStrings.selectAnItem.replace(
								/%s/g,
								'Spreadsheet'
							)}
						</option>
					)}
					{propertyValues['primary-property'].map((option: any) => (
						<option key={option.value} value={option.value}>
							{/*Truncate*/}
							{option.label}
						</option>
					))}
				</select>
			)}

			{spreadsheet && (
				<>
					<label
						className="section_label gppa-primary-property-label"
						style={{ marginTop: '15px' }}
						htmlFor={`gppa-${populate}-primary-property-sheet`}
					>
						Sheet
					</label>
					{sheets.length ? (
						<select
							className="gppa-primary-property"
							value={sheet ?? ''}
							onChange={handleSheetChange}
							id={`gppa-${populate}-primary-property-sheet`}
						>
							{!sheet && (
								<option value="" hidden disabled>
									{gppaStrings.selectAnItem.replace(
										/%s/g,
										'Sheet'
									)}
								</option>
							)}
							{sheets.map((option: any) => (
								<option value={option.value} key={option.value}>
									{/*Truncate*/}
									{option.label}
								</option>
							))}
						</select>
					) : (
						<select className="gppa-primary-property" disabled>
							<option value="" disabled selected>
								{gppaStrings.loadingEllipsis}
							</option>
						</select>
					)}
				</>
			)}
		</div>
	);
};

window.gform.addFilter(
	'gppa_custom_primary_property_component',
	function (component: any, store: any) {
		if (store.objectType !== 'gpgs_sheet') {
			return component;
		}

		return GPGSPrimaryPropertySelect;
	}
);
