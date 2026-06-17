import LegalLayout from '@/layouts/legal-layout';
import { Head } from '@inertiajs/react';

export default function Privacy() {
    return (
        <LegalLayout title="Privacy Policy">
            <Head title="Privacy Policy" />
            <p>
                This Privacy Policy explains how QR Manager (&quot;we&quot;, &quot;us&quot;) collects, uses, and protects
                information when you use our website and application.
            </p>

            <h2>1. Information we collect</h2>
            <ul>
                <li>
                    <strong>Account data:</strong> name, email, password (hashed), optional country, login timestamps
                </li>
                <li>
                    <strong>QR data:</strong> labels, types, destinations, status, and settings you configure
                </li>
                <li>
                    <strong>Scan analytics:</strong> timestamp, device/browser type, and approximate location (when
                    GeoIP data is available). We do not store raw visitor IP addresses — only a salted hash for
                    deduplication.
                </li>
                <li>
                    <strong>Billing data:</strong> subscription status, payment references, and invoice details via
                    Razorpay. We do not store full card numbers.
                </li>
                <li>
                    <strong>Technical logs:</strong> server logs, error reports, and security events
                </li>
            </ul>

            <h2>2. How we use information</h2>
            <p>We use collected data to:</p>
            <ul>
                <li>Provide, secure, and improve the Service</li>
                <li>Show analytics dashboards to QR owners</li>
                <li>Process subscriptions and send transactional emails</li>
                <li>Detect abuse, fraud, and policy violations</li>
                <li>Comply with legal obligations</li>
            </ul>

            <h2>3. Cookies & sessions</h2>
            <p>
                We use session cookies and similar technologies to keep you logged in and remember preferences. Scan
                tracking for analytics does not use advertising cookies.
            </p>

            <h2>4. Data sharing</h2>
            <p>We do not sell your personal data. We share data only with:</p>
            <ul>
                <li>
                    <strong>Infrastructure providers</strong> (hosting, database, email) under data processing terms
                </li>
                <li>
                    <strong>Razorpay</strong> for payment processing
                </li>
                <li>
                    <strong>Authorities</strong> when required by law or to protect rights and safety
                </li>
            </ul>

            <h2>5. Data retention</h2>
            <p>
                Account data is kept while your account is active. Deleted accounts are soft-deleted and may be retained
                for a limited period for legal and backup purposes. Analytics history on Free plans is limited per plan
                terms; Pro users retain full history while subscribed.
            </p>

            <h2>6. Your rights</h2>
            <p>
                You may access, update, or delete your account data from Settings. You may request export or deletion by
                contacting support. Indian users may have rights under applicable data protection laws.
            </p>

            <h2>7. Security</h2>
            <p>
                We use industry-standard measures including encrypted passwords, HTTPS, access controls, and audit
                logging for admin actions. No system is 100% secure; please use a strong unique password.
            </p>

            <h2>8. Children</h2>
            <p>The Service is not directed at children under 13. We do not knowingly collect data from children.</p>

            <h2>9. Changes</h2>
            <p>We may update this policy. The &quot;Last updated&quot; date at the bottom will reflect changes.</p>
        </LegalLayout>
    );
}
