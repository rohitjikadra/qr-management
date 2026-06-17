import { Alert, AlertDescription } from '@/components/ui/alert';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/admin-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface BrandingData {
    project_name: string;
    seo_title: string;
    seo_description: string;
    logo_url: string | null;
    favicon_url: string | null;
}

interface Props {
    branding: BrandingData;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/admin/dashboard' },
    { title: 'Settings', href: '/admin/settings' },
    { title: 'Branding & SEO', href: '/admin/settings/branding' },
];

export default function AdminBrandingSettings({ branding }: Props) {
    const { flash } = usePage<SharedData>().props;

    const { data, setData, post, errors, processing } = useForm({
        project_name: branding.project_name,
        seo_title: branding.seo_title,
        seo_description: branding.seo_description,
        logo: null as File | null,
        favicon: null as File | null,
        remove_logo: false,
        remove_favicon: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post('/admin/settings/branding', { forceFormData: true });
    };

    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Branding & SEO" />
            <div className="mx-auto flex w-full max-w-2xl flex-col gap-4 p-4">
                {flash?.success && (
                    <Alert>
                        <AlertDescription>{flash.success as string}</AlertDescription>
                    </Alert>
                )}

                <div className="flex items-center gap-3">
                    <Button variant="outline" size="sm" asChild>
                        <Link href="/admin/settings">
                            <ArrowLeft className="size-4" /> Back
                        </Link>
                    </Button>
                </div>

                <div>
                    <h1 className="text-xl font-semibold">Branding & SEO</h1>
                    <p className="text-muted-foreground mt-1 text-sm">
                        Project name and logo appear in the user dashboard sidebar, marketing site header, and admin panel.
                        Run <code className="text-xs">php artisan storage:link</code> once so uploaded images display correctly.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Public site identity</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="project_name">Project name</Label>
                                <p className="text-muted-foreground text-xs">
                                    Shown next to the logo in the user dashboard sidebar (e.g. QR Manager).
                                </p>
                                <Input
                                    id="project_name"
                                    value={data.project_name}
                                    onChange={(e) => setData('project_name', e.target.value)}
                                    required
                                />
                                <InputError message={errors.project_name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="seo_title">SEO title</Label>
                                <Input
                                    id="seo_title"
                                    value={data.seo_title}
                                    onChange={(e) => setData('seo_title', e.target.value)}
                                />
                                <InputError message={errors.seo_title} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="seo_description">SEO description</Label>
                                <textarea
                                    id="seo_description"
                                    rows={3}
                                    className="border-input bg-background flex w-full rounded-md border px-3 py-2 text-sm"
                                    value={data.seo_description}
                                    onChange={(e) => setData('seo_description', e.target.value)}
                                />
                                <InputError message={errors.seo_description} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="logo">Logo</Label>
                                <p className="text-muted-foreground text-xs">
                                    Square or horizontal image, PNG/SVG recommended. Max display size 32×32 px in the sidebar.
                                </p>
                                {branding.logo_url && !data.remove_logo && (
                                    <img src={branding.logo_url} alt="Current logo" className="h-12 w-auto rounded border" />
                                )}
                                <Input
                                    id="logo"
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => setData('logo', e.target.files?.[0] ?? null)}
                                />
                                {branding.logo_url && (
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="remove_logo"
                                            checked={data.remove_logo}
                                            onCheckedChange={(c) => setData('remove_logo', c === true)}
                                        />
                                        <Label htmlFor="remove_logo">Remove current logo</Label>
                                    </div>
                                )}
                                <InputError message={errors.logo} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="favicon">Favicon</Label>
                                {branding.favicon_url && !data.remove_favicon && (
                                    <img
                                        src={branding.favicon_url}
                                        alt="Current favicon"
                                        className="size-8 rounded border"
                                    />
                                )}
                                <Input
                                    id="favicon"
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) => setData('favicon', e.target.files?.[0] ?? null)}
                                />
                                {branding.favicon_url && (
                                    <div className="flex items-center gap-2">
                                        <Checkbox
                                            id="remove_favicon"
                                            checked={data.remove_favicon}
                                            onCheckedChange={(c) => setData('remove_favicon', c === true)}
                                        />
                                        <Label htmlFor="remove_favicon">Remove current favicon</Label>
                                    </div>
                                )}
                                <InputError message={errors.favicon} />
                            </div>

                            <Button type="submit" disabled={processing}>
                                Save branding
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AdminLayout>
    );
}
