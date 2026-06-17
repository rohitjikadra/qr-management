import InputError from '@/components/input-error';
import { EmailVerificationRequiredAlert } from '@/components/email-verification-required-alert';
import QrContentForm from '@/components/qr/qr-content-form';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { buildQrPayload, emptyContentFor, QR_TYPES, type QrContent, type QrTypeId } from '@/lib/qr';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, LoaderCircle, Lock, Pencil, RefreshCw } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { useMemo, useState } from 'react';

interface Props {
    limits: {
        can_create_dynamic: boolean;
        dynamic_count: number;
        dynamic_limit: number;
        email_verified: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'QR Codes', href: '/qr' },
    { title: 'Create', href: '/qr/create' },
];

export default function QrCreate({ limits }: Props) {
    const [step, setStep] = useState(1);

    const { data, setData, post, processing, errors } = useForm<{
        name: string;
        type: QrTypeId;
        content: QrContent;
        is_dynamic: boolean;
    }>({
        name: '',
        type: 'url',
        content: emptyContentFor('url'),
        is_dynamic: false,
    });

    const typeMeta = QR_TYPES.find((t) => t.id === data.type)!;
    const previewPayload = useMemo(() => buildQrPayload(data.type, data.content), [data.type, data.content]);

    const selectType = (type: QrTypeId) => {
        setData((d) => ({ ...d, type, content: emptyContentFor(type), is_dynamic: false }));
        setStep(2);
    };

    const dynamicBlocked = !limits.can_create_dynamic
        ? `Free plan allows ${limits.dynamic_limit} dynamic QRs (${limits.dynamic_count} used). Upgrade to Pro for unlimited.`
        : null;

    if (!limits.email_verified) {
        return (
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Create QR Code" />
                <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                    <EmailVerificationRequiredAlert />
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create QR Code" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                <div className="flex items-center gap-2 text-sm">
                    {[1, 2, 3].map((n) => (
                        <div key={n} className="flex items-center gap-2">
                            <span
                                className={`flex size-7 items-center justify-center rounded-full text-xs font-medium ${
                                    step >= n ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground'
                                }`}
                            >
                                {n}
                            </span>
                            <span className={step === n ? 'font-medium' : 'text-muted-foreground'}>
                                {n === 1 ? 'Type' : n === 2 ? 'Content' : 'Mode'}
                            </span>
                            {n < 3 && <span className="text-muted-foreground">→</span>}
                        </div>
                    ))}
                </div>

                {step === 1 && (
                    <div className="grid gap-3 sm:grid-cols-2">
                        {QR_TYPES.map((t) => (
                            <button
                                key={t.id}
                                type="button"
                                onClick={() => selectType(t.id)}
                                className="hover:border-primary/60 rounded-xl border p-4 text-left transition-colors"
                            >
                                <div className="flex items-center justify-between">
                                    <p className="font-medium">{t.label}</p>
                                    {t.supportsDynamic && <Badge variant="secondary">Dynamic available</Badge>}
                                </div>
                                <p className="text-muted-foreground mt-1 text-sm">{t.description}</p>
                            </button>
                        ))}
                    </div>
                )}

                {step === 2 && (
                    <div className="grid gap-4 md:grid-cols-[1fr_auto]">
                        <Card>
                            <CardHeader>
                                <CardTitle>{typeMeta.label}</CardTitle>
                                <CardDescription>Fill in the details for your QR code.</CardDescription>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">
                                        QR Name<span className="text-destructive"> *</span>
                                    </Label>
                                    <Input
                                        id="name"
                                        value={data.name}
                                        placeholder='e.g. "Shop Menu" (internal label)'
                                        onChange={(e) => setData('name', e.target.value)}
                                    />
                                    <InputError message={errors.name} />
                                </div>
                                <QrContentForm
                                    type={data.type}
                                    content={data.content}
                                    errors={errors as Record<string, string>}
                                    onChange={(content) => setData('content', content)}
                                />
                                <div className="flex justify-between">
                                    <Button variant="outline" type="button" onClick={() => setStep(1)}>
                                        <ArrowLeft className="size-4" /> Back
                                    </Button>
                                    <Button type="button" onClick={() => setStep(3)} disabled={!data.name}>
                                        Next <ArrowRight className="size-4" />
                                    </Button>
                                </div>
                            </CardContent>
                        </Card>
                        <div className="flex flex-col items-center gap-2">
                            <div className="rounded-xl border bg-white p-3">
                                <QRCodeSVG value={previewPayload || ' '} size={160} />
                            </div>
                            <p className="text-muted-foreground text-xs">Live preview</p>
                        </div>
                    </div>
                )}

                {step === 3 && (
                    <form
                        className="flex flex-col gap-4"
                        onSubmit={(e) => {
                            e.preventDefault();
                            post('/qr');
                        }}
                    >
                        <div className="grid gap-3 sm:grid-cols-2">
                            <button
                                type="button"
                                onClick={() => setData('is_dynamic', false)}
                                className={`rounded-xl border p-4 text-left transition-colors ${
                                    !data.is_dynamic ? 'border-primary ring-primary/30 ring-2' : 'hover:border-primary/40'
                                }`}
                            >
                                <div className="flex items-center gap-2">
                                    <Lock className="size-4" />
                                    <p className="font-medium">Static QR</p>
                                </div>
                                <ul className="text-muted-foreground mt-2 list-inside list-disc text-sm">
                                    <li>Works forever, even offline</li>
                                    <li>Content cannot be changed later</li>
                                    <li>No scan tracking</li>
                                </ul>
                            </button>

                            <button
                                type="button"
                                disabled={!typeMeta.supportsDynamic || dynamicBlocked !== null}
                                onClick={() => setData('is_dynamic', true)}
                                className={`rounded-xl border p-4 text-left transition-colors disabled:opacity-50 ${
                                    data.is_dynamic ? 'border-primary ring-primary/30 ring-2' : 'hover:border-primary/40'
                                }`}
                            >
                                <div className="flex items-center gap-2">
                                    <RefreshCw className="size-4" />
                                    <p className="font-medium">Dynamic QR</p>
                                    <Badge>Trackable</Badge>
                                </div>
                                <ul className="text-muted-foreground mt-2 list-inside list-disc text-sm">
                                    <li>
                                        <Pencil className="mr-1 inline size-3" />
                                        Edit destination anytime — QR stays the same
                                    </li>
                                    <li>Scan analytics included</li>
                                    <li>Pause / resume anytime</li>
                                </ul>
                                {!typeMeta.supportsDynamic && (
                                    <p className="text-destructive mt-2 text-xs">Not available for {typeMeta.label} QRs.</p>
                                )}
                                {typeMeta.supportsDynamic && dynamicBlocked && (
                                    <p className="text-destructive mt-2 text-xs">{dynamicBlocked}</p>
                                )}
                            </button>
                        </div>
                        <InputError message={errors.is_dynamic} />

                        {typeMeta.supportsDynamic && dynamicBlocked && !limits.can_create_dynamic && limits.email_verified && (
                            <Button variant="outline" asChild>
                                <Link href="/billing">Upgrade to Pro</Link>
                            </Button>
                        )}

                        <div className="flex justify-between">
                            <Button variant="outline" type="button" onClick={() => setStep(2)}>
                                <ArrowLeft className="size-4" /> Back
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing && <LoaderCircle className="size-4 animate-spin" />}
                                Create QR Code
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </AppLayout>
    );
}
