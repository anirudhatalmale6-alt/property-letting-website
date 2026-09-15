# Property letting website

A small, self-contained website for a landlord or letting agent: public
property listings with search, an inquiry form on every property, a fees page,
a contact page, and an admin area the owner can run without a developer.

Built in plain PHP 8 with a SQLite database. No Composer, no npm, no build
step, no database server. Upload the folder and it runs.

- **Installation:** [docs/INSTALL.md](docs/INSTALL.md)
- **Day-to-day use:** [docs/USER-GUIDE.md](docs/USER-GUIDE.md)

---

## What is in it

**Public site**

- Property listings with filters: search text, type, city, bedrooms, rent
  range, furnishing, and sort order
- A page per property: photo gallery, specification table, description,
  features, and an inquiry form
- Fees and charges page, driven by an editable table of charges
- Contact page with a general inquiry form, opening hours and an optional map
- Editable About, Disclosures, Privacy and Terms pages
- `sitemap.xml` and `robots.txt` generated from the live content
- Responsive down to a 360px phone; works with JavaScript disabled

**Admin area** (`/admin`)

- Dashboard with counts and the latest inquiries
- Properties: add, edit, feature, archive, restore, delete; multi-photo upload
  with a choice of cover image
- Inquiries: inbox with statuses (new / read / replied / closed), private
  notes, and one-click delete for a data request
- Fees: add, edit, reorder, hide or remove any charge
- Website text: the About, Privacy and Terms pages
- Settings: business name, logo upload, home page wording, contact details,
  notification address, currency symbol, rent-period wording, date format,
  default state, fair housing statement, and the two brand colors

## Layout of the code

```
app/
  config.php            settings (override with config.local.php)
  db.php                schema, migrations and first-run demo content
  helpers.php           escaping, URLs, sessions, CSRF, uploads, mail
  auth.php              admin sign-in and login rate limiting
  properties.php        property and inquiry queries
  routes.php            the routing table — one line per page
  controllers/
    site.php            public pages
    admin.php           admin pages
  views/                templates; admin templates in views/admin/
public/
  index.php             front controller — the only entry point
  assets/               stylesheets and scripts
  uploads/              property photographs
data/
  site.sqlite           the entire database, one file
docs/
  INSTALL.md            deployment
  USER-GUIDE.md         how to run the site day to day
  router.php            local preview helper only
```

To add a page: add a line to `app/routes.php`, a function in a controller, and
a template in `app/views/`. That is the whole framework.

## Security

- Every query is a prepared statement with bound parameters
- Every dynamic value in a template goes through `e()`; admin text is rendered
  as text, never as HTML, so pasted markup cannot execute
- CSRF token on every form that changes data, checked before anything is written
- Passwords stored with `password_hash()`; sign-in is rate limited to 5 attempts
  per IP per 15 minutes; session ID regenerated on login
- Uploads are decoded and re-encoded as JPEG, so a script renamed `.jpg` cannot
  survive; the uploads folder additionally refuses to execute anything
- Session cookie is HttpOnly, SameSite=Lax, and Secure once you are on https
- Content-Security-Policy, X-Content-Type-Options, X-Frame-Options and
  Referrer-Policy set on every response

## Data protection

- Inquiry forms carry an explicit consent checkbox, recorded against the inquiry
- The privacy policy that ships describes what the site actually does
- Any inquiry can be deleted outright from the admin, which removes the name,
  email, phone and message
- The dashboard flags inquiries older than the retention period set in config
  (two years by default). Nothing is deleted automatically — the decision is
  yours
- No analytics, no advertising, no third-party scripts, and only one cookie,
  set only when signing in to the admin

## Localization

The site is set up for a United States rental business — dollars, `/mo`,
`October 1, 2026`, City/State/ZIP, and property types and statuses in American
usage. None of that is hard-coded:

| Setting | Default | Effect |
|---|---|---|
| `currency_symbol` | `$` | Prefixes every price |
| `rent_period_label` | `/mo` | Follows every rent figure |
| `date_format` | `F j, Y` | Any PHP date format |
| `default_state` | `NJ` | Pre-selected on the property form |
| `fair_housing_note` | set | Footer line; clear it to remove |

Property fields cover what a US rental listing is expected to show: lease term,
pet policy, parking, year built, utilities included, and security deposit.

**A note on the fee and disclosure content.** The fee rows and the Disclosures
page ship as clearly marked placeholders, not as legal text. Amounts read
`SET YOUR AMOUNT` or `CONFIRM BEFORE PUBLISHING` on purpose. New Jersey has
specific rules on security deposits, disclosures and registration; those are
for the owner and their attorney to confirm, then enter through the admin.

## Testing

`docs/` contains no test runner. The site was verified with end-to-end browser
scripts: 58 checks across the public flows, the admin flows, three phone
viewports and a sweep that fails if any British spelling or term reaches a
page; plus 21 checks on branding, logo upload, currency and date formatting;
plus a contrast measurement that calibrates itself against known reference
values before it reports anything. The scripts live outside the repository —
ask if you want them added.
