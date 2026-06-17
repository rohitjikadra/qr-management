import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/react';
import { CreditCard, Info } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface Props {
    payments_enabled: boolean;
    payments_disabled_message: string;
    billing_mode: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Payment controls', href: '/admin/billing/payment-controls' },
];

export default function PaymentControls({ payments_enabled, payments_disabled_message, billing_mode }: Props) {
    const { flash } = usePage<SharedData>().props;

    const { data, setData, put, processing, errors } = useForm({
        payments_enabled,
        payments_disabled_message: payments_disabled_message ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put('/admin/billing/payment-controls');
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Payment controls" />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                {flash?.success && (
                    <Alert>
                        <AlertDescription>{flash.success as string}</AlertDescription>
                    </Alert>
                )}

                <div>
                    <h1 className="text-xl font-semibold">Payment controls</h1>
                    <p className="text-muted-foreground text-sm">
                        Super admin only. Close payments temporarily while keeping admin manual Pro grants active.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2 text-base">
                            <CreditCard className="size-4" /> Billing mode
                        </CardTitle>
                        <CardDescription>
                            {billing_mode === 'autopay'
                                ? 'Autopay (recurring subscriptions) is enabled.'
                                : 'Manual renewal — users pay once per period and renew themselves.'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Badge variant="secondary">
                            {billing_mode === 'autopay' ? 'Autopay' : 'Manual renewal'}
                        </Badge>
                        {billing_mode !== 'autopay' && (
                            <p className="text-muted-foreground mt-2 text-xs">
                                Autopay can be enabled in a future update. No action needed now.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <form onSubmit={submit}>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Checkout availability</CardTitle>
                            <CardDescription>
                                When closed, users cannot buy or renew Pro plans. Webhooks and admin complimentary access still work.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-6">
                            <div className="flex items-start gap-3 rounded-lg border p-4">
                                <Checkbox
                                    id="payments_enabled"
                                    checked={data.payments_enabled}
                                    onCheckedChange={(checked) => setData('payments_enabled', checked === true)}
                                />
                                <div className="flex flex-col gap-1">
                                    <Label htmlFor="payments_enabled">Accept payments</Label>
                                    <p className="text-muted-foreground text-xs">
                                        {data.payments_enabled
                                            ? 'Users can pay on pricing and billing pages.'
                                            : 'Checkout is disabled for all users.'}
                                    </p>
                                </div>
                            </div>

                            <div className="flex flex-col gap-2">
                                <Label htmlFor="payments_disabled_message">Message when payments are closed</Label>
                                <textarea
                                    id="payments_disabled_message"
                                    className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[100px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none"
                                    value={data.payments_disabled_message}
                                    onChange={(e) => setData('payments_disabled_message', e.target.value)}
                                    placeholder="e.g. We are upgrading our payment system. Please check back in a few hours."
                                    rows={4}
                                />
                                {errors.payments_disabled_message && (
                                    <p className="text-destructive text-sm">{errors.payments_disabled_message}</p>
                                )}
                                <p className="text-muted-foreground flex items-start gap-2 text-xs">
                                    <Info className="mt-0.5 size-3.5 shrink-0" />
                                    Shown on pricing and billing pages when checkout is disabled.
                                </p>
                            </div>

                            <Button type="submit" disabled={processing}>
                                Save payment controls
                            </Button>
                        </CardContent>
                    </Card>
                </form>
            </div>
        </AdminLayout>
    );
}
