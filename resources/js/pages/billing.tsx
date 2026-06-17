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
import AppLayout from '@/layouts/app-layout';
import { openRazorpayCheckout, type CheckoutData } from '@/lib/razorpay';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CreditCard, Info, LoaderCircle } from 'lucide-react';
import { useEffect, useState } from 'react';

interface Props {
    plan: { name: string; slug: string; is_free: boolean };
    billing_discount_percent: number | null;
    billing_note: string | null;
    subscription: {
        status: string;
        plan_name: string;
        plan_slug: string;
        gateway?: string;
        renewal_type?: string;
        is_complimentary?: boolean;
        starts_at: string | null;
        expires_at: string | null;
        cancelled_at: string | null;
    } | null;
    payments: {
        id: number;
        invoice_number: string | null;
        amount: number;
        currency: string;
        status: string;
        paid_at: string | null;
    }[];
    razorpayConfigured: boolean;
    can_renew: boolean;
    payments_enabled: boolean;
    payments_disabled_message: string;
    billing_mode: string;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Billing', href: '/billing' }];

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    active: 'default',
    pending: 'secondary',
    grace: 'destructive',
    frozen: 'destructive',
    cancelled: 'outline',
    expired: 'outline',
};

export default function Billing({
    plan,
    billing_discount_percent: pageDiscount,
    subscription,
    payments,
    razorpayConfigured,
    can_renew,
    payments_enabled,
    payments_disabled_message,
}: Props) {
    const { billing_discount_percent: sharedDiscount, auth, flash } = usePage<
        SharedData & { flash: { checkout?: CheckoutData } }
    >().props;
    const billingDiscount = pageDiscount ?? sharedDiscount ?? auth.user?.billing_discount_percent ?? null;
    const [renewing, setRenewing] = useState(false);

    const canCancel =
        subscription &&
        subscription.renewal_type === 'autopay' &&
        ['active', 'grace'].includes(subscription.status) &&
        subscription.gateway !== 'manual';

    useEffect(() => {
        if (flash.checkout) {
            void openRazorpayCheckout(flash.checkout, () => router.reload());
            setRenewing(false);
        }
    }, [flash.checkout]);

    const renew = (planSlug: string) => {
        setRenewing(true);
        router.post('/billing/subscribe', { plan: planSlug }, { onError: () => setRenewing(false) });
    };

    const renewPlanSlug = subscription?.plan_slug ?? (plan.is_free ? 'pro_monthly' : plan.slug);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Billing" />
            <div className="mx-auto flex w-full max-w-3xl flex-col gap-4 p-4">
                <h1 className="text-xl font-semibold">Billing</h1>

                {!razorpayConfigured && (
                    <Alert>
                        <Info className="size-4" />
                        <AlertDescription>Payments are not configured yet (test environment).</AlertDescription>
                    </Alert>
                )}

                {!payments_enabled && (
                    <Alert>
                        <Info className="size-4" />
                        <AlertDescription>{payments_disabled_message}</AlertDescription>
                    </Alert>
                )}

                {subscription?.status === 'grace' && (
                    <Alert variant="destructive">
                        <Info className="size-4" />
                        <AlertDescription>
                            Your plan has expired. Renew now to keep Pro features — your QR codes will keep working either way.
                        </AlertDescription>
                    </Alert>
                )}

                {subscription?.status === 'frozen' && (
                    <Alert variant="destructive">
                        <Info className="size-4" />
                        <AlertDescription>
                            Your plan is frozen. Dynamic QRs beyond the free limit are locked (still scannable). Renew to unlock
                            everything.
                        </AlertDescription>
                    </Alert>
                )}

                {subscription?.is_complimentary && (
                    <Alert>
                        <Info className="size-4" />
                        <AlertDescription>
                            You have complimentary Pro access until {subscription.expires_at}. No payment required.
                        </AlertDescription>
                    </Alert>
                )}

                {billingDiscount && billingDiscount > 0 && (
                    <Alert>
                        <Info className="size-4" />
                        <AlertDescription>
                            You have a {billingDiscount}% discount on paid plans. See discounted prices on{' '}
                            <Link href="/pricing" className="underline">
                                pricing
                            </Link>
                            .
                        </AlertDescription>
                    </Alert>
                )}

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="flex items-center gap-2 text-base">
                            <CreditCard className="size-4" /> Current Plan
                        </CardTitle>
                        {subscription && (
                            <Badge variant={statusVariant[subscription.status] ?? 'secondary'}>{subscription.status}</Badge>
                        )}
                    </CardHeader>
                    <CardContent className="flex flex-col gap-3">
                        <div className="flex items-baseline justify-between gap-3">
                            <p className="text-2xl font-semibold">{plan.name}</p>
                            {plan.is_free ? (
                                can_renew ? (
                                    <Button onClick={() => renew('pro_monthly')} disabled={renewing}>
                                        {renewing && <LoaderCircle className="size-4 animate-spin" />}
                                        Upgrade to Pro
                                    </Button>
                                ) : (
                                    <Button asChild variant={payments_enabled ? 'default' : 'outline'} disabled={!payments_enabled}>
                                        <Link href="/pricing">View plans</Link>
                                    </Button>
                                )
                            ) : can_renew ? (
                                <Button onClick={() => renew(renewPlanSlug)} disabled={renewing}>
                                    {renewing && <LoaderCircle className="size-4 animate-spin" />}
                                    Renew plan
                                </Button>
                            ) : (
                                canCancel && (
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button variant="outline">Cancel subscription</Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Cancel your subscription?</DialogTitle>
                                                <DialogDescription>
                                                    You'll keep Pro access until {subscription?.expires_at ?? 'the end of the billing period'}.
                                                    After that, dynamic QRs beyond the free limit will be locked — but every QR keeps
                                                    scanning and redirecting.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <DialogFooter>
                                                <Button variant="destructive" onClick={() => router.post('/billing/cancel')}>
                                                    Yes, cancel
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                )
                            )}
                        </div>
                        {subscription && (
                            <div className="text-muted-foreground grid grid-cols-2 gap-2 text-sm">
                                {subscription.starts_at && <span>Started: {subscription.starts_at}</span>}
                                {subscription.expires_at && (
                                    <span>Valid until: {subscription.expires_at}</span>
                                )}
                            </div>
                        )}
                        {!plan.is_free && subscription?.renewal_type !== 'autopay' && (
                            <p className="text-muted-foreground text-xs">
                                Manual renewal — no autopay. Renew before expiry to keep Pro features uninterrupted.
                            </p>
                        )}
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Payment History</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {payments.length === 0 ? (
                            <p className="text-muted-foreground py-4 text-center text-sm">No payments yet.</p>
                        ) : (
                            <div className="flex flex-col divide-y text-sm">
                                {payments.map((p) => (
                                    <div key={p.id} className="flex items-center justify-between py-2.5">
                                        <div>
                                            <p className="font-medium">{p.invoice_number ?? '—'}</p>
                                            <p className="text-muted-foreground text-xs">{p.paid_at ?? 'Pending'}</p>
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <Badge variant={p.status === 'paid' ? 'default' : p.status === 'failed' ? 'destructive' : 'secondary'}>
                                                {p.status}
                                            </Badge>
                                            <span className="font-medium">₹{p.amount.toLocaleString('en-IN')}</span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
