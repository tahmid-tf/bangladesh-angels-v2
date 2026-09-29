# aamarPay website requirements

## Implemented

- Public About Us, Services, Contact, Terms & Conditions, Privacy, Delivery, Refund/Return, and Cancellation pages.
- The supplied business phone, address, E-TIN, and trade licence in the public footer and contact page.
- Services and Terms show active admin-managed membership tiers, prices, and included benefits. Existing plan prices are not overwritten.
- An unchecked, required checkout acknowledgement with five policy links opening in new tabs. The server rejects missing consent or an outdated policy version.
- New checkouts retain the customer, purchased plan, business details, policy text/version, acknowledgement, server receipt timestamp, IP, and user agent. A browser-reported checkbox time is retained separately when available; it is not trusted as the authoritative timestamp.
- Verified successful payments are attached to their saved orders. Receipts and membership upgrade timestamps are retained. A separate, optional member action records confirmation of access after delivery; it does not waive policy rights.
- Members can find receipts and confirmation forms under Membership receipts in the investor sidebar or Membership Orders & Receipts in the footer. Admins can open all order evidence from Subscriptions.
- Admins can append customer correspondence or digital-delivery documents and upload private PDF, PNG, JPG, or TXT files. Attachments and internal communications are admin-only. The original receipt-email body, recipient, and send status are retained.
- Repeat verified callbacks do not duplicate payment, delivery, or receipt-email records. New-order member, plan, price, and currency are matched against the retained order rather than callback form fields.

## Deployment required

No migration has been executed against the configured/server database. Tests run only on a separate in-memory SQLite connection. The supplied XLSX and DOCX have not been changed.

After taking a normal database and private-storage backup, deploy the code and run only the new additive migration:

```sh
php artisan migrate --path=database/migrations/2026_09_04_000000_create_membership_order_evidence_tables.php --force
npm ci
npm run build
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

The migration creates `membership_orders` and `membership_order_records`. It does not alter, update, or delete existing tables or records. Its rollback intentionally leaves evidence intact. Do not use a database reset, refresh, or seed operation for this release. New checkout submission fails closed until the evidence tables are installed.

Keep `storage/app/private/membership-evidence` writable by the application and include it in private backups. Never expose that directory under the public storage link. No new payment credentials are needed for these website changes. Confirm the existing aamarPay configuration uses the intended live store and USD membership pricing before enabling payments.

## Operational responsibilities

- The source document contains an unconfirmed RJSC/incorporation placeholder. It was not published. Supply the confirmed number if aamarPay or its bank also requests it; E-TIN and trade licence were supplied and are displayed.
- The record register begins with new checkouts. Historical order consent cannot be truthfully reconstructed and is not backfilled. Existing payment and subscription records remain untouched.
- External inbox exchanges, calls, and any manually completed linked-account delivery must be appended by an administrator. This change does not connect to the team's email inbox.
- Administrators should follow up on missing access confirmations or failed receipt emails. A sent email indicates acceptance by the configured mail transport, not proof that it was opened or delivered to the customer's inbox. A receipt remains accessible online if email delivery fails.
- The refund/cancellation pages publish the supplied procedures. Staff still process requests and refunds with aamarPay; this does not automatically refund or cancel payments.
- No physical shipping documents are applicable to digital memberships. A receipt and automatic membership-upgrade timestamp are not substitutes for the customer's explicit access confirmation.
- Banking/international-route approval remains with aamarPay and its acquiring bank. Do not report the route as activated merely because the code is deployed. Conduct a controlled end-to-end gateway check after deployment and before confirming readiness to aamarPay.
- BAN Wealth's separate bank-transfer investment workflow has not been changed by this membership compliance release.

## Source and validation

Business details come from `BAN Website .xlsx` (Sheet1, D4 and D7); policies from `BAN Website Policy.docx`, sections 1–6; acknowledgement behavior from section 7. Internal drafting instructions were excluded from public copy.

Gateway transaction field reference: https://aamarpay.readme.io/reference/search-transaction

The isolated test configuration deliberately skips the live `.env` and replaces all database connections with SQLite `:memory:` before providers register. On Windows installations with SQLite extensions disabled, run:

```sh
php -d extension=pdo_sqlite -d extension=sqlite3 vendor/phpunit/phpunit/phpunit -c phpunit.isolated.xml
```

Do not use the default PHPUnit configuration for live-environment verification; it targets a separate MySQL test database and is not used for these checks.
