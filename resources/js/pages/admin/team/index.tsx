import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { type AdminTeamMember, type Paginated } from '@/types/admin';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ShieldCheck, Plus, Users } from 'lucide-react';
import { useState } from 'react';

interface Props {
    members: Paginated<AdminTeamMember>;
    filters: { search?: string; role?: string; status?: string };
    totalMembers: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Admin Team', href: '/admin/team' },
];

const ALL = '__all';

const roleLabels: Record<string, string> = {
    admin: 'Admin',
    super_admin: 'Super Admin',
};

function roleBadgeVariant(role: string): 'default' | 'secondary' {
    return role === 'super_admin' ? 'default' : 'secondary';
}

export default function AdminTeamIndex({ members, filters, totalMembers }: Props) {
    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (next: Record<string, string>) => {
        const params: Record<string, string> = {
            search,
            role: filters.role ?? '',
            status: filters.status ?? '',
        };

        Object.entries(next).forEach(([key, value]) => {
            params[key] = value;
        });

        Object.keys(params).forEach((key) => {
            if (params[key] === '' || params[key] === ALL) {
                delete params[key];
            }
        });

        router.get('/admin/team', params, { preserveState: true, replace: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Team" />
            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="flex items-center gap-2 text-xl font-semibold">
                            <ShieldCheck className="size-5" />
                            Admin Team
                        </h1>
                        <p className="text-muted-foreground text-sm">
                            Showing {members.total} of {totalMembers} admin accounts
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/admin/team/create">
                            <Plus className="size-4" /> Add admin
                        </Link>
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
                    <Select value={filters.role ?? ALL} onValueChange={(v) => applyFilters({ role: v })}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All roles" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value={ALL}>All roles</SelectItem>
                            <SelectItem value="admin">Admin</SelectItem>
                            <SelectItem value="super_admin">Super Admin</SelectItem>
                        </SelectContent>
                    </Select>
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
                </div>

                {members.data.length === 0 ? (
                    <Card className="flex flex-1 items-center justify-center py-16">
                        <CardContent className="flex flex-col items-center gap-3 text-center">
                            <Users className="text-muted-foreground size-12" />
                            <p className="text-lg font-medium">No admin accounts found</p>
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
                                            <th className="px-4 py-3 font-medium">Role</th>
                                            <th className="px-4 py-3 font-medium">Status</th>
                                            <th className="px-4 py-3 font-medium">Email verified</th>
                                            <th className="px-4 py-3 font-medium">Last login</th>
                                            <th className="px-4 py-3 font-medium">Created</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {members.data.map((member) => (
                                            <tr key={member.id} className="border-b last:border-0">
                                                <td className="px-4 py-3 font-medium">
                                                    <Link
                                                        href={`/admin/users/${member.id}`}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {member.name}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Link
                                                        href={`/admin/users/${member.id}`}
                                                        className="hover:text-primary hover:underline"
                                                    >
                                                        {member.email}
                                                    </Link>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={roleBadgeVariant(member.role)}>
                                                        {roleLabels[member.role] ?? member.role}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant={member.status === 'banned' ? 'destructive' : 'outline'}>
                                                        {member.status}
                                                    </Badge>
                                                </td>
                                                <td className="text-muted-foreground px-4 py-3">
                                                    {member.email_verified_at ?? '—'}
                                                </td>
                                                <td className="text-muted-foreground px-4 py-3">
                                                    {member.last_login_at ?? '—'}
                                                </td>
                                                <td className="text-muted-foreground px-4 py-3">{member.created_at}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {members.links.length > 3 && (
                    <div className="flex flex-wrap justify-center gap-1">
                        {members.links.map((link, i) =>
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
