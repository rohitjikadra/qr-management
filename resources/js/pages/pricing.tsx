import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { openRazorpayCheckout, type CheckoutData } from '@/lib/razorpay';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Check, LoaderCircle, X } from 'lucide-react';
import { useEffect, useState } from 'react';

interface PlanCard {
    slug: string;
    name: string;
    price: number;
    currency: string;
    billing_cycle: 'free' | 'monthly' | 'yearly';
    limits: Record<string, number | boolean>;
}

interface Props {
    plans: PlanCard[];
    currentPlan: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Pricing', href: '/pricing' }];

function featureList(limits: Record<string, number | boolean>): { label: string; included: boolean }[] {
    const dynamicQr = Number(limits.dynamic_qr);
    const scans = Number(limits.scans_per_month);
    const history = Number(limits.analytics_history_days);

    return [
        { label: 'Unlimited static QR codes', included: true },
        { label: dynamicQr === -1 ? 'Unlimited dynamic QR codes' : `${dynamicQr} dynamic QR codes`, included: true },
        { label: scans === -1 ? 'Unlimited scan analytics' : `${scans} scans/month analytics`, included: true },
        { label: history === -1 ? 'Full analytics history' : `${history}-day analytics history`, included: true },
        { label: 'SVG downloads', included: Boolean(limits.svg_download) },
        { label: 'Custom logo & colors (coming soon)', included: Boolean(limits.custom_logo) },
        { label: 'Ad-free', included: !limits.ads },
    ];
}

export default function Pricing({ plans, currentPlan }: Props) {
    const { auth, flash } = usePage<SharedData & { flash: { checkout?: CheckoutData } }>().props;
    const [processing, setProcessing] = useState<string | null>(null);

    useEffect(() => {
        if (flash.checkout) {
            void openRazorpayCheckout(flash.checkout, () => router.visit('/billing'));
            setProcessing(null);
        }
    }, [flash.checkout]);

    const subscribe = (slug: string) => {
        setProcessing(slug);
        router.post('/billing/subscribe', { plan: slug }, { onError: () => setProcessing(null) });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pricing" />
            <div className="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
                <div className="text-center">
                    <h1 className="text-2xl font-semibold">Simple, transparent pricing</h1>
                    <p className="text-muted-foreground mt-1">Start free. Upgrade when your QRs take off.</p>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    {plans.map((plan) => {
                        const isCurrent = currentPlan === plan.slug;
                        const isYearly = plan.billing_cycle === 'yearly';

                        return (
                            <Card key={plan.slug} className={isYearly ? 'border-primary relative' : ''}>
                                {isYearly && (
                                    <Badge className="absolute -top-2.5 left-1/2 -translate-x-1/2">2 months free</Badge>
                                )}
                                <CardHeader>
                                    <CardTitle className="text-lg">{plan.name}</CardTitle>
                                    <div>
                                        <span className="text-3xl font-bold">
                                            {plan.price === 0 ? 'Free' : `₹${plan.price.toLocaleString('en-IN')}`}
                                        </span>
                                        {plan.price > 0 && (
                                            <span className="text-muted-foreground text-sm">
                                                /{isYearly ? 'year' : 'month'}
                                            </span>
                                        )}
                                    </div>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-4">
                                    <ul className="flex flex-col gap-2 text-sm">
                                        {featureList(plan.limits).map(({ label, included }) => (
                                            <li key={label} className="flex items-center gap-2">
                                                {included ? (
                                                    <Check className="size-4 text-green-600" />
                                                ) : (
                                                    <X className="text-muted-foreground size-4" />
                                                )}
                                                <span className={included ? '' : 'text-muted-foreground line-through'}>
                                                    {label}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>

                                    {isCurrent ? (
                                        <Button variant="outline" disabled>
                                            Current plan
                                        </Button>
                                    ) : plan.billing_cycle === 'free' ? (
                                        !auth.user ? (
                                            <Button variant="outline" asChild>
                                                <Link href="/register">Get started</Link>
                                            </Button>
                                        ) : (
                                            <Button variant="outline" disabled>
                                                Included
                                            </Button>
                                        )
                                    ) : !auth.user ? (
                                        <Button asChild>
                                            <Link href="/register">Start with Pro</Link>
                                        </Button>
                                    ) : (
                                        <Button onClick={() => subscribe(plan.slug)} disabled={processing !== null}>
                                            {processing === plan.slug && <LoaderCircle className="size-4 animate-spin" />}
                                            Upgrade to {plan.name}
                                        </Button>
                                    )}
                                </CardContent>
                            </Card>
                        );
                    })}
                </div>

                <p className="text-muted-foreground text-center text-xs">
                    Payments are processed securely by Razorpay (UPI Autopay & cards). 7-day no-questions refund policy.
                </p>
            </div>
        </AppLayout>
    );
}
