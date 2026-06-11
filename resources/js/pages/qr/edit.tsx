import InputError from '@/components/input-error';
import QrContentForm from '@/components/qr/qr-content-form';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type QrContent, type QrTypeId } from '@/lib/qr';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Info, LoaderCircle } from 'lucide-react';

interface QrDetail {
    id: number;
    name: string;
    type: QrTypeId;
    type_label: string;
    content: QrContent;
    is_dynamic: boolean;
}

export default function QrEdit({ qr }: { qr: QrDetail }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'QR Codes', href: '/qr' },
        { title: qr.name, href: `/qr/${qr.id}` },
        { title: 'Edit', href: `/qr/${qr.id}/edit` },
    ];

    const { data, setData, put, processing, errors } = useForm<{
        name: string;
        content: QrContent;
    }>({
        name: qr.name,
        content: qr.content,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit — ${qr.name}`} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit QR Code</CardTitle>
                        <CardDescription>{qr.type_label}</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form
                            className="flex flex-col gap-4"
                            onSubmit={(e) => {
                                e.preventDefault();
                                put(`/qr/${qr.id}`);
                            }}
                        >
                            <div className="grid gap-2">
                                <Label htmlFor="name">QR Name</Label>
                                <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                                <InputError message={errors.name} />
                            </div>

                            {qr.is_dynamic ? (
                                <>
                                    <Alert>
                                        <Info className="size-4" />
                                        <AlertDescription>
                                            Your printed QR image stays exactly the same — only the destination changes.
                                        </AlertDescription>
                                    </Alert>
                                    <QrContentForm
                                        type={qr.type}
                                        content={data.content}
                                        errors={errors as Record<string, string>}
                                        onChange={(content) => setData('content', content)}
                                    />
                                </>
                            ) : (
                                <Alert>
                                    <Info className="size-4" />
                                    <AlertDescription>
                                        This is a static QR — its content is baked into the image and cannot be changed. Only the
                                        internal name can be edited.
                                    </AlertDescription>
                                </Alert>
                            )}

                            <div className="flex justify-end">
                                <Button type="submit" disabled={processing}>
                                    {processing && <LoaderCircle className="size-4 animate-spin" />}
                                    Save changes
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
