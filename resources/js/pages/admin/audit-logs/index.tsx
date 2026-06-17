import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ScrollText } from 'lucide-react';
import { useState } from 'react';

interface UserSummary {
    id: number;
    name: string;
    email: string;
}

interface LogItem {
    id: number;
    action: string;
    entity_type: string | null;
    entity_id: number | null;
    created_at: string | null;
    user: UserSummary | null;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    total: number;
}

interface Props {
    logs: Pagination<LogItem>;
    filters: { action?: string; user_id?: string; from?: string; to?: string };
    totalLogs: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Audit Logs', href: '/admin/audit-logs' },
];

export default function AdminAuditLogsIndex({ logs, filters, totalLogs }: Props) {
    const [action, setAction] = useState(filters.action ?? '');
    const [from, setFrom] = useState(filters.from ?? '');
    const [to, setTo] = useState(filters.to ?? '');

    const applyFilters = () => {
        const params: Record<string, string> = {};
        if (action.trim()) params.action = action.trim();
        if (from) params.from = from;
        if (to) params.to = to;
        router.get('/admin/audit-logs', params, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit Logs" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">Audit Logs</h1>
                    <p className="text-muted-foreground text-sm">
                        Showing {logs.total} of {totalLogs} log entries
                    </p>
                </div>

                <form
                    className="flex flex-wrap gap-2"
                    onSubmit={(e) => {
                        e.preventDefault();
                        applyFilters();
                    }}
                >
                    <Input
                        className="min-w-48 flex-1"
                        placeholder="Filter by action..."
                        value={action}
                        onChange={(e) => setAction(e.target.value)}
                    />
                    <Input type="date" value={from} onChange={(e) => setFrom(e.target.value)} />
                    <Input type="date" value={to} onChange={(e) => setTo(e.target.value)} />
                    <Button type="submit">Filter</Button>
                </form>

                {logs.data.length === 0 ? (
                    <Card className="flex flex-1 items-center justify-center py-16">
                        <CardContent className="flex flex-col items-center gap-3 text-center">
                            <ScrollText className="text-muted-foreground size-12" />
                            <p className="text-lg font-medium">No audit logs found</p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-4 py-3 font-medium">Action</th>
                                            <th className="px-4 py-3 font-medium">Actor</th>
                                            <th className="px-4 py-3 font-medium">Entity</th>
                                            <th className="px-4 py-3 font-medium">When</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {logs.data.map((log) => (
                                            <tr key={log.id} className="border-b last:border-0">
                                                <td className="px-4 py-3">
                                                    <Link
                                                        href={`/admin/audit-logs/${log.id}`}
                                                        className="font-medium hover:text-primary hover:underline"
                                                    >
                                                        {log.action}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">{log.user?.email ?? '—'}</td>
                                                <td className="text-muted-foreground px-4 py-3">
                                                    {log.entity_type
                                                        ? `${log.entity_type} #${log.entity_id}`
                                                        : '—'}
                                                </td>
                                                <td className="text-muted-foreground px-4 py-3">{log.created_at}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {logs.links.length > 3 && (
                    <div className="flex flex-wrap justify-center gap-1">
                        {logs.links.map((link, i) =>
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
