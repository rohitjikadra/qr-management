import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { type AdminUserListItem } from '@/types/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Users } from 'lucide-react';
import { useState } from 'react';

interface Pagination<T> {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    users: Pagination<AdminUserListItem>;
    filters: { search?: string; status?: string; trashed?: boolean };
    totalUsers: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Users', href: '/admin/users' },
];

const ALL = '__all';

export default function AdminUsersIndex({ users, filters, totalUsers }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (next: Record<string, string | boolean>) => {
        const params: Record<string, string> = {
            search,
            status: filters.status ?? '',
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

        router.get('/admin/users', params, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-xl font-semibold">Users</h1>
                        <p className="text-muted-foreground text-sm">
                            {users.total} of {totalUsers} customer accounts — users register on their own
                        </p>
                    </div>
                    <Button variant="outline" asChild>
                        <a href="/admin/users/export/csv">Export CSV</a>
                    </Button>
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
                            placeholder="Search name or email..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                        />
                    </form>
                    <Select value={filters.status ?? ALL} onValueChange={(v) => applyFilters({ status: v })}>
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="All status" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All status</SelectItem>
                            <SelectItem value="active">Active</SelectItem>
                            <SelectItem value="banned">Banned</SelectItem>
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

                {users.data.length === 0 ? (
                    <Card className="flex flex-1 items-center justify-center py-16">
                        <CardContent className="flex flex-col items-center gap-3 text-center">
                            <Users className="text-muted-foreground size-12" />
                            <p className="text-lg font-medium">No users found</p>
                            <p className="text-muted-foreground text-sm">Try adjusting your search or filters.</p>
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
                                            <th className="px-4 py-3 font-medium">Email</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 font-medium">Discount</th>
                                            <th className="px-4 py-3 font-medium">QRs</th>
                                            <th className="px-4 py-3 font-medium">Registered</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {users.data.map((user) => (
                                            <tr key={user.id} className="border-b last:border-0">
                                                <td className="px-4 py-3 font-medium">
                                                    <Link
                                                        href={`/admin/users/${user.id}`}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {user.name}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Link
                                                        href={`/admin/users/${user.id}`}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {user.email}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={user.status === 'banned' ? 'destructive' : 'outline'}>
                                                        {user.status}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {user.billing_discount_percent ? `${user.billing_discount_percent}%` : '—'}
                                                </td>
                                                <td className="px-4 py-3">{user.qr_codes_count}</td>
                                                <td className="text-muted-foreground px-4 py-3">{user.created_at}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {users.links.length > 3 && (
                    <div className="flex flex-wrap justify-center gap-1">
                        {users.links.map((link, i) =>
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
