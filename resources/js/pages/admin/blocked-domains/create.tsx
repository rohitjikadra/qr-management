import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { type FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Blocked Domains', href: '/admin/blocked-domains' },
    { title: 'Create', href: '/admin/blocked-domains/create' },
];

export default function AdminBlockedDomainCreate() {
    const { data, setData, post, errors, processing } = useForm({
        domain: '',
        reason: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/blocked-domains');
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Add blocked domain" />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                <div className="flex items-center gap-3">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/blocked-domains">
                            <ArrowLeft className="size-4" /> Back
                        </Link>
                    </Button>
                    <h1 className="text-xl font-semibold">Add blocked domain</h1>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Domain details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="domain">Domain</Label>
                                <Input
                                    id="domain"
                                    placeholder="example.com"
                                    value={data.domain}
                                    onChange={(e) => setData('domain', e.target.value)}
                                    required
                                />
                                <InputError message={errors.domain} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="reason">Reason (optional)</Label>
                                <Input
                                    id="reason"
                                    value={data.reason}
                                    onChange={(e) => setData('reason', e.target.value)}
                                    placeholder="Phishing, malware, etc."
                                />
                                <InputError message={errors.reason} />
                            </div>
                            <Button type="submit" disabled={processing}>
                                Add domain
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
