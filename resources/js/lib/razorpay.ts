export interface CheckoutData {
    checkout_type: 'order' | 'subscription';
    razorpay_key: string;
    name: string;
    prefill: { name: string; email: string };
    order_id?: string;
    gateway_subscription_id?: string;
    amount?: number;
    currency?: string;
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

    const options: Record<string, unknown> = {
        key: checkout.razorpay_key,
        name: checkout.name,
        description: checkout.checkout_type === 'order' ? 'Pro plan renewal' : 'Pro subscription',
        prefill: checkout.prefill,
        theme: { color: '#18181b' },
        handler: onSuccess,
    };

    if (checkout.checkout_type === 'order' && checkout.order_id) {
        options.order_id = checkout.order_id;
    } else if (checkout.gateway_subscription_id) {
        options.subscription_id = checkout.gateway_subscription_id;
    }

    new window.Razorpay!(options).open();
}
