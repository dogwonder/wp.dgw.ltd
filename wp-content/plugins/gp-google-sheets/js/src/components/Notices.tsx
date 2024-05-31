import React from 'react';
import { createPortal } from 'react-dom';
import { Notice } from '../store/slices/notices';

const Notices = ({ notices }: { notices: Notice[] }) => {
	const noticesWrapper = document.getElementById('gf-admin-notices-wrapper');

	if (!noticesWrapper) {
		return null;
	}

	const NoticeMarkup = ({ notice }: { notice: Notice }) => (
		<div className={`notice notice-${notice.type} gf-notice`}>
			<p>{notice.message}</p>
		</div>
	);

	const NoticesMarkup = (
		<React.Fragment>
			{notices.map((notice, index) => (
				<NoticeMarkup notice={notice} key={index} />
			))}
		</React.Fragment>
	);

	return createPortal(NoticesMarkup, noticesWrapper!);
};

export default Notices;
