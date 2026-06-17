import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AdminLayout from '@/layouts/admin-layout';
import { type AdminStats, type SignupsChartData } from '@/types/admin';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { QrCode, ScanLine, TrendingUp, Users } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Line,
    LineChart,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

interface Props {
    stats: AdminStats;
    signupsChart: SignupsChartData;
    qrTypeChart: SignupsChartData;
}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/admin/dashboard' }];

function formatInr(amount: number): string {
    return `₹${Math.round(amount).toLocaleString('en-IN')}`;
}

export default function AdminDashboard({ stats, signupsChart, qrTypeChart }: Props) {
    const growthPrefix = stats.revenue_growth_percent >= 0 ? '+' : '';

    const signupsData = signupsChart.labels.map((label, i) => ({
        label,
        signups: signupsChart.values[i] ?? 0,
    }));

    const qrTypeData = qrTypeChart.labels.map((label, i) => ({
        label,
        count: qrTypeChart.values[i] ?? 0,
    }));

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Admin Dashboard</h1>
                    <p className="text-muted-foreground text-sm">Platform overview and key metrics.</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
                            <Users className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_users}</div>
                            <p className="text-muted-foreground text-xs">{stats.new_users_30d} new in last 30 days</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Paid Users</CardTitle>
                            <TrendingUp className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.paid_users}</div>
                            <p className="text-muted-foreground text-xs">Active subscriptions</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">MRR</CardTitle>
                            <TrendingUp className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatInr(stats.mrr)}</div>
                            <p className="text-muted-foreground text-xs">Monthly recurring revenue</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Revenue (Month)</CardTitle>
                            <TrendingUp className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{formatInr(stats.revenue_this_month)}</div>
                            <p className="text-muted-foreground text-xs">
                                {growthPrefix}
                                {stats.revenue_growth_percent}% vs last month
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">QR Codes</CardTitle>
                            <QrCode className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_qr_codes}</div>
                            <p className="text-muted-foreground text-xs">{stats.active_qr_codes} active</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Total Scans</CardTitle>
                            <ScanLine className="text-muted-foreground size-4" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total_scans.toLocaleString('en-IN')}</div>
                            <p className="text-muted-foreground text-xs">
                                {stats.scans_this_month.toLocaleString('en-IN')} this month
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">New signups (30 days)</CardTitle>
                        </CardHeader>
                        <CardContent className="h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={signupsData}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                    <XAxis dataKey="label" tick={{ fontSize: 11 }} interval="preserveStartEnd" />
                                    <YAxis allowDecimals={false} tick={{ fontSize: 11 }} width={32} />
                                    <Tooltip />
                                    <Line
                                        type="monotone"
                                        dataKey="signups"
                                        stroke="hsl(var(--primary))"
                                        strokeWidth={2}
                                        dot={false}
                                    />
                                </LineChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">QR type distribution</CardTitle>
                        </CardHeader>
                        <CardContent className="h-64">
                            {qrTypeData.length === 0 ? (
                                <p className="text-muted-foreground flex h-full items-center justify-center text-sm">
                                    No QR codes yet.
                                </p>
                            ) : (
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={qrTypeData}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-muted" />
                                        <XAxis dataKey="label" tick={{ fontSize: 11 }} />
                                        <YAxis allowDecimals={false} tick={{ fontSize: 11 }} width={32} />
                                        <Tooltip />
                                        <Bar dataKey="count" fill="hsl(var(--primary))" radius={[4, 4, 0, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AdminLayout>
    );
}
