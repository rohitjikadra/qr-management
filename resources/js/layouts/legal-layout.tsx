import MarketingLayout from '@/layouts/marketing-layout';
import { Link } from '@inertiajs/react';
import { type ReactNode } from 'react';

interface LegalLayoutProps {
    title: string;
    children: ReactNode;
}

export default function LegalLayout({ title, children }: LegalLayoutProps) {
    return (
        <MarketingLayout>
            <div className="mx-auto max-w-3xl px-4 py-12">
                <p className="text-muted-foreground mb-6 text-sm">
                    <Link href={route('home')} className="hover:text-foreground">
                        &larr; Back to home
                    </Link>
                </p>
                <h1 className="mb-8 text-3xl font-bold tracking-tight">{title}</h1>
                <div className="prose prose-zinc dark:prose-invert max-w-none text-sm leading-relaxed [&_h2]:mt-8 [&_h2]:mb-3 [&_h2]:text-lg [&_h2]:font-semibold [&_li]:my-1 [&_p]:mb-4 [&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-5">
                    {children}
                </div>
                <p className="text-muted-foreground mt-10 border-t pt-6 text-xs">
                    Last updated: March 2026. Questions? Contact support via your account dashboard.
                </p>
            </div>
        </MarketingLayout>
    );
}
