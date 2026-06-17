import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Flag, Pause, X, Ban } from 'lucide-react';
import { useState } from 'react';

interface Owner {
    id: number;
    name: string;
    email: string;
}

interface QrSummary {
    id: number;
    name: string;
    status: string;
    admin_locked: boolean;
    owner: Owner | null;
}

interface ReportItem {
    id: number;
    reason: string;
    status: string;
    created_at: string | null;
    can_ban_owner: boolean;
    qr_code: QrSummary | null;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    reports: Pagination<ReportItem>;
    filters: { search?: string | null; status: string };
    pendingCount: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'QR Reports', href: '/admin/qr-reports' },
];

const ALL = 'all';

export default function AdminQrReportsIndex({ reports, filters, pendingCount }: Props) {
    const { flash } = usePage<SharedData>().props;
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (next: Record<string, string>) => {
        const params: Record<string, string> = {
            search,
            status: filters.status ?? 'pending',
        };

        Object.assign(params, next);
        Object.keys(params).forEach((key) => {
            if (params[key] === '' || (key === 'status' && params[key] === ALL)) {
                delete params[key];
            }
        });

        router.get('/admin/qr-reports', params, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="QR Reports" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                {flash?.success && (
                    <Alert>
                        <AlertDescription>{flash.success as string}</AlertDescription>
                    </Alert>
                )}

                <div>
                    <h1 className="text-xl font-semibold">QR Reports</h1>
                    <p className="text-muted-foreground text-sm">
                        {pendingCount} pending report{pendingCount === 1 ? '' : 's'}
                    </p>
                </div>

                <div className="flex flex-wrap gap-2">
                    <form
                        className="min-w-48 flex-1"
                        onSubmit={(e) => {
                            e.preventDefault();
                            applyFilters({});
                        }}
                    >
                        <Input
                            placeholder="Search reason, QR, or owner..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </form>
                    <Select
                        value={filters.status ?? 'pending'}
                        onValueChange={(v) => applyFilters({ status: v })}
                    >
                        <SelectTrigger className="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="pending">Pending</SelectItem>
                            <SelectItem value="reviewed">Reviewed</SelectItem>
                            <SelectItem value="actioned">Actioned</SelectItem>
                            <SelectItem value={ALL}>All</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                {reports.data.length === 0 ? (
                    <Card className="flex flex-1 items-center justify-center py-16">
                        <CardContent className="flex flex-col items-center gap-3 text-center">
                            <Flag className="text-muted-foreground size-12" />
                            <p className="text-lg font-medium">No reports found</p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-4 py-3 font-medium">QR</th>
                                            <th className="px-4 py-3 font-medium">Owner</th>
                                            <th className="px-4 py-3 font-medium">Reason</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 font-medium">Reported</th>
                                            <th className="px-4 py-3 font-medium">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {reports.data.map((report) => (
                                            <tr key={report.id} className="border-b last:border-0">
                                                <td className="px-4 py-3">
                                                    {report.qr_code ? (
                                                        <Link
                                                            href={`/admin/qr-codes/${report.qr_code.id}`}
                                                            className="font-medium hover:text-primary hover:underline"
                                                        >
                                                            {report.qr_code.name}
                                                        </Link>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {report.qr_code?.owner ? (
                                                        <Link
                                                            href={`/admin/users/${report.qr_code.owner.id}`}
                                                            className="hover:text-primary hover:underline"
                                                        >
                                                            {report.qr_code.owner.email}
                                                        </Link>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td className="max-w-xs px-4 py-3">{report.reason}</td>
                                                <td className="px-4 py-3">
                                                    <Badge
                                                        variant={
                                                            report.status === 'pending' ? 'destructive' : 'outline'
                                                        }
                                                    >
                                                        {report.status}
                                                    </Badge>
                                                </td>
                                                <td className="text-muted-foreground px-4 py-3">{report.created_at}</td>
                                                <td className="px-4 py-3">
                                                    {report.status === 'pending' && (
                                                        <div className="flex flex-wrap gap-1">
                                                            <Button
                                                                variant="outline"
                                                                size="sm"
                                                                onClick={() =>
                                                                    router.post(
                                                                        `/admin/qr-reports/${report.id}/dismiss`,
                                                                    )
                                                                }
                                                            >
                                                                <X className="size-3" /> Dismiss
                                                            </Button>
                                                            <Dialog>
                                                                <DialogTrigger asChild>
                                                                    <Button variant="destructive" size="sm">
                                                                        <Pause className="size-3" /> Pause QR
                                                                    </Button>
                                                                </DialogTrigger>
                                                                <DialogContent>
                                                                    <DialogHeader>
                                                                        <DialogTitle>Pause reported QR?</DialogTitle>
                                                                        <DialogDescription>
                                                                            This will admin-lock the QR and mark the
                                                                            report as actioned.
                                                                        </DialogDescription>
                                                                    </DialogHeader>
                                                                    <DialogFooter>
                                                                        <Button
                                                                            variant="destructive"
                                                                            onClick={() =>
                                                                                router.post(
                                                                                    `/admin/qr-reports/${report.id}/pause-qr`,
                                                                                )
                                                                            }
                                                                        >
                                                                            Confirm pause
                                                                        </Button>
                                                                    </DialogFooter>
                                                                </DialogContent>
                                                            </Dialog>
                                                            {report.can_ban_owner && (
                                                                <Dialog>
                                                                    <DialogTrigger asChild>
                                                                        <Button variant="destructive" size="sm">
                                                                            <Ban className="size-3" /> Ban user
                                                                        </Button>
                                                                    </DialogTrigger>
                                                                    <DialogContent>
                                                                        <DialogHeader>
                                                                            <DialogTitle>Ban reported user?</DialogTitle>
                                                                            <DialogDescription>
                                                                                Bans {report.qr_code?.owner?.email} and
                                                                                marks this report as actioned.
                                                                            </DialogDescription>
                                                                        </DialogHeader>
                                                                        <DialogFooter>
                                                                            <Button
                                                                                variant="destructive"
                                                                                onClick={() =>
                                                                                    router.post(
                                                                                        `/admin/qr-reports/${report.id}/ban-user`,
                                                                                    )
                                                                                }
                                                                            >
                                                                                Confirm ban
                                                                            </Button>
                                                                        </DialogFooter>
                                                                    </DialogContent>
                                                                </Dialog>
                                                            )}
                                                        </div>
                                                    )}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {reports.links.length > 3 && (
                    <div className="flex flex-wrap justify-center gap-1">
                        {reports.links.map((link, i) =>
                            link.url ? (
                                <Button key={i} variant={link.active ? 'default' : 'outline'} size="sm" asChild>
                                    <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                </Button>
                            ) : (
                                <Button
                                    key={i}
                                    variant="outline"
                                    size="sm"
                                    disabled
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ),
                        )}
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}
