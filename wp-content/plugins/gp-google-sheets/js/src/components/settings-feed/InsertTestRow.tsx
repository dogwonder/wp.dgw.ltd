import { __ } from '@wordpress/i18n';
import { useFeedSettingsStore } from '../../store/feed-settings';

const InsertTestRowButton = () => {
	const isInserting = useFeedSettingsStore.use.isInsertingTestRow();
	const insertTestRow = useFeedSettingsStore.use.insertTestRow();

	const onClick = (e: React.MouseEvent<HTMLButtonElement>) => {
		e.preventDefault();
		insertTestRow();
	};

	return (
		<button
			id="insert_test_row"
			className="button add-new-h2"
			onClick={onClick}
			disabled={isInserting}
			style={{ top: 0 }}
		>
			{__('Insert Test Row', 'gp-google-sheets')}
			{isInserting && (
				<span className="spinner spinner_insert_test_row is-active"></span>
			)}
		</button>
	);
};

export default InsertTestRowButton;
