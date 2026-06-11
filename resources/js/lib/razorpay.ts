export interface CheckoutData {
    gateway_subscription_id: string;
    razorpay_key: string;
    name: string;
    prefill: { name: string; email: string };
}

declare global {
    interface Window {
        Razorpay?: new (options: Record<string, unknown>) => { open: () => void };
    }
}

let scriptPromise: Promise<void> | null = null;

function loadCheckoutScript(): Promise<void> {
    if (window.Razorpay) return Promise.resolve();

    scriptPromise ??= new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://checkout.razorpay.com/v1/checkout.js';
        script.onload = () => resolve();
        script.onerror = () => reject(new Error('Failed to load Razorpay checkout'));
        document.body.appendChild(script);
    });

    return scriptPromise;
}

export async function openRazorpayCheckout(checkout: CheckoutData, onSuccess: () => void): Promise<void> {
    await loadCheckoutScript();

    new window.Razorpay!({
        key: checkout.razorpay_key,
        subscription_id: checkout.gateway_subscription_id,
        name: checkout.name,
        description: 'Pro subscription',
        prefill: checkout.prefill,
        theme: { color: '#18181b' },
        handler: onSuccess,
    }).open();
}
