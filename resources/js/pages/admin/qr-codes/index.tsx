import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { QrCode } from 'lucide-react';
import { useState } from 'react';

interface Owner {
    id: number;
    name: string;
    email: string;
}

interface QrListItem {
    id: number;
    name: string;
    slug: string | null;
    type: string;
    type_label: string;
    is_dynamic: boolean;
    status: string;
    admin_locked: boolean;
    frozen: boolean;
    scan_count: number;
    owner: Owner | null;
    created_at: string | null;
    deleted_at: string | null;
}

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface TypeOption {
    value: string;
    label: string;
}

interface Props {
    qrCodes: Pagination<QrListItem>;
    filters: { search?: string; type?: string; status?: string; dynamic?: string; trashed?: boolean };
    totalQrCodes: number;
    typeOptions: TypeOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'QR Codes', href: '/admin/qr-codes' },
];

const ALL = '__all';

export default function AdminQrCodesIndex({ qrCodes, filters, totalQrCodes, typeOptions }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (next: Record<string, string | boolean>) => {
        const params: Record<string, string> = {
            search,
            type: filters.type ?? '',
            status: filters.status ?? '',
            dynamic: filters.dynamic ?? '',
            ...(filters.trashed ? { trashed: '1' } : {}),
        };

        Object.entries(next).forEach(([key, value]) => {
            if (typeof value === 'boolean') {
                if (value) {
                    params[key] = '1';
                } else {
                    delete params[key];
                }
            } else {
                params[key] = value;
            }
        });

        Object.keys(params).forEach((key) => {
            if (params[key] === '' || params[key] === ALL) {
                delete params[key];
            }
        });

        router.get('/admin/qr-codes', params, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="QR Codes" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div>
                    <h1 className="text-xl font-semibold">QR Codes</h1>
                    <p className="text-muted-foreground text-sm">
                        Showing {qrCodes.total} of {totalQrCodes} total QR codes
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
                            placeholder="Search name, slug, or owner..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </form>
                    <Select value={filters.type ?? ALL} onValueChange={(v) => applyFilters({ type: v })}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All types" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All types</SelectItem>
                            {typeOptions.map((type) => (
                                <SelectItem key={type.value} value={type.value}>
                                    {type.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <Select value={filters.status ?? ALL} onValueChange={(v) => applyFilters({ status: v })}>
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="All status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All status</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="paused">Paused</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={filters.dynamic ?? ALL} onValueChange={(v) => applyFilters({ dynamic: v })}>
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="Dynamic" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All</SelectItem>
                            <SelectItem value="1">Dynamic</SelectItem>
                            <SelectItem value="0">Static</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button
                        type="button"
                        variant={filters.trashed ? 'default' : 'outline'}
                        onClick={() => applyFilters({ trashed: !filters.trashed })}
                    >
                        Trashed
                    </Button>
                </div>

                {qrCodes.data.length === 0 ? (
                    <Card className="flex flex-1 items-center justify-center py-16">
                        <CardContent className="flex flex-col items-center gap-3 text-center">
                            <QrCode className="text-muted-foreground size-12" />
                            <p className="text-lg font-medium">No QR codes found</p>
                        </CardContent>
                    </Card>
                ) : (
                    <Card>
                        <CardContent className="p-0">
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b text-left">
                                            <th className="px-4 py-3 font-medium">Name</th>
                                            <th className="px-4 py-3 font-medium">Owner</th>
                                            <th className="px-4 py-3 font-medium">Type</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 font-medium">Scans</th>
                                            <th className="px-4 py-3 font-medium">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {qrCodes.data.map((qr) => (
                                            <tr key={qr.id} className="border-b last:border-0">
                                                <td className="px-4 py-3">
                                                    <Link
                                                        href={`/admin/qr-codes/${qr.id}`}
                                                        className="font-medium hover:text-primary hover:underline"
                                                    >
                                                        {qr.name}
                                                    </Link>
                                                    {qr.is_dynamic && (
                                                        <Badge variant="secondary" className="ml-2">
                                                            Dynamic
                                                        </Badge>
                                                    )}
                                                    {qr.deleted_at && (
                                                        <Badge variant="destructive" className="ml-2">
                                                            Deleted
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    {qr.owner ? (
                                                        <Link
                                                            href={`/admin/users/${qr.owner.id}`}
                                                            className="hover:text-primary hover:underline"
                                                        >
                                                            {qr.owner.email}
                                                        </Link>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">{qr.type_label}</td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={qr.status === 'paused' ? 'destructive' : 'outline'}>
                                                        {qr.status}
                                                    </Badge>
                                                    {qr.admin_locked && (
                                                        <Badge variant="destructive" className="ml-1">
                                                            locked
                                                        </Badge>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">{qr.scan_count}</td>
                                                <td className="text-muted-foreground px-4 py-3">{qr.created_at}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {qrCodes.links.length > 3 && (
                    <div className="flex flex-wrap justify-center gap-1">
                        {qrCodes.links.map((link, i) =>
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
