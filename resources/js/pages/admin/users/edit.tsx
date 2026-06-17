import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface UserFormData {
    id: number;
    name: string;
    email: string;
    role: string;
    status: string;
    country: string;
    billing_discount_percent: number | '' | null;
    billing_note: string;
    email_verified: boolean;
    email_verified_at: string | null;
}

interface Props {
    user: UserFormData;
    canEditRole: boolean;
}

const NONE = '__none';

export default function AdminUserEdit({ user, canEditRole }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/admin/dashboard' },
        { title: 'Users', href: '/admin/users' },
        { title: user.name, href: `/admin/users/${user.id}` },
        { title: 'Edit', href: `/admin/users/${user.id}/edit` },
    ];

    const { data, setData, put, errors, processing } = useForm({
        name: user.name,
        email: user.email,
        role: user.role,
        status: user.status,
        password: '',
        country: user.country ?? '',
        billing_discount_percent: user.billing_discount_percent ?? '',
        billing_note: user.billing_note ?? '',
        email_verified: user.email_verified,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(`/admin/users/${user.id}`);
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${user.name}`} />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <div className="flex items-center gap-3">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/admin/users/${user.id}`}>
                            <ArrowLeft className="size-4" /> Back
                        </Link>
                    </Button>
                    <h1 className="text-xl font-semibold">Edit user</h1>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Account details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="flex items-start gap-3 rounded-lg border p-4">
                                <Checkbox
                                    id="email_verified"
                                    checked={data.email_verified}
                                    onCheckedChange={(checked) => setData('email_verified', checked === true)}
                                />
                                <div className="grid gap-1">
                                    <Label htmlFor="email_verified" className="cursor-pointer">
                                        Email verified
                                    </Label>
                                    <p className="text-muted-foreground text-sm">
                                        {data.email_verified
                                            ? user.email_verified_at
                                                ? `Verified on ${user.email_verified_at}`
                                                : 'Will be marked verified when saved.'
                                            : 'User must verify email before creating QR codes.'}
                                    </p>
                                </div>
                            </div>
                            <InputError message={errors.email_verified} />

                            <div className="grid gap-2">
                                <Label htmlFor="password">New password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Leave blank to keep current password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select value={data.status} onValueChange={(v) => setData('status', v)}>
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="banned">Banned</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            {canEditRole && (
                                <div className="grid gap-2">
                                    <Label htmlFor="role">Role</Label>
                                    <Select value={data.role} onValueChange={(v) => setData('role', v)}>
                                        <SelectTrigger id="role">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="user">User</SelectItem>
                                            <SelectItem value="admin">Admin</SelectItem>
                                            <SelectItem value="super_admin">Super Admin</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.role} />
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="country">Country</Label>
                                <Input
                                    id="country"
                                    value={data.country}
                                    onChange={(e) => setData('country', e.target.value)}
                                />
                                <InputError message={errors.country} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="billing_discount_percent">Billing discount</Label>
                                <Select
                                    value={data.billing_discount_percent === '' || data.billing_discount_percent === null
                                        ? NONE
                                        : String(data.billing_discount_percent)}
                                    onValueChange={(v) =>
                                        setData('billing_discount_percent', v === NONE ? '' : Number(v))
                                    }
                                >
                                    <SelectTrigger id="billing_discount_percent">
                                        <SelectValue placeholder="No discount" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value={NONE}>No discount</SelectItem>
                                        <SelectItem value="10">10%</SelectItem>
                                        <SelectItem value="25">25%</SelectItem>
                                        <SelectItem value="50">50%</SelectItem>
                                        <SelectItem value="75">75%</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.billing_discount_percent} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="billing_note">Billing note (internal)</Label>
                                <Input
                                    id="billing_note"
                                    value={data.billing_note}
                                    onChange={(e) => setData('billing_note', e.target.value)}
                                />
                                <InputError message={errors.billing_note} />
                            </div>

                            <div className="flex gap-2 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Save changes
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href={`/admin/users/${user.id}`}>Cancel</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
