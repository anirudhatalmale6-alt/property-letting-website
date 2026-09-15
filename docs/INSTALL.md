# Installation

The site is plain PHP with a SQLite database. There is no build step, no
Composer, no npm, and no database server to set up — you upload the folder and
it runs.

## What the server needs

| Requirement | Notes |
|---|---|
| PHP 8.0 or newer | Tested on 8.3. PHP 7.4 works if you remove the `declare(strict_types=1)` lines. |
| `pdo_sqlite` | Almost always enabled by default. |
| `gd` | Used to resize and re-encode uploaded photographs. |
| Apache with `mod_rewrite` | For the tidy URLs. See the nginx note below if you are not on Apache. |

Nothing else. No MySQL, no Node, no cron jobs.

## Installing on cPanel or similar shared hosting

1. Upload the whole project folder to your account.

2. **If you can point the domain at a sub-folder** (cPanel: Domains → Manage →
   Document Root), point it at `.../public`. This is the tidier option: it puts
   the application code and the database physically outside the web root.

3. **If you cannot change the document root**, upload everything into
   `public_html` instead. The `.htaccess` in the project root serves the site
   from `public/` and blocks direct access to `app/`, `data/` and `docs/`.

4. Make two folders writable by PHP (in cPanel's File Manager, Permissions →
   755, or 775 if 755 does not work on your host):

   - `data/`
   - `public/uploads/`

5. Visit your domain. The database is created and filled with demo content on
   the first request.

6. Go to `/admin` and sign in:

   - Username: `admin`
   - Password: `changeme`

7. **Change that password immediately** under Your account. The admin area will
   keep warning you until you do.

8. Delete the six demo properties (Properties → open each → Archive → Delete)
   and the demo photographs will go with them.

## Configuration

Everything day-to-day is in the admin under Settings. For the few things that
are not, create `app/config.local.php`:

```php
<?php
return [
    'env'        => 'prod',                  // 'dev' shows errors on screen
    'mail_from'  => 'website@yourdomain.co.uk',
    'mail_from_name' => 'Your Business Name',
    'enquiry_retention_days' => 730,
];
```

That file overrides `app/config.php` and is ignored by git, so your live
settings never end up in the repository. Do not edit `app/config.php` itself —
an update would overwrite it.

### Email

Enquiry notifications use PHP's `mail()`, which works on most shared hosting.
Set `mail_from` to an address **at your own domain** — a From address at
gmail.com or similar will be rejected or spam-filed by the receiving server.

If your host blocks `mail()`, swap the body of `send_mail()` in
`app/helpers.php` for an SMTP library such as PHPMailer. It is one function and
it is the only place mail is sent from.

An enquiry is always written to the database before any mail is attempted, so a
mail problem never loses you a lead — it will still be in the admin inbox.

## HTTPS

Install a certificate (cPanel gives you free Let's Encrypt under SSL/TLS
Status) and force https by adding this to the top of the `.htaccess` inside
`public/`, just after `RewriteEngine On`:

```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

The session cookie automatically marks itself Secure once the site is served
over https.

## Staging alongside live

Copy the folder to a second location — for example `staging.yourdomain.co.uk` —
and it runs completely independently, with its own `data/site.sqlite` and its
own uploads. Nothing is shared between the two.

To copy live content into staging, copy `data/site.sqlite` and the contents of
`public/uploads/` across. To go the other way, do the same in reverse. That is
the whole deployment story.

## Backups

Two things matter:

- `data/site.sqlite` — every property, enquiry, fee and setting
- `public/uploads/` — the photographs

Download both and you have a complete backup. To restore, put them back.

A scheduled copy is worth setting up. If your host offers cron:

```
0 3 * * * cd /home/USER/public_html && tar -czf ~/backups/site-$(date +\%F).tar.gz data public/uploads
```

## Running it on your own machine

```
php -S localhost:8000 -t public docs/router.php
```

Then open http://localhost:8000. `docs/router.php` only exists to make PHP's
built-in server behave like Apache; it takes no part in the live site.

## nginx instead of Apache

`.htaccess` is ignored by nginx. The equivalent server block:

```nginx
root /var/www/yoursite/public;
index index.php;

location / {
    try_files $uri $uri/ /index.php?$query_string;
}

# never execute anything in the uploads folder
location ~ ^/uploads/.*\.(php|phtml|phar)$ {
    deny all;
}

location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
```

Also make sure `data/` sits outside the `root` directory, or add a `location
/data { deny all; }` block.

## Upgrading later

New tables and columns are added by `migrate()` in `app/db.php`, which runs on
every request and only creates what is missing. Replacing the `app/` and
`public/assets/` folders with a newer version is safe — your database and
uploads are untouched.
