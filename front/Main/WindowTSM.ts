export interface CustomWindow extends Window {
    tsmVariables: Record<string, unknown>;
    tsmI18N: Record<string, string>;
    wp: any;
}
