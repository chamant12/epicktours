# EPICK Tours Sri Lanka – website & CMS

Plain PHP (no framework) with a MySQL database and an admin CMS built on the
SB Admin 2 theme.

**Requirements:** PHP 8.0+ with the `pdo_mysql` and `gd` extensions (GD with
WebP support recommended), MySQL 5.7+ or MariaDB 10.3+.

## Installing

1. Create an empty MySQL database (e.g. `epicktours`).
2. Put the database details in `includes/config.php` – or better, copy the
   `DB_*` lines into a new `includes/config.local.php` (ignored by git).
3. Make sure the web server can write to the `uploads/` folder.
4. Open `/install.php` in the browser, enter your name, email and password.
   This creates the tables, loads the starting content and your admin account.
5. **Delete `install.php`** from the server.
6. Log in at `/admin/`.

Local testing: `php -S localhost:8000` in this folder, then open
http://localhost:8000/install.php.

## Users and roles

| Role | Can do |
|---|---|
| **Administrator** | Everything: content, enquiries, users, settings |
| **Content Writer** | Pages, tours, destinations, experiences, home themes, images |

Admins add more content writers (or admins) in Admin → Users → **New content
writer**. Users are never deleted – deactivate them in Admin → Users. Accounts lock for
15 minutes after 5 wrong passwords. There must always be one active admin.

## What the CMS edits

- **Pages** – every heading, text, button label and image on Home, Tours,
  Destinations, Experiences, About, Contact and the footer. The editable fields
  are defined in `includes/cms-fields.php`; add a field there and use
  `block('page', 'key')` in the page to make more things editable.
- **Tours** – details, itinerary, inclusions, exclusions, hotels, highlights,
  destinations, main image and photo gallery (add, replace, reorder, delete).
- **Destinations, Experiences, Home page themes** – add, edit, hide, delete.
- **Enquiries** (admin) – everything sent from the contact form, with status,
  search and Excel/CSV export.
- **Settings** (admin) – site name, logo, contact details, currency, tour
  categories, SEO defaults, contact form email/SMTP settings.
- **SEO** – every page (Content → Pages) and every tour has its own page
  title, meta description and meta keywords, with character counters. Empty
  fields fall back to automatic titles and the defaults in Settings.

## Contact form

- **Captcha:** a 4-character image code (`captcha.php`, drawn with GD – no
  external service). Not case sensitive, valid 15 minutes, single use, with a
  refresh button. There is also a hidden honeypot field and a CSRF token.
- **Email to admin:** each enquiry is saved to the database and emailed (as a
  formatted table, with Reply-To set to the customer) to the addresses in
  Settings → Contact form emails, or to every active administrator if that is
  empty. Fill in the **SMTP** settings for reliable delivery and press **Send
  test email** to check. Without SMTP, PHP `mail()` is used. If sending fails
  the enquiry is still saved and the error goes to the PHP error log.
- The SMTP password is stored in the database `settings` table – unless the
  SMTP details are set in `includes/config.local.php` (see below), which then
  override the Settings fields.

### Sending through Zoho Mail (current setup)

Admin → Settings → Contact form emails:

| Field | Value |
|---|---|
| SMTP server | `smtppro.zoho.com` (company domain mailbox). Use `smtppro.zoho.in` / `smtppro.zoho.eu` if the Zoho account is in the India / EU data centre (check the address bar when logged in to mail.zoho.*). Free personal accounts use `smtp.zoho.com`. |
| SMTP port / encryption | `465` + SSL (or `587` + TLS) |
| SMTP username | the full Zoho address, e.g. `info@epicktours.com` |
| SMTP password | the mailbox password – or, if two-factor login is on, an **app-specific password** from Zoho Accounts → Security → App Passwords |
| "From" email address | **the same address as the username** (or one of its Zoho aliases) – Zoho refuses anything else |

Save, then press **Send test email**. If SMTP values are also defined in
`includes/config.local.php`, those win over these fields (the Settings page
says so).

### Sending through Amazon SES

1. In the AWS console open **Amazon SES** in your region (e.g. `ap-south-1`
   Mumbai) → **Identities** → **Create identity** → **Domain**, and add the
   DKIM CNAME records it shows to your domain's DNS. Wait until it says
   *Verified*. (Also add an SPF/DMARC record – SES shows recommendations.)
2. **SMTP settings** → **Create SMTP credentials**. Save the SMTP username and
   password – these are *not* your IAM access keys, and only work in that region.
3. New SES accounts are in the **sandbox**: you can only send *to* verified
   addresses. Either verify each admin's address under Identities, or use
   **Account dashboard → Request production access**.
4. Add to `includes/config.local.php`:
   ```php
   define('SMTP_HOST', 'email-smtp.ap-south-1.amazonaws.com'); // your region
   define('SMTP_PORT', 587);
   define('SMTP_ENCRYPTION', 'tls');
   define('SMTP_USERNAME', 'your-ses-smtp-username');
   define('SMTP_PASSWORD', 'your-ses-smtp-password');
   ```
   (Or enter the same values in Admin → Settings → Contact form emails.)
5. In Admin → Settings set **"From" email address** to an address on the
   verified domain (e.g. `noreply@yourdomain.lk`), save, and press
   **Send test email**.

Many hosts block outbound port 25 – SES works on 587 (TLS) or 465 (SSL).
On EC2, ports 587/465 are open by default.

## Images

Every uploaded image is processed automatically:

- resized to the space it is used in (sizes in `$IMAGE_SPECS` in
  `includes/cms-fields.php`, e.g. home hero 1920×1000, tour photos 1600×800),
  centre-cropped to fit, never enlarged;
- tours and destinations also get a smaller copy for cards and thumbnails;
- phone photos are rotated the right way up and metadata (GPS etc.) stripped;
- saved as WebP at quality 80 (JPEG/PNG if the server's GD lacks WebP).

Replacing or removing an image deletes the old file. Files are stored in
`uploads/YYYY/MM/`. Spaces without an image show a green gradient.

Upload limit: 15 MB (`MAX_UPLOAD_BYTES` in config). PHP's `upload_max_filesize`
and `post_max_size` must be at least that big.

## Security notes

- `includes/`, `install/` and admin partials are blocked by `.htaccess`
  (Apache). On **Nginx**, add:
  ```nginx
  location ~ ^/(includes|install)/ { deny all; }
  location ~ ^/admin/_ { deny all; }
  location ~ ^/uploads/.*\.php$ { deny all; }
  ```
- All forms use CSRF tokens; passwords are hashed with `password_hash`.
