import LegalLayout from '@/layouts/legal-layout';
import { Head } from '@inertiajs/react';

export default function Terms() {
    return (
        <LegalLayout title="Terms of Service">
            <Head title="Terms of Service" />
            <p>
                These Terms of Service (&quot;Terms&quot;) govern your use of QR Manager (&quot;Service&quot;), operated
                as a software-as-a-service platform for creating and managing QR codes. By creating an account or using
                the Service, you agree to these Terms.
            </p>

            <h2>1. Account & eligibility</h2>
            <p>
                You must provide accurate information when registering. You are responsible for keeping your password
                secure and for all activity under your account. You must be at least 18 years old to use paid features.
            </p>

            <h2>2. Acceptable use</h2>
            <p>You agree not to use the Service to:</p>
            <ul>
                <li>Link to phishing, malware, illegal content, or deceptive pages</li>
                <li>Harass, spam, or mislead people who scan your QR codes</li>
                <li>Circumvent plan limits, abuse rate limits, or attempt unauthorized access</li>
                <li>Violate applicable laws in India or your jurisdiction</li>
            </ul>
            <p>
                We may pause, restrict, or terminate QR codes or accounts that violate these rules. Admin-paused QR codes
                cannot be reactivated by users without support review.
            </p>

            <h2>3. Plans, billing & subscriptions</h2>
            <p>
                Free and paid plans are described on our Pricing page. Paid subscriptions are billed through Razorpay.
                Plan activation occurs after successful payment confirmation via webhook — not from the browser alone.
            </p>
            <p>
                If a subscription expires, you enter a grace period before Pro management features are frozen. QR
                redirects continue to work during grace and frozen states, as described on the Pricing and Billing pages.
            </p>

            <h2>4. QR content & intellectual property</h2>
            <p>
                You retain ownership of content you place in your QR codes. You grant us a limited license to host,
                process, redirect, and display that content solely to operate the Service. You must have the right to use
                any URLs, contact data, or materials you encode.
            </p>

            <h2>5. Service availability</h2>
            <p>
                We aim for high availability but do not guarantee uninterrupted service. Maintenance, third-party outages,
                or force majeure may cause temporary downtime. We are not liable for indirect losses from downtime.
            </p>

            <h2>6. Limitation of liability</h2>
            <p>
                To the maximum extent permitted by law, our total liability for any claim related to the Service is
                limited to the amount you paid us in the 12 months before the claim. We are not liable for lost profits,
                data loss beyond our backups, or issues caused by third-party destinations linked in your QR codes.
            </p>

            <h2>7. Termination</h2>
            <p>
                You may delete your account at any time from Settings. We may suspend or terminate accounts that breach
                these Terms. Upon termination, your right to use the Service ends, but provisions that should survive
                (billing records, liability limits) remain in effect.
            </p>

            <h2>8. Changes</h2>
            <p>
                We may update these Terms. Material changes will be posted on this page. Continued use after changes
                constitutes acceptance.
            </p>

            <h2>9. Governing law</h2>
            <p>
                These Terms are governed by the laws of India. Disputes shall be subject to the courts of competent
                jurisdiction in India, unless otherwise required by applicable consumer protection law.
            </p>
        </LegalLayout>
    );
}
