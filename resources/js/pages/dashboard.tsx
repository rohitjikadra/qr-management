import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { Activity, MailWarning, Plus, QrCode as QrCodeIcon, ScanLine, TrendingUp } from 'lucide-react';
import { QRCodeSVG } from 'qrcode.react';

interface DashboardProps {
    stats: {
        total_qr: number;
        active_qr: number;
        total_scans: number;
        scans_this_month: number;
    };
    plan: {
        name: string;
        is_free: boolean;
        scans_per_month: number;
    };
    recentQrs: {
        id: number;
        name: string;
        type_label: string;
        is_dynamic: boolean;
        status: string;
        scan_count: number;
        payload: string;
    }[];
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

export default function Dashboard({ stats, plan, recentQrs }: DashboardProps) {
    const { auth } = usePage<SharedData>().props;
    const verified = Boolean(auth.user.email_verified_at);

    const scanLimitPct =
        plan.is_free && plan.scans_per_month > 0
            ? Math.min(100, Math.round((stats.scans_this_month / plan.scans_per_month) * 100))
            : null;

    const statCards = [
        { label: 'Total QR Codes', value: stats.total_qr, icon: QrCodeIcon },
        { label: 'Active QRs', value: stats.active_qr, icon: Activity },
        { label: 'Total Scans', value: stats.total_scans, icon: ScanLine },
        { label: 'Scans This Month', value: stats.scans_this_month, icon: TrendingUp },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {!verified && (
                    <Alert>
                        <MailWarning className="size-4" />
                        <AlertDescription>
                            Verify your email to unlock <strong>dynamic QR codes</strong> with editing and scan tracking.{' '}
                            <Link href="/verify-email" className="underline">
                                Resend verification email
                            </Link>
                        </AlertDescription>
                    </Alert>
                )}

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="flex items-center gap-2">
                        <h1 className="text-xl font-semibold">Dashboard</h1>
                        <Badge variant={plan.is_free ? 'secondary' : 'default'}>{plan.name} plan</Badge>
                    </div>
                    <div className="flex gap-2">
                        {plan.is_free && (
                            <Button variant="outline" asChild>
                                <Link href="/billing">Upgrade to Pro</Link>
                            </Button>
                        )}
                        <Button asChild>
                            <Link href="/qr/create">
                                <Plus className="size-4" /> Create QR
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {statCards.map(({ label, value, icon: Icon }) => (
                        <Card key={label}>
                            <CardContent className="flex items-center gap-4 p-5">
                                <div className="bg-muted rounded-lg p-2.5">
                                    <Icon className="size-5" />
                                </div>
                                <div>
                                    <p className="text-2xl font-semibold">{value.toLocaleString()}</p>
                                    <p className="text-muted-foreground text-sm">{label}</p>
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {scanLimitPct !== null && (
                    <Card>
                        <CardContent className="p-5">
                            <div className="mb-2 flex justify-between text-sm">
                                <span>
                                    Monthly scan analytics — {stats.scans_this_month} / {plan.scans_per_month}
                                </span>
                                <span className="text-muted-foreground">{scanLimitPct}%</span>
                            </div>
                            <div className="bg-muted h-2 overflow-hidden rounded-full">
                                <div
                                    className={`h-full rounded-full ${scanLimitPct >= 100 ? 'bg-destructive' : 'bg-primary'}`}
                                    style={{ width: `${scanLimitPct}%` }}
                                />
                            </div>
                            {scanLimitPct >= 100 && (
                                <p className="text-muted-foreground mt-2 text-xs">
                                    Your QRs keep working — upgrade to Pro to see analytics beyond the free limit.
                                </p>
                            )}
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle className="text-base">Recent QR Codes</CardTitle>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/qr">View all</Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {recentQrs.length === 0 ? (
                            <div className="flex flex-col items-center gap-3 py-10 text-center">
                                <QrCodeIcon className="text-muted-foreground size-10" />
                                <p className="font-medium">No QR codes yet</p>
                                <p className="text-muted-foreground text-sm">Create your first QR code and start sharing.</p>
                                <Button asChild>
                                    <Link href="/qr/create">
                                        <Plus className="size-4" /> Create your first QR
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="flex flex-col divide-y">
                                {recentQrs.map((qr) => (
                                    <Link
                                        key={qr.id}
                                        href={`/qr/${qr.id}`}
                                        className="hover:bg-muted/50 -mx-2 flex items-center gap-3 rounded-md px-2 py-3"
                                    >
                                        <div className="rounded border bg-white p-1">
                                            <QRCodeSVG value={qr.payload || ' '} size={40} />
                                        </div>
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate text-sm font-medium">{qr.name}</p>
                                            <p className="text-muted-foreground text-xs">{qr.type_label}</p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {qr.status === 'paused' && <Badge variant="destructive">Paused</Badge>}
                                            <Badge variant={qr.is_dynamic ? 'default' : 'outline'}>
                                                {qr.is_dynamic ? 'Dynamic' : 'Static'}
                                            </Badge>
                                            {qr.is_dynamic && (
                                                <span className="text-muted-foreground flex items-center gap-1 text-xs">
                                                    <ScanLine className="size-3" /> {qr.scan_count}
                                                </span>
                                            )}
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
