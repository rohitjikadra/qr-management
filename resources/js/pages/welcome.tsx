import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import MarketingLayout from '@/layouts/marketing-layout';
import { type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import {
    BarChart3,
    Check,
    Download,
    Link2,
    Pencil,
    QrCode,
    ScanLine,
    Shield,
    Zap,
    Info,
} from 'lucide-react';

interface PlanCard {
    slug: string;
    name: string;
    price: number;
    discounted_price?: number;
    has_discount?: boolean;
    currency: string;
    billing_cycle: 'free' | 'monthly' | 'yearly';
    limits: Record<string, number | boolean>;
}

interface Props {
    plans: PlanCard[];
    billing_discount_percent?: number | null;
}

const features = [
    {
        icon: QrCode,
        title: '7 QR types',
        description: 'URL, WhatsApp, Email, Phone, WiFi, vCard, and plain text — all from one dashboard.',
    },
    {
        icon: Link2,
        title: 'Dynamic QR codes',
        description: 'Change the destination anytime without reprinting. Perfect for menus, campaigns, and links.',
    },
    {
        icon: BarChart3,
        title: 'Scan analytics',
        description: 'See total scans, daily trends, and monthly usage. Know what is working.',
    },
    {
        icon: Pencil,
        title: 'Easy management',
        description: 'Pause, edit, rename, and organize all your QR codes from a clean dashboard.',
    },
    {
        icon: Download,
        title: 'High-quality downloads',
        description: 'Export PNG in multiple sizes. Pro users get SVG for print-ready quality.',
    },
    {
        icon: Shield,
        title: 'Built-in safety',
        description: 'Abuse reporting, domain blocklists, and admin moderation keep the platform trustworthy.',
    },
];

const steps = [
    { step: '1', title: 'Create your QR', description: 'Pick a type, add your content, choose static or dynamic.' },
    { step: '2', title: 'Download & share', description: 'Download PNG or copy your short link. Print it anywhere.' },
    { step: '3', title: 'Track & update', description: 'Watch scans in real time. Edit dynamic QRs anytime.' },
];

const faqs = [
    {
        q: 'What is the difference between static and dynamic QR?',
        a: 'Static QR codes bake the content directly into the image — great for WiFi, vCard, or fixed links. Dynamic QR codes point to a short link you control, so you can change the destination and see scan analytics.',
    },
    {
        q: 'Will my QR codes stop working if I hit scan limits?',
        a: 'No. Redirects always work. On the Free plan, analytics visibility is limited after your monthly scan quota — your QR still redirects normally.',
    },
    {
        q: 'Which QR types support dynamic mode?',
        a: 'In V1, dynamic QR is available for URL and WhatsApp types. Other types are static-only.',
    },
    {
        q: 'Can I cancel my Pro subscription?',
        a: 'Yes. Cancel anytime from Billing. You keep Pro access until the end of your billing period.',
    },
    {
        q: 'Do you store my QR images on your servers?',
        a: 'No. QR images are generated on demand when you download them. We store your content and settings, not image files.',
    },
];

function formatPrice(plan: PlanCard): string {
    if (plan.billing_cycle === 'free') {
        return 'Free';
    }

    const suffix = plan.billing_cycle === 'yearly' ? '/year' : '/month';
    const amount = plan.has_discount ? plan.discounted_price! : plan.price;

    return `₹${amount.toLocaleString('en-IN')}${suffix}`;
}

function planHighlight(plan: PlanCard): string {
    const dynamic = Number(plan.limits.dynamic_qr);
    const scans = Number(plan.limits.scans_per_month);

    if (plan.billing_cycle === 'free') {
        return `${dynamic} dynamic QRs · ${scans} scans/mo analytics`;
    }

    return 'Unlimited dynamic QRs · Full analytics';
}

export default function Welcome({ plans, billing_discount_percent: pageDiscount }: Props) {
    const { auth, billing_discount_percent: sharedDiscount } = usePage<SharedData>().props;
    const billingDiscount = pageDiscount ?? sharedDiscount ?? auth.user?.billing_discount_percent ?? null;

    return (
        <MarketingLayout>
            <Head title="QR Codes Made Simple">
                <meta
                    head-key="description"
                    name="description"
                    content="Create, manage, and track QR codes with dynamic links, scan analytics, and easy downloads. Start free."
                />
                <meta property="og:title" content="QR Manager — QR Codes Made Simple" />
                <meta
                    property="og:description"
                    content="Create dynamic QR codes, track scans, and manage everything from one dashboard."
                />
                <meta property="og:type" content="website" />
            </Head>

            {/* Hero */}
            <section className="relative overflow-hidden">
                <div className="from-primary/5 absolute inset-0 -z-10 bg-gradient-to-b to-transparent" />
                <div className="mx-auto flex max-w-6xl flex-col items-center gap-8 px-4 py-16 text-center md:py-24">
                    <Badge variant="secondary" className="gap-1">
                        <Zap className="size-3" />
                        Free plan — no credit card required
                    </Badge>
                    <h1 className="max-w-3xl text-4xl font-bold tracking-tight md:text-5xl lg:text-6xl">
                        QR codes you can{' '}
                        <span className="text-primary">create, track,</span> and update
                    </h1>
                    <p className="text-muted-foreground max-w-2xl text-lg">
                        Build static or dynamic QR codes in minutes. Edit links without reprinting, monitor scans, and
                        download print-ready images — all from one dashboard.
                    </p>
                    <div className="flex flex-wrap justify-center gap-3">
                        <Button size="lg" asChild>
                            <Link href={auth.user ? route('qr.create') : route('register')}>
                                {auth.user ? 'Create a QR code' : 'Start for free'}
                            </Link>
                        </Button>
                        <Button size="lg" variant="outline" asChild>
                            <a href="#pricing">View pricing</a>
                        </Button>
                    </div>
                    <div className="text-muted-foreground flex flex-wrap justify-center gap-6 text-sm">
                        <span className="flex items-center gap-1.5">
                            <Check className="text-primary size-4" /> 7 QR types
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Check className="text-primary size-4" /> Dynamic URL & WhatsApp
                        </span>
                        <span className="flex items-center gap-1.5">
                            <Check className="text-primary size-4" /> Scan analytics
                        </span>
                    </div>
                </div>
            </section>

            {/* Features */}
            <section id="features" className="border-t bg-muted/20 py-16 md:py-20">
                <div className="mx-auto max-w-6xl px-4">
                    <div className="mb-12 text-center">
                        <h2 className="text-3xl font-bold">Everything you need for QR management</h2>
                        <p className="text-muted-foreground mt-2">Not just a generator — a full platform to manage your codes.</p>
                    </div>
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {features.map((f) => (
                            <Card key={f.title} className="border-0 bg-background shadow-sm">
                                <CardHeader>
                                    <div className="bg-primary/10 text-primary mb-2 flex size-10 items-center justify-center rounded-lg">
                                        <f.icon className="size-5" />
                                    </div>
                                    <CardTitle className="text-base">{f.title}</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <p className="text-muted-foreground text-sm">{f.description}</p>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                </div>
            </section>

            {/* How it works */}
            <section id="how-it-works" className="py-16 md:py-20">
                <div className="mx-auto max-w-6xl px-4">
                    <div className="mb-12 text-center">
                        <h2 className="text-3xl font-bold">How it works</h2>
                        <p className="text-muted-foreground mt-2">Three steps from idea to tracked QR code.</p>
                    </div>
                    <div className="grid gap-8 md:grid-cols-3">
                        {steps.map((s) => (
                            <div key={s.step} className="text-center">
                                <div className="bg-primary text-primary-foreground mx-auto mb-4 flex size-10 items-center justify-center rounded-full text-lg font-bold">
                                    {s.step}
                                </div>
                                <h3 className="mb-2 font-semibold">{s.title}</h3>
                                <p className="text-muted-foreground text-sm">{s.description}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Pricing preview */}
            <section id="pricing" className="border-t bg-muted/20 py-16 md:py-20">
                <div className="mx-auto max-w-6xl px-4">
                    <div className="mb-12 text-center">
                        <h2 className="text-3xl font-bold">Simple pricing</h2>
                        <p className="text-muted-foreground mt-2">Start free. Upgrade when you need more.</p>
                    </div>
                    {billingDiscount && billingDiscount > 0 && (
                        <Alert className="mb-6">
                            <Info className="size-4" />
                            <AlertDescription>
                                Your account has a {billingDiscount}% discount on paid plans (prices below).
                            </AlertDescription>
                        </Alert>
                    )}
                    <div className="grid gap-6 md:grid-cols-3">
                        {plans.map((plan) => (
                            <Card key={plan.slug} className={plan.billing_cycle === 'yearly' ? 'border-primary relative' : ''}>
                                {plan.billing_cycle === 'yearly' && (
                                    <Badge className="absolute -top-2.5 left-1/2 -translate-x-1/2">Best value</Badge>
                                )}
                                <CardHeader>
                                    <CardTitle>{plan.name}</CardTitle>
                                    <div>
                                        <p className="text-2xl font-bold">{formatPrice(plan)}</p>
                                        {plan.has_discount && plan.price > 0 && (
                                            <p className="text-muted-foreground text-sm line-through">
                                                ₹{plan.price.toLocaleString('en-IN')}
                                                {plan.billing_cycle === 'yearly' ? '/year' : '/month'}
                                            </p>
                                        )}
                                    </div>
                                    <p className="text-muted-foreground text-sm">{planHighlight(plan)}</p>
                                </CardHeader>
                                <CardContent>
                                    <Button className="w-full" variant={plan.billing_cycle === 'free' ? 'default' : 'outline'} asChild>
                                        <Link href={route('pricing')}>
                                            {plan.billing_cycle === 'free' ? 'Get started' : 'See plan'}
                                </Link>
                                    </Button>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                    <p className="text-muted-foreground mt-6 text-center text-sm">
                        <Link href={route('pricing')} className="text-primary underline-offset-4 hover:underline">
                            Compare all features &rarr;
                                </Link>
                    </p>
                </div>
            </section>

            {/* FAQ */}
            <section id="faq" className="py-16 md:py-20">
                <div className="mx-auto max-w-2xl px-4">
                    <div className="mb-10 text-center">
                        <h2 className="text-3xl font-bold">Frequently asked questions</h2>
                        </div>
                    <div className="space-y-3">
                        {faqs.map((faq) => (
                            <details key={faq.q} className="group border-b pb-3">
                                <summary className="flex cursor-pointer list-none items-center justify-between py-2 font-medium [&::-webkit-details-marker]:hidden">
                                    {faq.q}
                                    <ScanLine className="text-muted-foreground size-4 shrink-0 opacity-0 transition-opacity group-open:opacity-100" />
                                </summary>
                                <p className="text-muted-foreground pb-2 text-sm">{faq.a}</p>
                            </details>
                        ))}
                        </div>
                </div>
            </section>

            {/* CTA */}
            <section className="border-t bg-primary/5 py-16">
                <div className="mx-auto max-w-2xl px-4 text-center">
                    <h2 className="text-2xl font-bold md:text-3xl">Ready to create your first QR?</h2>
                    <p className="text-muted-foreground mt-2">Join free today. No credit card needed.</p>
                    <Button size="lg" className="mt-6" asChild>
                        <Link href={auth.user ? route('qr.create') : route('register')}>
                            {auth.user ? 'Go to dashboard' : 'Create free account'}
                        </Link>
                    </Button>
            </div>
            </section>
        </MarketingLayout>
    );
}
