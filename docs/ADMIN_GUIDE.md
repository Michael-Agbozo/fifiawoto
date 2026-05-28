# Admin dashboard — staff guide

This guide is for foundation staff using the admin dashboard at `/admin`. It explains what each section does and which role can see what.

---

## Signing in

Open `/admin` (or the homepage and click any admin link) and log in with the email + password your administrator gave you. Two-factor authentication is supported and recommended for Owner/Admin accounts — set it up under **Settings → Two-factor**.

If you forget your password, click **Forgot password?** on the login screen.

> Public sign-up is intentionally disabled. New staff accounts are created by an admin from **User management → Invite a teammate**. Each invite generates a one-time password emailed to the new user.

---

## What each section does

### Overview

- **Dashboard** — at-a-glance KPIs: applications received, donations recorded this month, upcoming events, recent contact messages.
- **Email inbox** — every message submitted through the public contact form lands here. You can reply directly from the inbox; replies are sent from the foundation's official address and stored as an audit trail.

### People

- **Beneficiaries** — the master list of people the foundation supports. Each record has demographics, the support category, status, assigned staff member, a Drive-style folder set for documents, and a timeline of every interaction.
- **Beneficiary applications** — new submissions from the public application form. You can review, request more info, approve (which converts the application into a beneficiary record and seeds the default folders), or reject.
- **Volunteers** — two views in one screen: pending volunteer applications (top) and the active roster (bottom). Approving an application moves the person to the roster and emails them a confirmation.

### Programmes

- **Events** — create, edit, archive, and delete programmes. Setting an event to **Published** also queues an invitation email to every active volunteer (200 at a time, queued — no UI delay). Each event has goal + raised totals and a list of donations linked to it.
- **Donations** — record every cash gift the foundation receives (mobile money, bank transfer, cheque, card, cash, other). Each entry can be tied to an event so the event's progress bar updates. Use **Export CSV** for finance handover.

### Media & Website

- **Media gallery** — assets shown on the public `/media` page. Each item is tagged with a category and optionally linked to an event, so on the public site a tile can drill through to the related event.
- **Instagram sync** — manage which Instagram posts surface in the home page's "Follow Our Journey" strip. Admins can paste real `instagram.com/p/...` permalinks; until the Graph API is connected, seeded highlights all link to the foundation's profile.
- **Testimonials** — quotes, photos, and optional video URLs shown on the home carousel and the `/testimonials` page. Featuring a testimonial promotes it to the home page.
- **Leadership team** — board members and advisors shown on the about page. Add a photo, role, optional bio, sort order, and toggle publish on/off.
- **Newsletter subscribers** — every signup from the public footer. Export CSV for use in Mailchimp/Mailerlite/etc.

### Operations

- **Reports** — analytics: donation totals over time by category and event, beneficiary counts, application throughput. Export as **CSV** or branded **PDF**.
- **User management** — invite teammates, change roles, edit per-user permission overrides, reset passwords, and remove accounts.
- **System logs** — audit trail of admin actions. Owner-only.

### Profile

- **Settings** — your own profile, password, and 2FA.

---

## Roles + what they can do

The platform has six roles, each with a default permission set. Owner and Admin see every section. The rest are scoped to what they need.

| Role                  | Sees                                                                                                       |
| --------------------- | ---------------------------------------------------------------------------------------------------------- |
| Super Admin (Owner)   | Everything, including system logs                                                                          |
| Admin                 | Everything except system logs                                                                              |
| Foundation Staff      | Beneficiaries, applications, events, donations, reports, inbox + read-only media/testimonials/instagram     |
| Volunteer Coordinator | Volunteers (full), beneficiaries (view), events (view), reports (view), inbox (view)                       |
| Media Manager         | Media, Instagram, testimonials, leaders, newsletter (full), events (view), inbox (view)                    |
| Volunteer             | Cannot access `/admin` — public site only                                                                  |

Need to grant a one-off exception? Use **User management → Permissions** on the user's row. The defaults from their role are locked-on; tick any extras to grant more access. Removing a role default isn't possible — change the role instead.

A full machine-readable list is in [PERMISSIONS.md](PERMISSIONS.md).

---

## Common tasks

### Invite a new staff account

1. **User management → Invite a teammate**
2. Fill in name, email, role
3. Save — a one-time password is displayed and emailed to the user. Share it via a secure channel (Signal, in-person) in case the email is delayed.
4. The new user is asked to change their password on first login.

### Record a donation

1. **Donations → Record donation**
2. Donor name + email + amount + currency
3. Pick the **payment method** (Cash, MoMo, Bank, Card, Cheque, Other) — the reference field hint changes to remind you what to capture (transaction ID, cheque number, etc.).
4. Link to an event if it was part of a campaign — the event's progress bar updates immediately.
5. Save. If the donor provided an email, a thank-you receipt is sent automatically.

### Publish a new event

1. **Events → New event**
2. Upload a 1080×1080 hero image (PNG, JPG, or WebP up to 5 MB).
3. Fill in description, activities, expected impact, volunteer roles, optional fundraising goal.
4. Status: **Draft** keeps it hidden; **Published** makes it live and queues an invitation email to every active volunteer.
5. Save.

### Reply to a contact-form message

1. **Email inbox → Open message**
2. Type your reply in the rich textbox.
3. **Send reply** — your message is sent from the foundation's official address, and a copy of the reply is stored on the message thread for the audit trail.

### Export a donation report

1. **Reports**
2. Set the date range and (optional) category filter.
3. **Download CSV** for spreadsheets or **Download PDF** for board packs.

---

## Photo + file uploads

All admin upload fields accept PNG, JPG, or WebP up to **5 MB**, except beneficiary documents which also accept PDF up to **10 MB**. Square aspect ratios are recommended for avatars (testimonials, leaders); landscape 16:9 or 5:3 for event/media hero images.

Uploads land under `storage/app/public/` and are served from `/storage/...`. Old files are cleaned up automatically when you replace or remove an image — no orphans.

---

## Security notes

- **Two-factor**: Owners and Admins should enable it from **Settings → Two-factor**.
- **Session timeout**: 2 hours of inactivity logs you out automatically.
- **Rate limiting**: Login throttles to 5 attempts per minute per email + IP combo. The public contact form is limited to 3 submissions per IP per minute.
- **You can't delete your own account.** Ask another Owner/Admin to do it.
- **Role escalation is blocked by middleware AND server-side checks** in every Livewire mutation method — even if someone manipulates the HTML, the server refuses unauthorised actions.

See [SECURITY.md](SECURITY.md) for the full model.

---

## Getting help

- Tech issues: open a ticket with the tech lead. Include the URL, what you tried, what happened, and a screenshot if visual.
- Process/training questions: ask in the foundation Slack `#admin` channel.
- Forgotten password: use the **Forgot password?** link on the login screen; if your email isn't recognised, contact an Owner to reset for you.
