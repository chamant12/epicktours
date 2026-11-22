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
- The SMTP password is stored in the database `settings` table.

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
