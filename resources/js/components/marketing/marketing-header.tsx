import { Button } from '@/components/ui/button';
import AppLogoIcon from '@/components/app-logo-icon';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useState } from 'react';

const navLinks = [
    { label: 'Features', href: '#features' },
    { label: 'How it works', href: '#how-it-works' },
    { label: 'Pricing', href: route('pricing') },
    { label: 'FAQ', href: '#faq' },
];

export default function MarketingHeader() {
    const { auth, name, branding } = usePage<SharedData>().props;
    const [mobileOpen, setMobileOpen] = useState(false);
    const logoUrl = branding?.logo_url ?? null;

    return (
        <header className="border-b bg-background/80 sticky top-0 z-50 backdrop-blur">
            <div className="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <Link href={route('home')} className="flex min-w-0 items-center gap-2 font-semibold">
                    <div className="bg-primary text-primary-foreground flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-md">
                        {logoUrl ? (
                            <img src={logoUrl} alt={name} className="size-full object-contain p-0.5" />
                        ) : (
                            <AppLogoIcon className="size-5 fill-current" />
                        )}
                    </div>
                    <span className="truncate">{name}</span>
                </Link>

                <nav className="hidden items-center gap-6 md:flex">
                    {navLinks.map((link) => (
                        <a key={link.href} href={link.href} className="text-muted-foreground hover:text-foreground text-sm transition-colors">
                            {link.label}
                        </a>
                    ))}
                </nav>

                <div className="hidden items-center gap-2 md:flex">
                    {auth.user ? (
                        <Button asChild>
                            <Link href={route('dashboard')}>Dashboard</Link>
                        </Button>
                    ) : (
                        <>
                            <Button variant="ghost" asChild>
                                <Link href={route('login')}>Log in</Link>
                            </Button>
                            <Button asChild>
                                <Link href={route('register')}>Get started free</Link>
                            </Button>
                        </>
                    )}
                </div>

                <button
                    type="button"
                    className="text-muted-foreground md:hidden"
                    onClick={() => setMobileOpen(!mobileOpen)}
                    aria-label="Toggle menu"
                >
                    {mobileOpen ? <X className="size-5" /> : <Menu className="size-5" />}
                </button>
            </div>

            {mobileOpen && (
                <div className="border-t px-4 py-4 md:hidden">
                    <nav className="flex flex-col gap-3">
                        {navLinks.map((link) => (
                            <a
                                key={link.href}
                                href={link.href}
                                className="text-sm"
                                onClick={() => setMobileOpen(false)}
                            >
                                {link.label}
                            </a>
                        ))}
                        <div className="mt-2 flex flex-col gap-2">
                            {auth.user ? (
                                <Button asChild>
                                    <Link href={route('dashboard')}>Dashboard</Link>
                                </Button>
                            ) : (
                                <>
                                    <Button variant="outline" asChild>
                                        <Link href={route('login')}>Log in</Link>
                                    </Button>
                                    <Button asChild>
                                        <Link href={route('register')}>Get started free</Link>
                                    </Button>
                                </>
                            )}
                        </div>
                    </nav>
                </div>
            )}
        </header>
    );
}
