# Property letting website

A small, self-contained website for a landlord or letting agent: public
property listings with search, an enquiry form on every property, a fees page,
a contact page, and an admin area the owner can run without a developer.

Built in plain PHP 8 with a SQLite database. No Composer, no npm, no build
step, no database server. Upload the folder and it runs.

- **Installation:** [docs/INSTALL.md](docs/INSTALL.md)
- **Day-to-day use:** [docs/USER-GUIDE.md](docs/USER-GUIDE.md)

---

## What is in it

**Public site**

- Property listings with filters: search text, type, town, bedrooms, rent
  range, furnishing, and sort order
- A page per property: photo gallery, specification table, description,
  features, and an enquiry form
- Fees and charges page (Tenant Fees Act style breakdown)
- Contact page with a general enquiry form, opening hours and an optional map
- Editable About, Privacy and Terms pages
- `sitemap.xml` and `robots.txt` generated from the live content
- Responsive down to a 360px phone; works with JavaScript disabled

**Admin area** (`/admin`)

- Dashboard with counts and the latest enquiries
- Properties: add, edit, feature, archive, restore, delete; multi-photo upload
  with a choice of cover image
- Enquiries: inbox with statuses (new / read / replied / closed), private
  notes, and one-click delete for a data request
- Fees: add, edit, reorder, hide or remove any charge
- Website text: the About, Privacy and Terms pages
- Settings: business name, home page wording, contact details, notification
  address, and the two brand colours

## Layout of the code

```
app/
  config.php            settings (override with config.local.php)
  db.php                schema, migrations and first-run demo content
  helpers.php           escaping, URLs, sessions, CSRF, uploads, mail
  auth.php              admin sign-in and login rate limiting
  properties.php        property and enquiry queries
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

- Enquiry forms carry an explicit consent checkbox, recorded against the enquiry
- The privacy policy that ships describes what the site actually does
- Any enquiry can be deleted outright from the admin, which removes the name,
  email, phone and message
- The dashboard flags enquiries older than the retention period set in config
  (two years by default). Nothing is deleted automatically — the decision is
  yours
- No analytics, no advertising, no third-party scripts, and only one cookie,
  set only when signing in to the admin

## Testing

`docs/` contains no test runner — the site was verified with an end-to-end
browser script covering 37 checks across the public flows, the admin flows and
three phone viewports. The script lives outside the repository; ask if you want
it added.
