import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

interface UserSummary {
    id: number;
    name: string;
    email: string;
}

interface PaymentDetail {
    id: number;
    invoice_number: string | null;
    amount: number;
    currency: string;
    status: string;
    gateway: string;
    gateway_payment_id: string | null;
    gateway_order_id: string | null;
    meta_json: string;
    paid_at: string | null;
    created_at: string | null;
    user: UserSummary | null;
    subscription: { id: number; plan_name: string | null; status: string } | null;
}

interface Props {
    payment: PaymentDetail;
    canMarkRefunded: boolean;
}

function DetailRow({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="text-sm font-medium break-all sm:text-right">{value || '—'}</dd>
        </div>
    );
}

export default function AdminPaymentShow({ payment, canMarkRefunded }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin/dashboard' },
        { title: 'Payments', href: '/admin/payments' },
        { title: payment.invoice_number ?? `#${payment.id}`, href: `/admin/payments/${payment.id}` },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Payment #${payment.id}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/payments">
                                <ArrowLeft className="size-4" /> Back
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-semibold">
                                {payment.invoice_number ?? `Payment #${payment.id}`}
                            </h1>
                            <div className="mt-1 flex gap-2">
                                <Badge variant="outline">{payment.status}</Badge>
                                <Badge variant="secondary">{payment.gateway}</Badge>
                            </div>
                        </div>
                    </div>
                    {canMarkRefunded && (
                        <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => {
                                if (confirm('Mark this payment as refunded? (Local record only — no Razorpay API call.)')) {
                                    router.post(`/admin/payments/${payment.id}/refund`);
                                }
                            }}
                        >
                            Mark refunded
                        </Button>
                    )}
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Payment details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <dl className="space-y-3">
                                <DetailRow label="User" value={payment.user?.email} />
                                <DetailRow
                                    label="Amount"
                                    value={`${payment.currency} ${payment.amount.toLocaleString('en-IN')}`}
                                />
                                <DetailRow label="Gateway payment ID" value={payment.gateway_payment_id} />
                                <DetailRow label="Gateway order ID" value={payment.gateway_order_id} />
                                <DetailRow label="Paid at" value={payment.paid_at} />
                                <DetailRow label="Created" value={payment.created_at} />
                            </dl>
                            {payment.user && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/admin/users/${payment.user.id}`}>View user</Link>
                                </Button>
                            )}
                            {payment.subscription && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/admin/subscriptions/${payment.subscription.id}`}>
                                        View subscription
                                    </Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Gateway meta (raw JSON)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <pre className="bg-muted max-h-96 overflow-auto rounded-lg border p-3 text-xs">
                                {payment.meta_json}
                            </pre>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
