import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { type FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Admin Team', href: '/admin/team' },
    { title: 'Add admin', href: '/admin/team/create' },
];

export default function AdminTeamCreate() {
    const { data, setData, post, errors, processing } = useForm({
        name: '',
        email: '',
        password: '',
        status: 'active',
        email_verified: true,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/team');
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Add admin" />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <div className="flex items-center gap-3">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/team">
                            <ArrowLeft className="size-4" /> Back
                        </Link>
                    </Button>
                    <h1 className="text-xl font-semibold">Add admin</h1>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Admin account</CardTitle>
                        <CardDescription>
                            Creates a new admin account. Regular users must register through the public sign-up page.
                        </CardDescription>
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

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    required
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
                                        Mark the email as verified so the admin can use the panel immediately.
                                    </p>
                                </div>
                            </div>
                            <InputError message={errors.email_verified} />

                            <div className="flex gap-2 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Create admin
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <Link href="/admin/team">Cancel</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
