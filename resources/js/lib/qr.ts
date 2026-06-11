export type QrTypeId = 'url' | 'whatsapp' | 'email' | 'phone' | 'wifi' | 'vcard' | 'text';

export type QrContent = Record<string, string | boolean | undefined>;

export interface QrTypeMeta {
    id: QrTypeId;
    label: string;
    description: string;
    supportsDynamic: boolean;
}

export const QR_TYPES: QrTypeMeta[] = [
    { id: 'url', label: 'Website URL', description: 'Open a website when scanned', supportsDynamic: true },
    { id: 'whatsapp', label: 'WhatsApp', description: 'Start a WhatsApp chat', supportsDynamic: true },
    { id: 'email', label: 'Email', description: 'Compose an email', supportsDynamic: false },
    { id: 'phone', label: 'Phone Call', description: 'Dial a phone number', supportsDynamic: false },
    { id: 'wifi', label: 'WiFi', description: 'Connect to a WiFi network', supportsDynamic: false },
    { id: 'vcard', label: 'Contact Card', description: 'Save contact details (vCard)', supportsDynamic: false },
    { id: 'text', label: 'Plain Text', description: 'Show a text message', supportsDynamic: false },
];

const escapeWifi = (v: string) => v.replace(/([\\;,":])/g, '\\$1');

/**
 * Client-side mirror of the backend QrContentBuilder, used only for
 * the live preview while creating/editing. The server build is canonical.
 */
export function buildQrPayload(type: QrTypeId, c: QrContent): string {
    const s = (k: string) => String(c[k] ?? '');

    switch (type) {
        case 'url':
            return s('url');
        case 'whatsapp': {
            const phone = s('phone').replace(/[^0-9]/g, '');
            const msg = s('message');
            return `https://wa.me/${phone}` + (msg ? `?text=${encodeURIComponent(msg)}` : '');
        }
        case 'email': {
            const params = new URLSearchParams();
            if (s('subject')) params.set('subject', s('subject'));
            if (s('body')) params.set('body', s('body'));
            const q = params.toString();
            return `mailto:${s('to')}` + (q ? `?${q}` : '');
        }
        case 'phone':
            return `tel:${s('phone').replace(/[^0-9+]/g, '')}`;
        case 'wifi': {
            const sec = s('security') || 'WPA';
            let out = `WIFI:T:${sec === 'None' ? 'nopass' : sec};S:${escapeWifi(s('ssid'))};`;
            if (sec !== 'None' && s('password')) out += `P:${escapeWifi(s('password'))};`;
            if (c.hidden) out += 'H:true;';
            return out + ';';
        }
        case 'vcard': {
            const lines = [
                'BEGIN:VCARD',
                'VERSION:3.0',
                `N:${s('last_name')};${s('first_name')};;;`,
                `FN:${[s('first_name'), s('last_name')].filter(Boolean).join(' ')}`,
            ];
            if (s('organization')) lines.push(`ORG:${s('organization')}`);
            if (s('job_title')) lines.push(`TITLE:${s('job_title')}`);
            if (s('phone')) lines.push(`TEL;TYPE=CELL:${s('phone')}`);
            if (s('email')) lines.push(`EMAIL:${s('email')}`);
            if (s('website')) lines.push(`URL:${s('website')}`);
            lines.push('END:VCARD');
            return lines.join('\r\n');
        }
        case 'text':
            return s('text');
    }
}

export function emptyContentFor(type: QrTypeId): QrContent {
    switch (type) {
        case 'url':
            return { url: '' };
        case 'whatsapp':
            return { phone: '', message: '' };
        case 'email':
            return { to: '', subject: '', body: '' };
        case 'phone':
            return { phone: '' };
        case 'wifi':
            return { ssid: '', security: 'WPA', password: '', hidden: false };
        case 'vcard':
            return {
                first_name: '', last_name: '', organization: '', job_title: '',
                phone: '', email: '', website: '', street: '', city: '',
                state: '', zip: '', country: '',
            };
        case 'text':
            return { text: '' };
    }
}
