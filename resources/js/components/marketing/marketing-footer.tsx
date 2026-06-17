import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

export default function MarketingFooter() {
    const year = new Date().getFullYear();
    const { name } = usePage<SharedData>().props;

    return (
        <footer className="border-t bg-muted/30">
            <div className="mx-auto flex max-w-6xl flex-col gap-6 px-4 py-10 md:flex-row md:items-center md:justify-between">
                <div>
                    <p className="font-medium">{name}</p>
                    <p className="text-muted-foreground mt-1 text-sm">Create, manage, and track QR codes in one place.</p>
                </div>
                <nav className="text-muted-foreground flex flex-wrap gap-x-6 gap-y-2 text-sm">
                    <Link href={route('pricing')}>Pricing</Link>
                    <Link href={route('legal.terms')}>Terms</Link>
                    <Link href={route('legal.privacy')}>Privacy</Link>
                    <Link href={route('legal.refund')}>Refund Policy</Link>
                    <Link href={route('login')}>Log in</Link>
                </nav>
            </div>
            <div className="text-muted-foreground border-t px-4 py-4 text-center text-xs">
                &copy; {year} {name}. All rights reserved.
            </div>
        </footer>
    );
}
