# Liberia Tourism Management System

A web-based system for promoting Liberian tourist destinations and managing tour
packages, hotels, guides, events, bookings, payments and reports.

Built with HTML5, CSS3, vanilla JavaScript (ES6), PHP 8 and MySQL/MariaDB on XAMPP.
No frameworks and no build step.

## 1. Setup (XAMPP)

1. Copy the `liberia-tourism` folder into `C:\xampp\htdocs\`.
2. Open the **XAMPP Control Panel** and start **Apache** and **MySQL**.
3. Open <http://localhost/phpmyadmin>, click **Import**, choose `database.sql`
   from this folder and click **Go**. This creates the `liberia_tourism` database
   with all tables and sample data.
4. Open <http://localhost/liberia-tourism/>.

If your MySQL user is not `root` with an empty password, edit `config/db.php`.

## 2. Default accounts

| Role          | Email                       | Password       | Lands on            |
|---------------|-----------------------------|----------------|---------------------|
| Administrator | admin@visitliberia.test     | `Admin@2026`   | Admin panel         |
| Staff         | staff@visitliberia.test     | `Staff@2026`   | Admin panel (limited) |
| Tourist       | tourist@visitliberia.test   | `Tourist@2026` | My bookings         |

Change these passwords before showing the system to anyone outside the project team
(Admin panel > User accounts > Edit).

## 3. Who can do what

| Feature                                         | Visitor | Tourist | Staff | Admin |
|-------------------------------------------------|:-------:|:-------:|:-----:|:-----:|
| Browse and search destinations, hotels, packages, events, guides | ✔ | ✔ | ✔ | ✔ |
| Send an enquiry (contact form)                  | ✔ | ✔ | ✔ | ✔ |
| Register / sign in                              | ✔ | ✔ | ✔ | ✔ |
| Book packages and hotels, pay, print receipt    |   | ✔ |   |   |
| Cancel own bookings, write reviews, edit profile|   | ✔ |   |   |
| Manage destinations, counties, hotels, packages, guides, events | | | ✔ | ✔ |
| Confirm / complete / cancel bookings, record cash |   |   | ✔ | ✔ |
| Moderate reviews, answer enquiries, edit pages  |   |   | ✔ | ✔ |
| Reports (print, CSV export)                     |   |   | ✔ | ✔ |
| Edit home page, contact details, footer, menu links |  |  |   | ✔ |
| Create user accounts and assign roles           |   |   |   | ✔ |

### Content the admin can change (no code needed)

- **Home page and settings**: site name, logo, hero title/text/image/buttons, section
  headings, about block, three feature boxes, call-to-action, contact details,
  exchange rate, footer text and social links.
- **Menu links**: every link in the top navigation and both footer menus
  (label, address, position, shown/hidden).
- **Pages**: About, Travel Information, FAQ, Terms, Privacy, plus any new page.
- **Tourism data**: destinations, counties, hotels, packages, guides, events, with photos.

When no photo has been uploaded, a coloured placeholder with the name is shown.

## 4. Folder structure

```
liberia-tourism/
├── index.php              Home page
├── destinations.php       Destination list + filters      destination.php  details + reviews
├── packages.php           Tour package list + filters     package.php      details + booking form
├── hotels.php             Hotel list + filters            hotel.php        details + reservation form
├── events.php  guides.php  contact.php  page.php (CMS pages)
├── login.php  register.php  logout.php
├── book.php               Creates a booking (server-side price calculation)
├── api/search.php         JSON endpoint for the live search (fetch API)
├── tourist/               index (my bookings), pay, receipt, profile
├── admin/
│   ├── index.php          Dashboard
│   ├── bookings.php       Booking management
│   ├── reports.php        Reports, print and CSV export
│   ├── settings.php       Home page / contact / footer content
│   ├── crud.php           One generic add/edit/delete screen for every table
│   └── entities.php       Describes the tables and fields crud.php manages
├── includes/              functions.php, header.php, footer.php, cards.php
├── config/db.php          Database connection (PDO)
├── assets/css, assets/js  Stylesheets and JavaScript
├── uploads/               Uploaded photos
└── database.sql           Schema and sample data
```

## 5. Database tables (for the ER diagram and data dictionary)

| Table                  | Purpose                                   | Key relationships |
|------------------------|-------------------------------------------|-------------------|
| `users`                | Admin, staff and tourist accounts         | |
| `counties`             | The 15 counties of Liberia                | |
| `destinations`         | Tourist attractions                       | `county_id` → counties |
| `hotels`               | Accommodation                             | `county_id` → counties |
| `guides`               | Tour guides                               | |
| `packages`             | Tour packages                             | `guide_id` → guides |
| `package_destinations` | Which destinations a package visits (M:N) | → packages, → destinations |
| `events`               | Festivals and national celebrations       | `county_id` → counties |
| `bookings`             | A tourist's booking of a package or hotel | `user_id` → users, `package_id` → packages, `hotel_id` → hotels |
| `payments`             | Payment recorded for a booking (1:1)      | `booking_id` → bookings |
| `reviews`              | Ratings of destinations                   | `user_id` → users, `destination_id` → destinations |
| `enquiries`            | Contact form messages                     | |
| `settings`             | Editable site content (key/value)         | |
| `pages`                | Editable full pages                       | |
| `menu_links`           | Editable navigation links                 | |

Column details are in `database.sql`.

## 6. Security measures

- Passwords stored with `password_hash()` and checked with `password_verify()`.
- Every query uses PDO prepared statements (protection against SQL injection).
- All output passes through `e()` / `htmlspecialchars()` (protection against XSS).
- CSRF token on every form.
- Session ID regenerated at sign-in; role checked on every admin and tourist page.
- Uploads limited to real JPG/PNG/WEBP/GIF images under 3 MB, renamed randomly,
  and scripts cannot run from the `uploads/` folder.
- `config/`, `includes/`, `database.sql` and this file are blocked from the browser.

## 7. Notes and limits

- **Payments are simulated.** Choosing MTN Mobile Money or Orange Money records a
  transaction reference and marks the payment as paid; no real money moves.
- **Sample data.** Counties, attractions and public holidays are real. Hotels, guides,
  prices and phone numbers are invented for demonstration. The exchange rate
  (1 USD = 190 LRD) is a placeholder: set the current rate under
  Admin > Home page and settings > General.
- The site does not send email. Replies to enquiries are recorded in the admin panel.

## 8. Troubleshooting

- **"Database connection failed"**: MySQL is not running, or `database.sql` has not
  been imported.
- **MySQL will not start in XAMPP and the log says "Aria recovery failed"**: stop MySQL,
  move the files `aria_log.00000001` and `aria_log_control` out of
  `C:\xampp\mysql\data\` into a backup folder, then start MySQL again.
- **Photos do not upload**: make sure the `uploads` folder exists and is writable.
