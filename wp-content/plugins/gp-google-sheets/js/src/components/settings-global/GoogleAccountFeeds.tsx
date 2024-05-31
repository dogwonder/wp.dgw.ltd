import { __ } from '@wordpress/i18n';
import { GoogleAccountHealth } from '../../store/slices/plugin-settings/google-accounts-health';

const { gpgs_settings_plugin_strings: strings } = window;

const GoogleAccountFeeds = ({
	googleAccount,
	visible,
}: {
	googleAccount: GoogleAccountHealth;
	visible: Boolean;
}) => {
	const { connectedFeeds } = googleAccount;

	return (
		<tr
			className="gpgs-token-feeds gpgs_border_top"
			style={{ display: visible ? 'table-row' : 'none' }}
		>
			<td colSpan={6} className="gpgs_light_grey_background">
				<table className="gform-table gform-table--responsive gform-table--no-outer-border gpgs_light_grey_background gpgs_token_connected_feed_table">
					<thead className="gpgs_border_bottom">
						<tr>
							<th>{__('Form', 'gp-google-sheets')}</th>
							<th>{__('Feed', 'gp-google-sheets')}</th>
						</tr>
					</thead>
					<tbody>
						{connectedFeeds
							?.filter(
								({
									form_title: formTitle,
									feed_name: feedName,
								}) => {
									return formTitle && feedName;
								}
							)
							.map(
								({
									form_title: formTitle,
									feed_name: feedName,
									feed_url: feedUrl,
									form_id: formId,
								}) => (
									<tr
										className="gpgs_token_connected_feed_list_row"
										key={feedUrl}
									>
										<td>
											<a
												href={`${strings.admin_url}?page=gf_edit_forms&id=${formId}`}
												target="_blank"
												rel="noreferrer"
											>
												{formTitle}
											</a>
										</td>
										<td>
											<a
												href={feedUrl}
												target="_blank"
												rel="noreferrer"
											>
												{feedName}
											</a>
										</td>
									</tr>
								)
							)}
					</tbody>
				</table>
			</td>
		</tr>
	);
};

export default GoogleAccountFeeds;
