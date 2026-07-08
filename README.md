# Princess Homes Fatima Chapel Collection System

A modern Laravel + MySQL web app for recording and monitoring chapel collections for Princess Homes Fatima Chapel. It tracks monthly **Balik Gasa**, optional **Donation**, and optional **Halad** entries with role-based access, reports, CSV export, and printable pages.

## Features

- Dashboard totals for Balik Gasa, Donation, Halad, current-month Balik Gasa, unpaid members, and recent entries.
- Members management with search, status filtering, Hugpong Banay assignment, profile pages, and collection history.
- Hugpong Banay management with current leader selection from members and leader tenure history.
- Auto-generated member IDs.
- Collection CRUD with searchable member selectors and filters by member, type, Balik Gasa month, and date range.
- Balik Gasa monthly monitoring for all active members with paid/unpaid status and quick payment.
- Duplicate Balik Gasa prevention per member per month.
- Halad recorded as a total mass collection from all members, not per member.
- Click a member ID to open a yearly Balik Gasa payment plot with previous/next year navigation.
- Quick monthly Donation posting from the Balik Gasa monitoring page.
- Quick Offering posting after mass as a total collection from all members.
- References and notes on collections, ledger entries, and disbursements.
- Ledger for Balik Gasa, Donation, Offering, and General Chapel Fund.
- Disbursement encoding deducts from chapel fund balances.
- Month locks can close Balik Gasa, Donation, Offering, and Disbursement records; admins can unlock closed months.
- Reports for monthly collections, member history, overall summary, print view, and CSV export.
- User accounts with roles:
  - **Admin**: full access.
  - **Treasurer/Encoder**: manage members and collections.
  - **Viewer**: dashboard, monitoring, and reports only.

## Tech Stack

- Laravel 13
- PHP 8.3+
- MySQL
- Tailwind CSS 4
- Vite

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
```

Configure MySQL in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chapel_collection_system
DB_USERNAME=root
DB_PASSWORD=
```

Create the database in MySQL, then run:

```bash
php artisan migrate --seed
npm run build
php artisan serve
```

Open `http://127.0.0.1:8000`.

## Seeded Accounts

All seeded accounts use password `password`.

| Role | Email |
| --- | --- |
| Admin | `admin@chapel.test` |
| Treasurer/Encoder | `treasurer@chapel.test` |
| Viewer | `viewer@chapel.test` |

## Database ERD Explanation

### `users`

Stores login accounts and the `role` field used for authorization. Users can encode collection records through the `encoded_by` relationship.

### `members`

Stores chapel members:

- `member_id`
- `full_name`
- `contact_number`
- `address_purok` used as the member address field
- `hugpong_banay_id` references `hugpong_banays.id`
- `status`
- `date_joined`

`member_id` is generated automatically by the system. One member belongs to one Hugpong Banay and has many Balik Gasa or Donation entries.

### `hugpong_banays`

Stores each Hugpong Banay group:

- `name`
- `description`
- `status`
- `current_leader_id` references the selected leader in `members.id`

One Hugpong Banay has many members and many leader history records.

### `hugpong_banay_leader_histories`

Stores leader tenure history:

- `hugpong_banay_id`
- `member_id`
- `started_at`
- `ended_at`
- `notes`

When a Hugpong Banay leader changes, the previous active tenure is closed and a new active tenure is created.

### `collections`

Stores all Balik Gasa, Donation, and Halad records:

- `member_id` references `members.id`, nullable for Halad mass totals
- `collection_type` is `balik_gasa`, `donation`, or `halad`
- `amount`
- `collection_date`
- `collection_month` in `YYYY-MM` format for Balik Gasa only
- `remarks`
- `reference_no`
- `encoded_by` references `users.id`
- `deleted_at` enables soft deletes

Balik Gasa is validated so each active member can only have one record per `collection_month`. Donation requires a member and allows multiple entries per member on any date. Offering is recorded as a total collection per mass/service and does not require a member.

### `ledger_entries`

Stores manual fund movements such as beginning balances and other sources:

- `fund_type`: `balik_gasa`, `donation`, `halad`, or `general`
- `entry_type`: `credit` or `debit`
- `amount`
- `entry_date`
- `reference_no`
- `remarks`

### `expenses`

Stores chapel disbursements that deduct from fund balances:

- `fund_type`
- `category`
- `amount`
- `expense_date`
- `reference_no`
- `remarks`

### `month_locks`

Stores closed months for each record type:

- `lockable_type`: `balik_gasa`, `donation`, `halad`, or `disbursement`
- `month` in `YYYY-MM` format
- `locked_by` references `users.id`

When a month is locked, records in that month cannot be added, edited, or deleted until an admin unlocks it.

## Page Descriptions

- **Login**: clean branded login page with seeded account hints for local testing.
- **Dashboard**: card-based totals, unpaid Balik Gasa list for the current month, and recent entries.
- **Members**: searchable member table, Hugpong Banay assignment, add/edit forms, delete action, and member profile with collection totals.
- **Hugpong Banay**: manage groups, assign a current leader from members, view assigned members, and review leader tenure history.
- **Collections**: entry list with filters, add/edit/delete forms, conditional month field for Balik Gasa.
- **Balik Gasa Monitoring**: month selector, Hugpong Banay buttons, all active members, paid/unpaid status, amount, payment date, quick Balik Gasa payment, quick Donation, Offering posting, and member yearly payment plot.
- **Reports**: monthly table, member history, summary totals, grand total, CSV export, and print button.
- **Ledger**: fund balances, manual entries for beginning balance/other source, disbursement posting, month lock, and transaction ledger.
- **User Accounts**: admin-only role and account management.

## Notes

- Collections use soft delete.
- Member delete is blocked when collection history exists.
- Validation requires member, collection type, amount greater than zero, and collection date.
- The UI uses white, soft blue, gold, and light gray with responsive Tailwind layouts.
