export {};

interface GFScriptStrings {
    [key: string]: string;
}

export type GPGSPluginSettingsStrings = GFScriptStrings & {
    token?: Token;
    user_id: string
}

export type GPGSFeedSettingsStrings = GFScriptStrings & {
    token?: Token;
    user_id: string;
}

export type GPGSFeedSettingsEditStrings = GPGSFeedSettingsStrings & {
    error_message?: string;
}

declare global {
    interface Window {
        gpgs_settings_plugin_strings: GPGSPluginSettingsStrings;
        gpgs_settings_strings: GPGSFeedSettingsStrings;
        gpgs_settings_feed_edit_strings: GPGSFeedSettingsEditStrings;
        gform_initialize_tooltips: () => void;
    }

    interface WPAjaxJSONResponse {
        success: boolean
        data: any
    }
}

export interface Token {
	access_token: string;
	refresh_token: string;
	expiry_date: number;
	scope: string;
	token_type: string;
}

export interface OAuthResponseData {
	success: '0' | '1';
	message: string;
	token?: Token;
    sheet_url?: string;
}
