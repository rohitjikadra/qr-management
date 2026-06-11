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
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Check, Copy, Download, Lock, Pause, Pencil, Play, ScanLine, Trash2 } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';
import { useState } from 'react';
import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

interface QrDetail {
    id: number;
    name: string;
    type: string;
    type_label: string;
    content: Record<string, unknown>;
    is_dynamic: boolean;
    status: 'active' | 'paused';
    admin_locked: boolean;
    frozen: boolean;
    scan_count: number;
    last_scanned_at: string | null;
    payload: string;
    redirect_url: string | null;
    can_svg: boolean;
    created_at: string;
}

interface Analytics {
    range: number;
    requested_range: number;
    history_limit_days: number;
    series: { date: string; scans: number }[];
    scans_today: number;
    scans_this_week: number;
    scans_this_month: number;
}

export default function QrShow({ qr, analytics }: { qr: QrDetail; analytics: Analytics | null }) {
    const [copied, setCopied] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'QR Codes', href: '/qr' },
        { title: qr.name, href: `/qr/${qr.id}` },
    ];

    const copyLink = async () => {
        if (!qr.redirect_url) return;
        await navigator.clipboard.writeText(qr.redirect_url);
        setCopied(true);
        setTimeout(() => setCopied(false), 1500);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={qr.name} />
            <div className="mx-auto flex w-full max-w-4xl flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <h1 className="text-xl font-semibold">{qr.name}</h1>
                        <Badge variant="secondary">{qr.type_label}</Badge>
                        <Badge variant={qr.is_dynamic ? 'default' : 'outline'}>{qr.is_dynamic ? 'Dynamic' : 'Static'}</Badge>
                        {qr.status === 'paused' && <Badge variant="destructive">Paused</Badge>}
                        {qr.admin_locked && (
                            <Badge variant="destructive">
                                <Lock className="size-3" /> Locked
                            </Badge>
                        )}
                    </div>
                    <div className="flex gap-2">
                        {!qr.admin_locked && !qr.frozen && (
                            <>
                                <Button variant="outline" size="sm" asChild>
                                    <Link href={`/qr/${qr.id}/edit`}>
                                        <Pencil className="size-4" /> Edit
                                    </Link>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => router.post(`/qr/${qr.id}/toggle-status`)}
                                >
                                    {qr.status === 'active' ? (
                                        <>
                                            <Pause className="size-4" /> Pause
                                        </>
                                    ) : (
                                        <>
                                            <Play className="size-4" /> Activate
                                        </>
                                    )}
                                </Button>
                            </>
                        )}
                        <Dialog>
                            <DialogTrigger asChild>
                                <Button variant="destructive" size="sm">
                                    <Trash2 className="size-4" /> Delete
                                </Button>
                            </DialogTrigger>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Delete this QR code?</DialogTitle>
                                    <DialogDescription>
                                        {qr.is_dynamic
                                            ? 'Anyone scanning this QR will see a "not found" page. This cannot be undone and the short link will never be reused.'
                                            : 'This will remove the QR from your dashboard. Printed copies will keep working since the content is baked in.'}
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <Button variant="destructive" onClick={() => router.delete(`/qr/${qr.id}`)}>
                                        Delete permanently
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-[auto_1fr]">
                    <Card>
                        <CardContent className="flex flex-col items-center gap-4 p-6">
                            <div className="rounded-xl border bg-white p-4">
                                <QRCodeSVG value={qr.payload || ' '} size={220} />
                            </div>
                            <div className="flex flex-wrap justify-center gap-2">
                                {[256, 512, 1024].map((size) => (
                                    <Button key={size} variant="outline" size="sm" asChild>
                                        <a href={`/qr/${qr.id}/download?format=png&size=${size}`}>
                                            <Download className="size-4" /> PNG {size}
                                        </a>
                                    </Button>
                                ))}
                                {qr.can_svg ? (
                                    <Button variant="outline" size="sm" asChild>
                                        <a href={`/qr/${qr.id}/download?format=svg&size=512`}>
                                            <Download className="size-4" /> SVG
                                        </a>
                                    </Button>
                                ) : (
                                    <Button variant="outline" size="sm" disabled title="SVG download is a Pro feature">
                                        <Lock className="size-4" /> SVG (Pro)
                                    </Button>
                                )}
                            </div>
                            {qr.redirect_url && (
                                <Button variant="secondary" size="sm" onClick={copyLink}>
                                    {copied ? <Check className="size-4" /> : <Copy className="size-4" />}
                                    {copied ? 'Copied!' : 'Copy short link'}
                                </Button>
                            )}
                        </CardContent>
                    </Card>

                    <div className="flex flex-col gap-4">
                        {qr.is_dynamic && analytics ? (
                            <Card>
                                <CardHeader className="flex flex-row items-center justify-between">
                                    <CardTitle className="flex items-center gap-2 text-base">
                                        <ScanLine className="size-4" /> Analytics
                                    </CardTitle>
                                    <div className="flex gap-1">
                                        {[7, 30, 90].map((days) => {
                                            const locked =
                                                analytics.history_limit_days !== -1 && days > analytics.history_limit_days;
                                            return (
                                                <Button
                                                    key={days}
                                                    variant={analytics.range === days ? 'default' : 'outline'}
                                                    size="sm"
                                                    disabled={locked}
                                                    title={locked ? 'Upgrade to Pro for longer history' : undefined}
                                                    onClick={() =>
                                                        router.get(
                                                            `/qr/${qr.id}`,
                                                            { range: days },
                                                            { preserveState: true, preserveScroll: true },
                                                        )
                                                    }
                                                >
                                                    {locked && <Lock className="size-3" />}
                                                    {days}d
                                                </Button>
                                            );
                                        })}
                                    </div>
                                </CardHeader>
                                <CardContent className="flex flex-col gap-4">
                                    <div className="grid grid-cols-2 gap-4 sm:grid-cols-4">
                                        <div>
                                            <p className="text-2xl font-semibold">{qr.scan_count}</p>
                                            <p className="text-muted-foreground text-xs">Total scans</p>
                                        </div>
                                        <div>
                                            <p className="text-2xl font-semibold">{analytics.scans_today}</p>
                                            <p className="text-muted-foreground text-xs">Today</p>
                                        </div>
                                        <div>
                                            <p className="text-2xl font-semibold">{analytics.scans_this_week}</p>
                                            <p className="text-muted-foreground text-xs">This week</p>
                                        </div>
                                        <div>
                                            <p className="text-2xl font-semibold">{analytics.scans_this_month}</p>
                                            <p className="text-muted-foreground text-xs">This month</p>
                                        </div>
                                    </div>
                                    <div className="h-48">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <AreaChart data={analytics.series} margin={{ top: 4, right: 4, bottom: 0, left: -20 }}>
                                                <defs>
                                                    <linearGradient id="scanFill" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="0%" stopColor="currentColor" stopOpacity={0.25} />
                                                        <stop offset="100%" stopColor="currentColor" stopOpacity={0} />
                                                    </linearGradient>
                                                </defs>
                                                <CartesianGrid strokeDasharray="3 3" vertical={false} className="stroke-muted" />
                                                <XAxis
                                                    dataKey="date"
                                                    tick={{ fontSize: 11 }}
                                                    tickFormatter={(d: string) => d.slice(5)}
                                                    tickLine={false}
                                                    axisLine={false}
                                                />
                                                <YAxis tick={{ fontSize: 11 }} tickLine={false} axisLine={false} allowDecimals={false} />
                                                <Tooltip
                                                    contentStyle={{ borderRadius: 8, fontSize: 12 }}
                                                    labelFormatter={(d) => `Date: ${d}`}
                                                />
                                                <Area
                                                    type="monotone"
                                                    dataKey="scans"
                                                    className="text-primary"
                                                    stroke="currentColor"
                                                    strokeWidth={2}
                                                    fill="url(#scanFill)"
                                                />
                                            </AreaChart>
                                        </ResponsiveContainer>
                                    </div>
                                    {analytics.history_limit_days !== -1 && (
                                        <p className="text-muted-foreground text-xs">
                                            Free plan shows {analytics.history_limit_days} days of history.{' '}
                                            <Link href="/billing" className="underline">
                                                Upgrade to Pro
                                            </Link>{' '}
                                            for full history.
                                        </p>
                                    )}
                                </CardContent>
                            </Card>
                        ) : (
                            <Card>
                                <CardContent className="text-muted-foreground p-6 text-sm">
                                    Scan tracking is available on <strong>dynamic</strong> QR codes.
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle className="text-base">Details</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col gap-2 text-sm">
                                {qr.redirect_url && (
                                    <div className="flex justify-between gap-4">
                                        <span className="text-muted-foreground">Short link</span>
                                        <span className="truncate font-mono text-xs">{qr.redirect_url}</span>
                                    </div>
                                )}
                                <div className="flex justify-between gap-4">
                                    <span className="text-muted-foreground">Created</span>
                                    <span>{qr.created_at}</span>
                                </div>
                                {Object.entries(qr.content)
                                    .filter(([, v]) => v !== '' && v !== null && v !== false)
                                    .map(([k, v]) => (
                                        <div key={k} className="flex justify-between gap-4">
                                            <span className="text-muted-foreground capitalize">{k.replace(/_/g, ' ')}</span>
                                            <span className="max-w-64 truncate">{String(v)}</span>
                                        </div>
                                    ))}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
