import LegalLayout from '@/layouts/legal-layout';
import { Head } from '@inertiajs/react';

export default function Refund() {
    return (
        <LegalLayout title="Refund Policy">
            <Head title="Refund Policy" />
            <p>
                This Refund Policy applies to paid subscriptions for QR Manager processed through Razorpay. Please read
                this before purchasing a Pro plan.
            </p>

            <h2>1. Subscription billing</h2>
            <p>
                Pro Monthly and Pro Yearly are recurring subscriptions. You are charged at the start of each billing
                cycle. Cancelling stops future charges but does not automatically refund the current period unless
                stated below.
            </p>

            <h2>2. Cancellation</h2>
            <p>
                You can cancel anytime from Billing in your dashboard. After cancellation, your Pro features remain active
                until the end of the paid period. QR redirects continue to work according to our subscription lifecycle
                rules.
            </p>

            <h2>3. Refund eligibility</h2>
            <p>We may offer a refund within <strong>7 days</strong> of your first paid subscription purchase if:</p>
            <ul>
                <li>You have not substantially used Pro-only features beyond reasonable testing</li>
                <li>The request is made in good faith (e.g. accidental duplicate charge, technical failure on our side)</li>
            </ul>
            <p>Renewal charges are generally non-refundable unless required by law or Razorpay dispute resolution.</p>

            <h2>4. Non-refundable cases</h2>
            <ul>
                <li>Partial months or years after the refund window</li>
                <li>Account termination for Terms of Service violations</li>
                <li>Change of mind after significant Pro usage</li>
                <li>Issues caused by third-party payment methods outside our control</li>
            </ul>

            <h2>5. How to request a refund</h2>
            <p>
                Email support from your registered account email with your invoice number and reason. We aim to respond
                within 5 business days. Approved refunds are processed back to the original payment method via Razorpay
                and may take 5–10 business days to appear.
            </p>

            <h2>6. Chargebacks</h2>
            <p>
                Please contact us before initiating a chargeback. Unauthorized chargebacks may result in account
                suspension while the dispute is investigated.
            </p>

            <h2>7. Contact</h2>
            <p>
                For billing questions, use the support contact listed in your account or on our website footer once live
                support email is configured.
            </p>
        </LegalLayout>
    );
}
