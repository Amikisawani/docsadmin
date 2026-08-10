/// <reference types="vite/client" />

interface ImportMetaEnv {
    readonly VITE_APP_URL?: string;
    readonly VITE_REVERB_APP_KEY?: string;
    readonly VITE_REVERB_HOST?: string;
    readonly VITE_REVERB_PORT?: string;
    readonly VITE_REVERB_SCHEME?: string;
    readonly VITE_PUSHER_APP_KEY?: string;
    readonly VITE_PUSHER_APP_CLUSTER?: string;
    readonly VITE_BROADCAST_CONNECTION?: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
