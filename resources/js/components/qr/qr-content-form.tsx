import InputError from '@/components/input-error';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type QrContent, type QrTypeId } from '@/lib/qr';

interface Props {
    type: QrTypeId;
    content: QrContent;
    errors: Record<string, string>;
    onChange: (content: QrContent) => void;
}

function Textarea(props: React.TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return (
        <textarea
            {...props}
            className="border-input placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-20 w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-1 focus-visible:outline-none"
        />
    );
}

export default function QrContentForm({ type, content, errors, onChange }: Props) {
    const set = (key: string, value: string | boolean) => onChange({ ...content, [key]: value });

    const field = (key: string, label: string, opts: { placeholder?: string; type?: string; required?: boolean } = {}) => (
        <div className="grid gap-2">
            <Label htmlFor={`c-${key}`}>
                {label}
                {opts.required && <span className="text-destructive"> *</span>}
            </Label>
            <Input
                id={`c-${key}`}
                type={opts.type ?? 'text'}
                value={String(content[key] ?? '')}
                placeholder={opts.placeholder}
                onChange={(e) => set(key, e.target.value)}
            />
            <InputError message={errors[`content.${key}`]} />
        </div>
    );

    switch (type) {
        case 'url':
            return field('url', 'Website URL', { placeholder: 'https://example.com', type: 'url', required: true });

        case 'whatsapp':
            return (
                <>
                    {field('phone', 'WhatsApp Number (with country code)', { placeholder: '+91 98765 43210', required: true })}
                    <div className="grid gap-2">
                        <Label htmlFor="c-message">Pre-filled Message</Label>
                        <Textarea
                            id="c-message"
                            value={String(content.message ?? '')}
                            placeholder="Hi! I'm interested in..."
                            onChange={(e) => set('message', e.target.value)}
                        />
                        <InputError message={errors['content.message']} />
                    </div>
                </>
            );

        case 'email':
            return (
                <>
                    {field('to', 'To Email', { placeholder: 'hello@example.com', type: 'email', required: true })}
                    {field('subject', 'Subject', { placeholder: 'Enquiry' })}
                    <div className="grid gap-2">
                        <Label htmlFor="c-body">Body</Label>
                        <Textarea
                            id="c-body"
                            value={String(content.body ?? '')}
                            onChange={(e) => set('body', e.target.value)}
                        />
                        <InputError message={errors['content.body']} />
                    </div>
                </>
            );

        case 'phone':
            return field('phone', 'Phone Number (with country code)', { placeholder: '+91 98765 43210', required: true });

        case 'wifi':
            return (
                <>
                    {field('ssid', 'Network Name (SSID)', { required: true })}
                    <div className="grid gap-2">
                        <Label>Security</Label>
                        <Select value={String(content.security ?? 'WPA')} onValueChange={(v) => set('security', v)}>
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="WPA">WPA / WPA2</SelectItem>
                                <SelectItem value="WEP">WEP</SelectItem>
                                <SelectItem value="None">None (open network)</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors['content.security']} />
                    </div>
                    {content.security !== 'None' && field('password', 'Password', { required: true })}
                    <div className="flex items-center gap-2">
                        <Checkbox id="c-hidden" checked={Boolean(content.hidden)} onCheckedChange={(v) => set('hidden', v === true)} />
                        <Label htmlFor="c-hidden">Hidden network</Label>
                    </div>
                </>
            );

        case 'vcard':
            return (
                <>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {field('first_name', 'First Name', { required: true })}
                        {field('last_name', 'Last Name')}
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {field('organization', 'Company')}
                        {field('job_title', 'Job Title')}
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {field('phone', 'Phone')}
                        {field('email', 'Email', { type: 'email' })}
                    </div>
                    {field('website', 'Website', { placeholder: 'https://', type: 'url' })}
                    <div className="grid gap-4 sm:grid-cols-2">
                        {field('city', 'City')}
                        {field('country', 'Country')}
                    </div>
                </>
            );

        case 'text':
            return (
                <div className="grid gap-2">
                    <Label htmlFor="c-text">
                        Text<span className="text-destructive"> *</span>
                    </Label>
                    <Textarea
                        id="c-text"
                        value={String(content.text ?? '')}
                        placeholder="Your message..."
                        onChange={(e) => set('text', e.target.value)}
                    />
                    <InputError message={errors['content.text']} />
                </div>
            );
    }
}
