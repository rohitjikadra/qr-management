import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, CalendarPlus, XCircle } from 'lucide-react';
import { useState, type FormEventHandler } from 'react';

interface UserSummary {
    id: number;
    name: string;
    email: string;
}

interface SubscriptionDetail {
    id: number;
    status: string;
    gateway: string;
    gateway_subscription_id: string;
    is_complimentary: boolean;
    admin_note: string | null;
    starts_at: string | null;
    expires_at: string | null;
    cancelled_at: string | null;
    created_at: string | null;
    user: UserSummary | null;
    plan: { id: number; name: string; slug: string } | null;
    granted_by: UserSummary | null;
}

interface PaymentRow {
    id: number;
    invoice_number: string | null;
    amount: number;
    currency: string;
    status: string;
    paid_at: string | null;
}

interface Props {
    subscription: SubscriptionDetail;
    payments: PaymentRow[];
    canExtend: boolean;
    canRevoke: boolean;
}

function DetailRow({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="text-sm font-medium break-all sm:text-right">{value || '—'}</dd>
        </div>
    );
}

export default function AdminSubscriptionShow({ subscription, payments, canExtend, canRevoke }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [extendOpen, setExtendOpen] = useState(false);
    const [revokeOpen, setRevokeOpen] = useState(false);

    const extendForm = useForm({ days: 30, admin_note: '' });
    const revokeForm = useForm({ admin_note: '' });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin/dashboard' },
        { title: 'Subscriptions', href: '/admin/subscriptions' },
        { title: `#${subscription.id}`, href: `/admin/subscriptions/${subscription.id}` },
    ];

    const submitExtend: FormEventHandler = (e) => {
        e.preventDefault();
        extendForm.post(`/admin/subscriptions/${subscription.id}/extend`, {
            onSuccess: () => {
                setExtendOpen(false);
                extendForm.reset('admin_note');
            },
        });
    };

    const submitRevoke: FormEventHandler = (e) => {
        e.preventDefault();
        revokeForm.post(`/admin/subscriptions/${subscription.id}/revoke`, {
            onSuccess: () => setRevokeOpen(false),
        });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Subscription #${subscription.id}`} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {flash?.success && (
                    <Alert>
                        <AlertDescription>{flash.success as string}</AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/subscriptions">
                                <ArrowLeft className="size-4" /> Back
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-semibold">Subscription #{subscription.id}</h1>
                            <p className="text-muted-foreground text-sm">{subscription.plan?.name ?? 'Unknown plan'}</p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Badge variant="outline">{subscription.status}</Badge>
                        <Badge variant="secondary">{subscription.gateway}</Badge>
                        {subscription.is_complimentary && <Badge>Complimentary</Badge>}
                        {canExtend && (
                            <Dialog open={extendOpen} onOpenChange={setExtendOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        <CalendarPlus className="size-4" /> Extend
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <form onSubmit={submitExtend} className="space-y-4">
                                        <DialogHeader>
                                            <DialogTitle>Extend manual subscription</DialogTitle>
                                        </DialogHeader>
                                        <div className="grid gap-2">
                                            <Label>Days to add</Label>
                                            <Select
                                                value={String(extendForm.data.days)}
                                                onValueChange={(v) => extendForm.setData('days', Number(v))}
                                            >
                                                <SelectTrigger>
                                                    <SelectValue />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="7">7 days</SelectItem>
                                                    <SelectItem value="14">14 days</SelectItem>
                                                    <SelectItem value="30">30 days</SelectItem>
                                                    <SelectItem value="90">90 days</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="extend_note">Admin note</Label>
                                            <textarea
                                                id="extend_note"
                                                rows={3}
                                                className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
                                                value={extendForm.data.admin_note}
                                                onChange={(e) => extendForm.setData('admin_note', e.target.value)}
                                                required
                                            />
                                            <InputError message={extendForm.errors.admin_note} />
                                        </div>
                                        <DialogFooter>
                                            <Button type="submit" disabled={extendForm.processing}>
                                                Extend
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        )}
                        {canRevoke && (
                            <Dialog open={revokeOpen} onOpenChange={setRevokeOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="destructive" size="sm">
                                        <XCircle className="size-4" /> Revoke
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <form onSubmit={submitRevoke} className="space-y-4">
                                        <DialogHeader>
                                            <DialogTitle>Revoke manual access?</DialogTitle>
                                            <DialogDescription>
                                                User will lose Pro benefits from this complimentary subscription.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="grid gap-2">
                                            <Label htmlFor="revoke_note">Reason</Label>
                                            <textarea
                                                id="revoke_note"
                                                rows={3}
                                                className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
                                                value={revokeForm.data.admin_note}
                                                onChange={(e) => revokeForm.setData('admin_note', e.target.value)}
                                                required
                                            />
                                            <InputError message={revokeForm.errors.admin_note} />
                                        </div>
                                        <DialogFooter>
                                            <Button type="submit" variant="destructive" disabled={revokeForm.processing}>
                                                Revoke access
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Details</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <dl className="space-y-3">
                                <DetailRow label="User" value={subscription.user?.email} />
                                <DetailRow label="Gateway ID" value={subscription.gateway_subscription_id} />
                                <DetailRow label="Starts" value={subscription.starts_at} />
                                <DetailRow label="Expires" value={subscription.expires_at} />
                                <DetailRow label="Cancelled" value={subscription.cancelled_at} />
                                <DetailRow label="Granted by" value={subscription.granted_by?.email} />
                            </dl>
                            {subscription.user && (
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/admin/users/${subscription.user.id}`}>View user</Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Admin notes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <pre className="bg-muted max-h-64 overflow-auto rounded-lg border p-3 text-xs whitespace-pre-wrap">
                                {subscription.admin_note || 'No admin notes.'}
                            </pre>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Related payments</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {payments.length === 0 ? (
                            <p className="text-muted-foreground text-sm">No payments linked.</p>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-2 py-2 font-medium">Invoice</th>
                                            <th className="px-2 py-2 font-medium">Amount</th>
                                            <th className="px-2 py-2 font-medium">Status</th>
                                            <th className="px-2 py-2 font-medium">Paid</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {payments.map((payment) => (
                                            <tr key={payment.id} className="border-b last:border-0">
                                                <td className="px-2 py-2">
                                                    <Link
                                                        href={`/admin/payments/${payment.id}`}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {payment.invoice_number ?? `#${payment.id}`}
                                                    </Link>
                                                </td>
                                                <td className="px-2 py-2">
                                                    {payment.currency} {payment.amount}
                                                </td>
                                                <td className="px-2 py-2">{payment.status}</td>
                                                <td className="text-muted-foreground px-2 py-2">
                                                    {payment.paid_at ?? '—'}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
