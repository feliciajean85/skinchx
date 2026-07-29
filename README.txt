=== Plugin Name ===
Contributors: (this should be a list of wordpress.org userid's)
Donate link: https://encoderit.com/
Tags: comments, spam
Requires at least: 3.0.1
Tested up to: 3.4
Stable tag: 4.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Here is a short description of the plugin.  This should be no more than 150 characters.  No markup here.

== Description ==

This is the long description.  No limit, and you can use Markdown (as well as in the following sections).

For backwards compatibility, if this section is missing, the full length of the short description will be used, and
Markdown parsed.

A few notes about the sections above:

*   "Contributors" is a comma separated list of wp.org/wp-plugins.org usernames
*   "Tags" is a comma separated list of tags that apply to the plugin
*   "Requires at least" is the lowest version that the plugin will work on
*   "Tested up to" is the highest version that you've *successfully used to test the plugin*. Note that it might work on
higher versions... this is just the highest one you've verified.
*   Stable tag should indicate the Subversion "tag" of the latest stable version, or "trunk," if you use `/trunk/` for
stable.

    Note that the `readme.txt` of the stable tag is the one that is considered the defining one for the plugin, so
if the `/trunk/readme.txt` file says that the stable tag is `4.3`, then it is `/tags/4.3/readme.txt` that'll be used
for displaying information about the plugin.  In this situation, the only thing considered from the trunk `readme.txt`
is the stable tag pointer.  Thus, if you develop in trunk, you can update the trunk `readme.txt` to reflect changes in
your in-development version, without having that information incorrectly disclosed about the current stable version
that lacks those changes -- as long as the trunk's `readme.txt` points to the correct stable tag.

    If no stable tag is provided, it is assumed that trunk is stable, but you should specify "trunk" if that's where
you put the stable version, in order to eliminate any doubt.

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `wpamelia-addon.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php do_action('plugin_name_hook'); ?>` in your templates

== Frequently Asked Questions ==

= A question that someone might have =

An answer to that question.

= What about foo bar? =

Answer to foo bar dilemma.

== Screenshots ==

1. This screen shot description corresponds to screenshot-1.(png|jpg|jpeg|gif). Note that the screenshot is taken from
the /assets directory or the directory that contains the stable readme.txt (tags or trunk). Screenshots in the /assets
directory take precedence. For example, `/assets/screenshot-1.png` would win over `/tags/4.3/screenshot-1.png`
(or jpg, jpeg, gif).
2. This is the second screen shot

== Changelog ==

= 9.7.4 =
* NEW: Bulk import existing waitlist (per service) — collapsible "📥 Import existing waitlist from CSV" panel inside the Manual Entry card. Pick a service, paste rows from a spreadsheet (Excel / Google Sheets / Numbers — copy & paste straight in) OR upload a .csv/.txt file. Each row = Name, Email, Phone, Months. Months use 3-letter abbreviations (Jan, Feb, …) separated by semicolons or spaces (e.g. `Jan;Feb;Mar`) so they fit cleanly inside one CSV cell.
* Smart import safeguards: auto-detects tab vs. comma delimiter (spreadsheet copy-paste just works), optional "first row is a header" toggle, skips duplicate emails already on that service's list, skips rows missing a name or valid email, tags every imported row with source `import`. End-of-import summary tells you how many were saved / skipped / invalid. No confirmation emails are sent on import (treated as historical data).
* NEW: Phone column — schema migration adds `phone VARCHAR(60)` to `wp_amelia_bc_waitlist` (idempotent dbDelta). Surfaces in the entries table (with click-to-call tel: link), the manual Add Entry form, and the CSV export. Imports populate it from the third column.
* NEW: Demand chart now honours every filter — the per-service month-demand chart used to ignore Service and Status. It now respects all four Filter card inputs (From, To, Service, Status). Pick a single service to focus the chart on one card, or pick Status = `new` to see demand from people you haven't contacted yet.
* UI: Card order rearranged + Manual Entry compacted — Manual Entry now lives directly under "At a Glance"; Filter sits above the Demand chart so the chart's filters are always within reach. Manual Entry uses a 4-column header row (Name, Email, Phone, Service), flex-wrap month chips matching the frontend form's pill style, and a single shared row for Status + Notes — saves ~2 rows of vertical space.

= 9.7.3 =
* NEW: Customer-facing "you're on the waitlist" confirmation email — automatically sent right after a successful frontend signup AND optionally on manual admin add (checkbox in the manual-add form, default ON). Plain-and-friendly HTML template; uses your site name + admin email as the sender. Failures fall back gracefully — the signup itself never breaks if SMTP is down.
* NEW: WordPress-style pagination on the waitlist dashboard — pick 5 / 10 / 20 / 50 entries per page from a dropdown, plus First / Prev / Next / Last navigation. Per-page choice + filters preserved across page navigation. Replaces the old hard-cap of 500 rows.
* NEW: Waitlist form — when the Booking Slot Counter shows the "fully booked" panel, customers can now leave their name, email, and one or more preferred booking months (Jan–Dec multi-select). Every submission saves to a new `wp_amelia_bc_waitlist` table.
* NEW: Waitlist admin dashboard — Amelia Addon → Waitlist. Filter by service / status / date range, change per-row status (new / exported / contacted / booked), edit notes, manually add or remove entries.
* NEW: "Top Demanded Months" mini-charts — per-service bar chart (Jan–Dec) on the dashboard showing how many people requested each month, with the busiest month highlighted in mint green and the rest in pink. Sorted by total signups so the busiest service is on top. Respects the From/To date filter (ignores service+status filter so the breakdown is always complete).
* NEW: CSV export — exports the currently filtered list (UTF-8 with BOM for Excel) and (optionally) auto-flips matching `new` rows to `exported` so the doctor can see at a glance what's already been pulled.
* NEW: Manual entry form on the dashboard for adding people who reached out via phone / DM, plus the same status field and notes column.
* Cheap rate-limit on the public form (same email + 60s window) so refreshes don't create duplicate rows.
* Schema: new table `wp_amelia_bc_waitlist` (id, service_id, service_name_cache, name, email, preferred_months CSV, status, notes, ip_address, user_agent, source, created_utc, updated_utc). Created / migrated idempotently on every admin_init.

= 9.7.2 =
* PATCH (frontend display fix): Booking Slot Counter now ALSO renders via a JavaScript DOM injector for pages where Amelia is embedded through Elementor / Divi / Beaver Builder / Gutenberg widgets that mount Vue directly without going through do_shortcode(). The PHP shortcode filter still handles plain pages and Elementor's "Shortcode" widget. Robust to late-mounting Vue apps via polling + MutationObserver. No double-rendering when both paths fire on the same page.
* PATCH (per-service email merge): Decay-date auto-reminders now pull each service's own report email (saved on the body-chart admin panel — wp_amelia_service_chart.data.email) AND merge it with the global default recipients list (deduped, case-insensitive, invalid entries dropped). The "Default reminder recipient" field is now optional — if you've set a per-service email on every service that has a decay date, you don't need to fill it in at all. The schedule's status_message lists which service-specific email was added.
* NEW: Booking Slot Counter — display "X of Y slots booked — Z available" above your Amelia booking widget
* You enter the planned total per service (e.g. you've opened 50 slots for the next 30 days). The plugin counts how many appointments already exist in wp_amelia_appointments for that service in the future and shows the difference.
* When booked reaches the total, the configurable waitlist message replaces the counter automatically
* NEW: Decay date — "Slots valid through" date per service. After that date passes, the panel automatically switches to the fully-booked / waitlist message
* NEW: Decay-date auto-reminder — set a global "lead time" (e.g. 7 days), recipient email and time of day, and the addon auto-creates / updates / deletes a Group Report Reminder entry in the existing Scheduled Lists feature for each service that has a decay date
* Auto-managed schedules are tagged (source_marker = "bc:decay:<service_id>") so they never conflict with manually-created Scheduled Lists entries
* Per-service "Mark as Fully Booked" manual override (always shows the waitlist message)
* Per-service custom counter label and waitlist message overrides (with global defaults)
* Counter label variables: {booked} {total} {remaining} {service}
* Auto-injects above [ameliabooking] AND [ameliastepbooking]; per-page opt-out via hide_counter="1"
* Manual placement shortcodes: [amelia_booking_counter service_ids="1,2"] and [amelia_booking_full_message force="1"]
* Live status preview in the admin table — colour-coded chip (OK / Nearing capacity / Full / Decay passed) shows each service's current state at a glance
* Schema migration: scheduled_lists table gains `source_marker` column for tracking auto-managed schedules

= 9.7.1 =
* FIX: Booking Dashboard date filter — early-morning appointments (e.g. 7am) were leaking to the previous day's page because the date range was compared against UTC-stored bookingStart values as literal local-time strings
* Now converts the selected local date boundaries (00:00:00 and 23:59:59) to UTC before querying, so every appointment from 12:00 AM to 11:59 PM local time shows on the correct day
* NEW: Number Checker tool (Amelia Addon → Number Checker) — scans the Amelia customers table for phone numbers needing cleanup
* Now detects TWO kinds of issue: (1) missing country code (not starting with + or 00), and (2) non-digit characters (parens, dashes, commas, spaces, letters)
* Reason column in the results table tells you exactly why each number was flagged
* Clean & Apply strips every non-digit character (preserving a leading + if present), converts leading 00 to +, and prepends +XX to numbers that are missing a country code
* Country code input is OPTIONAL when every selected row already has a country code (i.e. you're only cleaning formatting)
* Live preview column shows exactly what each number will become before committing
* NEW: Amelia booking form — a small helpful note is now shown under the phone field: "The country code is required to receive SMS notifications." Purely informational, does not block the Continue button (relies on your Amelia default country code setting to guide the customer).

= 9.7.0 =
* NEW: Hover Thumbnail Preview — hover over any red spot with an attached photo and a floating preview pops up next to your cursor
* Hover preview shows the full photo thumbnail (up to 200px), lesion ID (if linked), and smart positioning (flips to avoid going off-screen)
* Cursor changes to "zoom-in" when hovering over a photo-attached spot, "crosshair" elsewhere — clear visual cue
* Preview auto-hides when you click a new spot or open the upload modal

= 9.6.9 =
* NEW: Admin notice badges/banners from other plugins (Yoast, WooCommerce, Elementor, Rank Math, WPForms, SeedProd, UpdraftPlus, iThemes, WPMU DEV, Jetpack, WP Rocket, SiteGround, etc.) and the WP core update nag are now suppressed on all Amelia Addon plugin pages
* Your own success/error messages still display normally — only third-party nags are hidden
* FIX: Photo Migration now scans the correct tables (wp_amelia_body_chart and wp_amelia_body_chart_ref) — previously looked in wp_amelia_customer_bookings.info which is why it couldn't find any photos
* FIX: Photo Migration now joins through customer_bookings to resolve the correct customerId per photo
* FIX: Photo Migration updates the source body-chart row after moving the file (previously updated the wrong table)
* NEW: "Reset & Re-scan" button on the Migration page — lets admins re-run the migration after a prior completion (e.g. to pick up new photos or retry after the earlier bug)
* Photo Migration now skips URLs already in /patient-photos/ (won't re-process already-migrated photos)
* NEW: Body Chart Marker Photos — clicking a red spot on any body chart canvas now opens an "Attach Photo" popup with two options:
    - Upload from Device (file picker)
    - Take Photo (opens mobile camera via HTML5 capture="environment")
* Marker photos are uploaded directly to the protected /wp-content/patient-photos/ folder — they NEVER enter the Media Library
* Each marker photo is saved to the Historical Photos DB (wp_amelia_patient_photos) with the exact marker coordinates, customer_id, appointment_id, and body_location
* Marker photos are auto-linked to nearby existing lesions (lesion_id), enabling automatic lesion tracking across visits
* Red spots with attached photos now show a small green camera badge on the canvas
* Undoing a marked spot also deletes its attached photo from the DB and protected storage
* On reload, existing marker photos from the patient_photos DB are auto-matched to red spots by coordinate proximity

= 9.6.8 =
* FIX: "Last Booked Appointment" box now updates live when you tick/untick services (via AJAX)
* NEW: "Use this date/time" button next to the last appointment — one-click copy into the reminder date/time pickers
* FIX: Scheduled Lists table "Timing" column now shows the reminder's picked date/time (instead of "24 hours before first appointment")
* FIX: Edit Schedule summary now shows the picked date/time for Group Report Reminder (live updates as you change pickers)
* Attendee list summary: Now also reflects before/after direction and last/first anchor accurately

= 9.6.7 =
* NEW: Preview button now supports Group Report Reminder schedules
* Reminder Preview: Shows resolved Subject + Body (with placeholders replaced), scheduled date/time, last appointment info, customer name, and recipients
* Reminder Preview: CSV section is hidden (not applicable to reminders)

= 9.6.6 =
* FIX: Group Report Reminder - Date/Time picker now saves correctly (was being overwritten by appointment-based recalculation)
* NEW: Separate default email template for Group Report Reminder in Settings (Subject + Body)
* NEW: Reminder email placeholders: {customer_name}, {last_appointment_date}, {last_appointment_time}, {hours_since_appointment}
* Group Report Reminder: Edit-schedule page now pulls the reminder default from Settings and swaps the default preview when switching schedule types
* Group Report Reminder: Status message now shows the scheduled local date/time (e.g. "Scheduled for Feb 20, 2026 5:00 PM")

= 9.6.5 =
* NEW: Group Report Reminder - New schedule type in Scheduled Lists
* Group Report Reminder: Shows last booked appointment date/time for selected services
* Group Report Reminder: Set specific date and time for the reminder email
* Group Report Reminder: Simple email notification - event finished, please send report
* Scheduled Lists: New schedule type selector (Attendee List Export vs Group Report Reminder)
* Database: Added schedule_type and time_direction columns

= 9.6.0 =
* NEW: Historical Photos page - search customers and view all their photos across appointments
* Historical Photos: Body region overview with photo counts per location (Face, Body Front, Body Back)
* Historical Photos: Automatic lesion tracking - photos at similar coordinates are linked together
* Historical Photos: Tracked Lesions panel showing unique lesions with first/last seen dates
* Historical Photos: Photo timeline view grouped by appointment date
* Historical Photos: Side-by-side comparison tool - compare up to 4 photos to track changes
* NEW: Photo Migration Tool - Move existing photos from Media Library to protected storage
* Photo Migration: Scans body chart data for all existing patient photos
* Photo Migration: Moves files to protected wp-content/patient-photos/ directory with .htaccess
* Photo Migration: Updates all body chart data references and removes from Media Library
* NEW: AI Lesion Analysis - GPT-4o Vision integration for comparing photos
* AI Analysis: Detects size, color, border, shape, and surface changes between photos
* AI Analysis: ABCDE criteria assessment (Asymmetry, Border, Color, Diameter, Evolution)
* AI Analysis: Risk level assessment (Low/Medium/High concern) with clinical recommendations
* AI Analysis: Save analysis results to patient record for documentation
* Database: New wp_amelia_patient_photos table for secure photo storage with lesion tracking
* Security: Protected photos directory with .htaccess restrictions
* Referral Report: DOB field restored as manual entry (auto-population removed)

= 9.5.1 =
* Referral Report: Signature image constrained to 300px width, left-aligned.

= 9.5.0 =
* Performance updates.

= 9.3.5 =
* Referral Chart: Empty image boxes (N/A) are now hidden during PDF generation; only uploaded images print, centered in the row. Preview still shows all 4 boxes.
* Program Materials: Fixed mobile preview dead space caused by CSS transform scaling; replaced with zoom-based scaling for proper layout flow.
* Program Materials: Fixed PDF generation on mobile producing incorrect sizes; generating mode now fully overrides all mobile-responsive styles for consistent desktop-quality output.
* Updated version constant and header to 9.3.5.

= 9.3.4 =
* Referral Chart: Images displayed in single row, 200x200px with 20px green border, N/A shown for empty slots.

= 9.3.3 =
* Mobile responsive fixes: buttons at 75% width on mobile screens.
* PDF generation forced desktop sizing at 1200px.

= 9.3.2 =
* Fixed duplicate Create Schedule button.
* Fixed PDF generation/preview sizing.

= 1.0 =
* Initial release.

== Upgrade Notice ==

= 1.0 =
Upgrade notices describe the reason a user should upgrade.  No more than 300 characters.

= 0.5 =
This version fixes a security related bug.  Upgrade immediately.

== Arbitrary section ==

You may provide arbitrary sections, in the same format as the ones above.  This may be of use for extremely complicated
plugins where more information needs to be conveyed that doesn't fit into the categories of "description" or
"installation."  Arbitrary sections will be shown below the built-in sections outlined above.

== A brief Markdown Example ==

Ordered list:

1. Some feature
1. Another feature
1. Something else about the plugin

Unordered list:

* something
* something else
* third thing

Here's a link to [WordPress](http://wordpress.org/ "Your favorite software") and one to [Markdown's Syntax Documentation][markdown syntax].
Titles are optional, naturally.

[markdown syntax]: http://daringfireball.net/projects/markdown/syntax
            "Markdown is what the parser uses to process much of the readme file"

Markdown uses email style notation for blockquotes and I've been told:
> Asterisks for *emphasis*. Double it up  for **strong**.

`<?php code(); // goes in backticks ?>`