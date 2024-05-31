import React, { Fragment, useState } from 'react';
import { __ } from '@wordpress/i18n';

import IconTokenHealthy from '../../svgs/green-checkmark.svg';

// eslint-disable-next-line import/no-unresolved
import IconTokenUnhealthy from '../../svgs/red-x.svg';

// eslint-disable-next-line import/no-unresolved
import IconWarning from '../../svgs/warning.svg';
import DeleteAccountButton from './DeleteAccountButton';
import ReconnectTokenButton from './ReconnectTokenButton';
import GoogleAccountSpreadsheets from './GoogleAccountSpreadsheets';
import GoogleAccountFeeds from './GoogleAccountFeeds';
import { GoogleAccountHealth } from '../../store/slices/plugin-settings/google-accounts-health';

const GoogleAccountRow = ({
	googleAccount,
}: {
	googleAccount: GoogleAccountHealth;
}) => {
	const [feedsVisible, setFeedVisible] = useState(false);
	const [spreadsheetsVisible, setSpreadsheetsVisible] = useState(false);

	const {
		tokenIsHealthy,
		googleEmail,
		accountId,
		connectedFeeds,
		userDisplayName,
		userEditLink,
		isLegacyToken,
	} = googleAccount;

	let userCol = (
		<td className="gpgs_google_account_user_display_name">
			<a href={userEditLink}>{userDisplayName}</a>
		</td>
	);

	if (userDisplayName === null) {
		userCol = <td className="gpgs_google_account_user_display_name">–</td>;

		if (isLegacyToken) {
			userCol = (
				<td className="gpgs_google_account_user_display_name">
					<span className="gpgs_token_legacy_token_warning">
						<IconWarning />
						{__('Legacy Token', 'gp-google-sheets')}
					</span>
				</td>
			);
		}
	}

	const toggleFeeds = (e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		setFeedVisible(!feedsVisible);
	};

	const toggleSpreadsheets = (e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		setSpreadsheetsVisible(!spreadsheetsVisible);
	};

	return (
		<Fragment>
			<tr className="">
				<td className="gpgs_google_account_status">
					{tokenIsHealthy ? (
						<Fragment>
							<IconTokenHealthy />
							<span className="screen-reader-text">
								{__('Connected', 'gp-google-sheets')}
							</span>
						</Fragment>
					) : (
						<Fragment>
							<IconTokenUnhealthy />
							<span className="screen-reader-text">
								{__('Disconnected', 'gp-google-sheets')}
							</span>
						</Fragment>
					)}
				</td>
				{userCol}
				<td className="gpgs_google_account_google_email">
					{googleEmail || '–'}
				</td>
				<td className="gpgs_google_account_connected_feeds">
					{connectedFeeds?.length ? (
						<Fragment>
							<button
								className="gpgs_token_toggle_feeds button-link"
								onClick={toggleFeeds}
							>
								{feedsVisible
									? __('Hide Feeds', 'gp-google-sheets')
									: __('Show Feeds', 'gp-google-sheets')}
							</button>{' '}
							({connectedFeeds.length})
						</Fragment>
					) : (
						''
					)}
				</td>
				<td className="gpgs_google_account_spreadsheets">
					{tokenIsHealthy && !isLegacyToken && (
						<button
							className="gpgs_token_toggle_spreadsheets button-link"
							onClick={toggleSpreadsheets}
						>
							{spreadsheetsVisible
								? __('Hide Spreadsheets', 'gp-google-sheets')
								: __('Show Spreadsheets', 'gp-google-sheets')}
						</button>
					)}
					{isLegacyToken && '–'}
				</td>
				<td className="gpgs_google_account_actions">
					{!tokenIsHealthy && !isLegacyToken ? (
						<ReconnectTokenButton />
					) : undefined}

					<DeleteAccountButton accountId={accountId} />
				</td>
			</tr>
			<GoogleAccountFeeds
				googleAccount={googleAccount}
				visible={feedsVisible}
			/>
			<GoogleAccountSpreadsheets
				googleAccount={googleAccount}
				visible={spreadsheetsVisible}
			/>
		</Fragment>
	);
};

export default GoogleAccountRow;
