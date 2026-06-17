import { Alert, AlertDescription } from '@/components/ui/alert';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { Plus, Settings } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface SettingRow {
    key: string;
    value: string | null;
}

interface Props {
    settings: SettingRow[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Settings', href: '/admin/settings' },
];

export default function AdminSettingsIndex({ settings: initialSettings }: Props) {
    const { flash } = usePage<SharedData>().props;

    const { data, setData, put, errors, processing } = useForm({
        settings:
            initialSettings.length > 0
                ? initialSettings.map((s) => ({ key: s.key, value: s.value ?? '' }))
                : [{ key: '', value: '' }],
    });

    const addRow = () => {
        setData('settings', [...data.settings, { key: '', value: '' }]);
    };

    const updateRow = (index: number, field: 'key' | 'value', value: string) => {
        const next = [...data.settings];
        next[index] = { ...next[index], [field]: value };
        setData('settings', next);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put('/admin/settings');
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Settings" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                {flash?.success && (
                    <Alert>
                        <AlertDescription>{flash.success as string}</AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Settings</h1>
                        <p className="text-muted-foreground text-sm">
                            Key-value configuration. Branding keys are on{' '}
                            <Link href="/admin/settings/branding" className="text-primary underline">
                                Branding & SEO
                            </Link>
                            .
                        </p>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Application settings</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {data.settings.length === 0 ? (
                            <div className="flex flex-col items-center gap-3 py-8 text-center">
                                <Settings className="text-muted-foreground size-10" />
                                <p className="text-muted-foreground text-sm">No settings yet.</p>
                                <Button type="button" variant="outline" onClick={addRow}>
                                    <Plus className="size-4" /> Add setting
                                </Button>
                            </div>
                        ) : (
                            <form onSubmit={submit} className="space-y-4">
                                {data.settings.map((row, index) => (
                                    <div key={index} className="grid gap-3 border-b pb-4 last:border-0 sm:grid-cols-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor={`key-${index}`}>Key</Label>
                                            <Input
                                                id={`key-${index}`}
                                                value={row.key}
                                                onChange={(e) => updateRow(index, 'key', e.target.value)}
                                                placeholder="setting_key"
                                                required
                                            />
                                            <InputError message={errors[`settings.${index}.key` as keyof typeof errors]} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor={`value-${index}`}>Value</Label>
                                            <textarea
                                                id={`value-${index}`}
                                                rows={2}
                                                className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
                                                value={row.value}
                                                onChange={(e) => updateRow(index, 'value', e.target.value)}
                                            />
                                            <InputError
                                                message={errors[`settings.${index}.value` as keyof typeof errors]}
                                            />
                                        </div>
                                    </div>
                                ))}
                                <div className="flex flex-wrap gap-2">
                                    <Button type="button" variant="outline" onClick={addRow}>
                                        <Plus className="size-4" /> Add row
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        Save settings
                                    </Button>
                                </div>
                            </form>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
