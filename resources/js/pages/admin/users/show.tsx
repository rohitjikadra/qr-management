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
import { Alert, AlertDescription } from '@/components/ui/alert';
import InputError from '@/components/input-error';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, Ban, CheckCircle, Pencil, CreditCard, QrCode, Percent, Gift, Mail, UserCog, Trash2 } from 'lucide-react';
import { useState, type FormEventHandler } from 'react';

interface UserDetail {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    email_verified_at: string | null;
    billing_discount_percent: number | null;
    billing_note: string | null;
    country: string | null;
    last_login_at: string | null;
    created_at: string | null;
    updated_at: string | null;
    deleted_at: string | null;
    qr_codes_count: number;
    subscriptions_count: number;
    payments_count: number;
}

interface SubscriptionSummary {
    id: number;
    plan_name: string;
    status: string;
    gateway: string;
    is_complimentary?: boolean;
    starts_at: string | null;
    expires_at: string | null;
}

interface QrSummary {
    id: number;
    name: string;
    type: string;
    type_label?: string;
    status: string;
    scan_count: number;
    is_dynamic?: boolean;
    created_at: string | null;
}

interface PaymentSummary {
    id: number;
    invoice_number: string | null;
    amount: number;
    currency: string;
    status: string;
    paid_at: string | null;
}

interface ComplimentaryPlan {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    user: UserDetail;
    activeSubscription: SubscriptionSummary | null;
    recentSubscriptions: SubscriptionSummary[];
    recentQrCodes: QrSummary[];
    subscriptions: SubscriptionSummary[];
    qrCodes: QrSummary[];
    payments: PaymentSummary[];
    canBan: boolean;
    canUnban: boolean;
    canImpersonate: boolean;
    canDelete: boolean;
    canResendVerification: boolean;
    complimentaryPlans: ComplimentaryPlan[];
}

type TabKey = 'overview' | 'qr-codes' | 'subscriptions' | 'payments';

const roleLabels: Record<string, string> = {
    user: 'User',
    admin: 'Admin',
    super_admin: 'Super Admin',
};

const NONE = '__none';

function DetailRow({ label, value }: { label: string; value: string | null | undefined }) {
    return (
        <div className="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
            <dt className="text-muted-foreground text-sm">{label}</dt>
            <dd className="text-sm font-medium sm:text-right">{value || '—'}</dd>
        </div>
    );
}

export default function AdminUserShow({
    user,
    activeSubscription,
    subscriptions,
    qrCodes,
    payments,
    canBan,
    canUnban,
    canImpersonate,
    canDelete,
    canResendVerification,
    complimentaryPlans,
}: Props) {
    const { flash } = usePage<SharedData>().props;
    const [tab, setTab] = useState<TabKey>('overview');
    const [discountOpen, setDiscountOpen] = useState(false);
    const [complimentaryOpen, setComplimentaryOpen] = useState(false);

    const discountForm = useForm({
        billing_discount_percent: user.billing_discount_percent ?? ('' as number | ''),
        billing_note: user.billing_note ?? '',
    });

    const complimentaryForm = useForm({
        plan_id: complimentaryPlans[0]?.id ?? ('' as number | ''),
        duration_days: 30,
        admin_note: '',
    });

    const submitDiscount: FormEventHandler = (e) => {
        e.preventDefault();
        discountForm.post(`/admin/users/${user.id}/discount`, {
            onSuccess: () => setDiscountOpen(false),
        });
    };

    const submitComplimentary: FormEventHandler = (e) => {
        e.preventDefault();
        complimentaryForm.post(`/admin/users/${user.id}/complimentary`, {
            onSuccess: () => {
                setComplimentaryOpen(false);
                complimentaryForm.reset('admin_note');
            },
        });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin/dashboard' },
        { title: 'Users', href: '/admin/users' },
        { title: user.name, href: `/admin/users/${user.id}` },
    ];

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={user.name} />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {flash?.success && (
                    <Alert>
                        <AlertDescription>{flash.success as string}</AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-3">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/admin/users">
                                <ArrowLeft className="size-4" /> Back
                            </Link>
                        </Button>
                        <div>
                            <h1 className="text-xl font-semibold">{user.name}</h1>
                            <p className="text-muted-foreground text-sm">{user.email}</p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/admin/users/${user.id}/edit`}>
                                <Pencil className="size-4" /> Edit
                            </Link>
                        </Button>
                        {canBan && (
                            <Dialog>
                                <DialogTrigger asChild>
                                    <Button variant="destructive" size="sm">
                                        <Ban className="size-4" /> Ban user
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <DialogHeader>
                                        <DialogTitle>Ban {user.name}?</DialogTitle>
                                        <DialogDescription>
                                            This user will not be able to log in until unbanned.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <DialogFooter>
                                        <Button
                                            variant="destructive"
                                            onClick={() => router.post(`/admin/users/${user.id}/ban`)}
                                        >
                                            Confirm ban
                                        </Button>
                                    </DialogFooter>
                                </DialogContent>
                            </Dialog>
                        )}
                        {canUnban && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.post(`/admin/users/${user.id}/unban`)}
                            >
                                <CheckCircle className="size-4" /> Unban user
                            </Button>
                        )}
                        {canResendVerification && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.post(`/admin/users/${user.id}/resend-verification`)}
                            >
                                <Mail className="size-4" /> Resend verification
                            </Button>
                        )}
                        {canImpersonate && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => router.post(`/admin/users/${user.id}/impersonate`)}
                            >
                                <UserCog className="size-4" /> Impersonate
                            </Button>
                        )}
                        {canDelete && (
                            <Button
                                variant="destructive"
                                size="sm"
                                onClick={() => {
                                    if (confirm(`Delete ${user.name}? This soft-deletes the account.`)) {
                                        router.delete(`/admin/users/${user.id}`);
                                    }
                                }}
                            >
                                <Trash2 className="size-4" /> Delete
                            </Button>
                        )}
                        <Badge variant={user.status === 'banned' ? 'destructive' : 'outline'}>{user.status}</Badge>
                        <Badge variant="secondary">{roleLabels[user.role] ?? user.role}</Badge>
                        {user.deleted_at && <Badge variant="destructive">Deleted</Badge>}
                    </div>
                </div>

                <div className="flex flex-wrap gap-2 border-b pb-2">
                    {(
                        [
                            ['overview', 'Overview'],
                            ['qr-codes', `QR Codes (${user.qr_codes_count})`],
                            ['subscriptions', `Subscriptions (${user.subscriptions_count})`],
                            ['payments', `Payments (${user.payments_count})`],
                        ] as const
                    ).map(([key, label]) => (
                        <Button
                            key={key}
                            type="button"
                            size="sm"
                            variant={tab === key ? 'default' : 'ghost'}
                            onClick={() => setTab(key)}
                        >
                            {label}
                        </Button>
                    ))}
                </div>

                {tab === 'overview' && (
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Profile</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <dl className="space-y-3">
                                <DetailRow label="Email verified" value={user.email_verified_at} />
                                <DetailRow label="Country" value={user.country} />
                                <DetailRow label="Last login" value={user.last_login_at} />
                                <DetailRow label="Registered" value={user.created_at} />
                                <DetailRow label="Updated" value={user.updated_at} />
                                {user.deleted_at && <DetailRow label="Deleted at" value={user.deleted_at} />}
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0">
                            <CardTitle className="text-base">Billing</CardTitle>
                            <div className="flex flex-wrap gap-2">
                                <Dialog open={complimentaryOpen} onOpenChange={setComplimentaryOpen}>
                                    <DialogTrigger asChild>
                                        <Button variant="outline" size="sm" disabled={complimentaryPlans.length === 0}>
                                            <Gift className="size-4" /> Grant free Pro
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <form onSubmit={submitComplimentary} className="space-y-4">
                                            <DialogHeader>
                                                <DialogTitle>Grant complimentary access</DialogTitle>
                                                <DialogDescription>
                                                    Creates a manual subscription without payment. Existing manual
                                                    subscriptions are replaced.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <div className="grid gap-2">
                                                <Label htmlFor="plan_id">Plan</Label>
                                                <Select
                                                    value={String(complimentaryForm.data.plan_id)}
                                                    onValueChange={(v) =>
                                                        complimentaryForm.setData('plan_id', Number(v))
                                                    }
                                                >
                                                    <SelectTrigger id="plan_id">
                                                        <SelectValue placeholder="Select plan" />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {complimentaryPlans.map((plan) => (
                                                            <SelectItem key={plan.id} value={String(plan.id)}>
                                                                {plan.name}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={complimentaryForm.errors.plan_id} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="duration_days">Duration</Label>
                                                <Select
                                                    value={String(complimentaryForm.data.duration_days)}
                                                    onValueChange={(v) =>
                                                        complimentaryForm.setData('duration_days', Number(v))
                                                    }
                                                >
                                                    <SelectTrigger id="duration_days">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="7">7 days</SelectItem>
                                                        <SelectItem value="14">14 days</SelectItem>
                                                        <SelectItem value="30">30 days</SelectItem>
                                                        <SelectItem value="90">90 days</SelectItem>
                                                        <SelectItem value="365">365 days</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <InputError message={complimentaryForm.errors.duration_days} />
                                            </div>
                                            <div className="grid gap-2">
                                                <Label htmlFor="admin_note">Admin note (required)</Label>
                                                <textarea
                                                    id="admin_note"
                                                    rows={3}
                                                    className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                                    value={complimentaryForm.data.admin_note}
                                                    onChange={(e) =>
                                                        complimentaryForm.setData('admin_note', e.target.value)
                                                    }
                                                    placeholder="Reason for complimentary access..."
                                                />
                                                <InputError message={complimentaryForm.errors.admin_note} />
                                            </div>
                                            <DialogFooter>
                                                <Button type="submit" disabled={complimentaryForm.processing}>
                                                    Grant access
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                                <Dialog open={discountOpen} onOpenChange={setDiscountOpen}>
                                <DialogTrigger asChild>
                                    <Button variant="outline" size="sm">
                                        <Percent className="size-4" /> Set discount
                                    </Button>
                                </DialogTrigger>
                                <DialogContent>
                                    <form onSubmit={submitDiscount} className="space-y-4">
                                        <DialogHeader>
                                            <DialogTitle>Set billing discount</DialogTitle>
                                            <DialogDescription>
                                                Shown on /pricing and /billing when this user is logged in.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <div className="grid gap-2">
                                            <Label htmlFor="billing_discount_percent">Discount %</Label>
                                            <Select
                                                value={
                                                    discountForm.data.billing_discount_percent === '' ||
                                                    discountForm.data.billing_discount_percent === null
                                                        ? NONE
                                                        : String(discountForm.data.billing_discount_percent)
                                                }
                                                onValueChange={(v) =>
                                                    discountForm.setData(
                                                        'billing_discount_percent',
                                                        v === NONE ? '' : Number(v),
                                                    )
                                                }
                                            >
                                                <SelectTrigger id="billing_discount_percent">
                                                    <SelectValue placeholder="No discount" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value={NONE}>No discount</SelectItem>
                                                    <SelectItem value="10">10%</SelectItem>
                                                    <SelectItem value="25">25%</SelectItem>
                                                    <SelectItem value="50">50%</SelectItem>
                                                    <SelectItem value="75">75%</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            <InputError message={discountForm.errors.billing_discount_percent} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="billing_note">Internal note</Label>
                                            <Input
                                                id="billing_note"
                                                value={discountForm.data.billing_note}
                                                onChange={(e) => discountForm.setData('billing_note', e.target.value)}
                                                placeholder="e.g. Early adopter, influencer deal..."
                                            />
                                            <InputError message={discountForm.errors.billing_note} />
                                        </div>
                                        <DialogFooter>
                                            <Button type="submit" disabled={discountForm.processing}>
                                                Save discount
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                            </div>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <dl className="space-y-3">
                                <DetailRow
                                    label="Discount"
                                    value={user.billing_discount_percent ? `${user.billing_discount_percent}%` : 'None'}
                                />
                                <DetailRow label="Internal note" value={user.billing_note} />
                            </dl>
                            {activeSubscription ? (
                                <div className="bg-muted/50 mt-4 rounded-lg border p-3 text-sm">
                                    <p className="font-medium">Active plan: {activeSubscription.plan_name}</p>
                                    <p className="text-muted-foreground">
                                        {activeSubscription.status} · {activeSubscription.gateway}
                                        {activeSubscription.is_complimentary ? ' · complimentary' : ''}
                                    </p>
                                    <p className="text-muted-foreground">
                                        Expires: {activeSubscription.expires_at ?? '—'}
                                    </p>
                                </div>
                            ) : (
                                <p className="text-muted-foreground text-sm">No active paid subscription.</p>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle className="text-base">Usage</CardTitle>
                        </CardHeader>
                        <CardContent className="grid grid-cols-3 gap-3 text-center">
                            <div className="rounded-lg border p-3">
                                <QrCode className="text-muted-foreground mx-auto mb-1 size-4" />
                                <p className="text-lg font-semibold">{user.qr_codes_count}</p>
                                <p className="text-muted-foreground text-xs">QR codes</p>
                            </div>
                            <div className="rounded-lg border p-3">
                                <CreditCard className="text-muted-foreground mx-auto mb-1 size-4" />
                                <p className="text-lg font-semibold">{user.subscriptions_count}</p>
                                <p className="text-muted-foreground text-xs">Subscriptions</p>
                            </div>
                            <div className="rounded-lg border p-3">
                                <CreditCard className="text-muted-foreground mx-auto mb-1 size-4" />
                                <p className="text-lg font-semibold">{user.payments_count}</p>
                                <p className="text-muted-foreground text-xs">Payments</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
                )}

                {tab === 'qr-codes' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">QR Codes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {qrCodes.length === 0 ? (
                                <p className="text-muted-foreground text-sm">No QR codes.</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b text-left">
                                                <th className="px-2 py-2 font-medium">Name</th>
                                                <th className="px-2 py-2 font-medium">Type</th>
                                                <th className="px-2 py-2 font-medium">Status</th>
                                                <th className="px-2 py-2 font-medium">Scans</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {qrCodes.map((qr) => (
                                                <tr key={qr.id} className="border-b last:border-0">
                                                    <td className="px-2 py-2">
                                                        <Link
                                                            href={`/admin/qr-codes/${qr.id}`}
                                                            className="hover:text-primary hover:underline"
                                                        >
                                                            {qr.name}
                                                        </Link>
                                                    </td>
                                                    <td className="px-2 py-2">{qr.type_label ?? qr.type}</td>
                                                    <td className="px-2 py-2">{qr.status}</td>
                                                    <td className="px-2 py-2">{qr.scan_count}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {tab === 'subscriptions' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Subscriptions</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {subscriptions.length === 0 ? (
                                <p className="text-muted-foreground text-sm">No subscriptions.</p>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b text-left">
                                                <th className="px-2 py-2 font-medium">Plan</th>
                                                <th className="px-2 py-2 font-medium">Status</th>
                                                <th className="px-2 py-2 font-medium">Gateway</th>
                                                <th className="px-2 py-2 font-medium">Expires</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {subscriptions.map((subscription) => (
                                                <tr key={subscription.id} className="border-b last:border-0">
                                                    <td className="px-2 py-2">
                                                        <Link
                                                            href={`/admin/subscriptions/${subscription.id}`}
                                                            className="hover:text-primary hover:underline"
                                                        >
                                                            {subscription.plan_name}
                                                        </Link>
                                                    </td>
                                                    <td className="px-2 py-2">{subscription.status}</td>
                                                    <td className="px-2 py-2">{subscription.gateway}</td>
                                                    <td className="text-muted-foreground px-2 py-2">
                                                        {subscription.expires_at ?? '—'}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {tab === 'payments' && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Payments</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {payments.length === 0 ? (
                                <p className="text-muted-foreground text-sm">No payments.</p>
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
                )}
            </div>
        </AdminLayout>
    );
}
