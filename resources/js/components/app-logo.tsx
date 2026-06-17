import AppLogoIcon from './app-logo-icon';
import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

interface AppLogoProps {
    showName?: boolean;
}

export default function AppLogo({ showName = true }: AppLogoProps) {
    const { name, branding } = usePage<SharedData>().props;
    const logoUrl = branding?.logo_url ?? null;

    return (
        <>
            <div className="bg-sidebar-primary text-sidebar-primary-foreground flex aspect-square size-8 shrink-0 items-center justify-center overflow-hidden rounded-md">
                {logoUrl ? (
                    <img src={logoUrl} alt={name} className="size-full object-contain p-0.5" />
                ) : (
                    <AppLogoIcon className="size-5 fill-current text-white dark:text-black" />
                )}
            </div>
            {showName && (
                <div className="ml-1 grid min-w-0 flex-1 text-left text-sm">
                    <span className="mb-0.5 truncate leading-none font-semibold">{name}</span>
                </div>
            )}
        </>
    );
}
