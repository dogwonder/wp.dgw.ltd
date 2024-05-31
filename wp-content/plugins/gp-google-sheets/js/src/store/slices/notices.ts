import { StateCreator } from 'zustand';

export interface Notice {
	type: 'success' | 'error';
	message: string;
}

export interface NoticesSlice {
	notices: Notice[];
	clearNotices: () => void;
	addNotice: (notice: Notice) => void;
	setNotice: (notice: Notice) => void;
	setErrorNotice: (message: string) => void;
	setSuccessNotice: (message: string) => void;
}

export const createNoticesSlice: StateCreator<NoticesSlice> = (set) => ({
	notices: [],
	clearNotices: () => set({ notices: [] }),
	addNotice: (notice) =>
		set((state) => ({ notices: [...state.notices, notice] })),
	setNotice: (notice) => set({ notices: [notice] }),
	setErrorNotice: (message) => set({ notices: [{ type: 'error', message }] }),
	setSuccessNotice: (message) =>
		set({ notices: [{ type: 'success', message }] }),
});
