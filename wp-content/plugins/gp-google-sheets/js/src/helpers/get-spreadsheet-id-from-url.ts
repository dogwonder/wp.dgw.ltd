const getSpreadsheetIdFromUrl = (url: string) => {
	const urlPieces = url.split('/');
	if (urlPieces.length >= 6) {
		return urlPieces[5];
	}
	return null;
};

export default getSpreadsheetIdFromUrl;
