# Dadaa Fifiawoto Nyamadi Foundation — Product Requirements

> Source: Google Doc shared by stakeholder, transcribed verbatim and reorganised for engineering reference. Treat this as the source of truth for scope. When a screenshot or follow-up note conflicts with this document, the newer artefact wins — log the change here.

---

## 1. Brand & Site Identity

- **Name:** Dadaa Fifiawoto Nyamadi Foundation
- **Mission:** Empower underserved communities and honour the legacy of Madam Dadaa Fifiawoto Nyamadi-Adabla through sustainable programs focused on women, children, and vulnerable individuals.
- **Vision:** A world where every individual has the opportunity to build a brighter future regardless of circumstance.
- **Core Values:** Compassion · Service · Empowerment · Inclusivity · Sustainability
- **Countries of operation:** United States, Ghana, Togo, Benin

---

## 2. Global Navigation

Primary nav: Home · About · Events · Volunteer · Contact

- Primary CTA button (highlighted): **Donate**
- Secondary CTA button: **Get Involved**

---

## 3. Public Pages

### 3.1 Home

**Hero**
- Background: cinematic video or image slider of outreach work.
- Headline: "Empowering New Beginnings"
- Subheadline: "Transforming lives, one step at a time through compassion, service, and community empowerment."
- Intro paragraph (3 sentences) covering the foundation's origin and remit.
- Buttons: Donate Now · Volunteer With Us

**Impact Dashboard ("Our Impact in Numbers")** — live metrics:
- Children Supported (e.g. 520+)
- Women Empowered (e.g. 230+)
- Communities Reached (e.g. 18+)
- Countries Impacted (4)
- Volunteer Hours Contributed (e.g. 2,000+)

**Founder Tribute — "A Legacy of Compassion"**
- Portrait photo, short biography, link to full story on About page.

**Programs Section — "Our Programs and Initiatives"**
1. Women Empowerment
2. Child Education Support
3. Support for Vulnerable Populations
4. Community Development
5. Global Outreach

**Media Gallery — "Our Work in Action"**
- Photo grid + short documentary clips.
- Optional categories: Community Outreach · Education Support · Volunteer Activities · Events.

**Instagram Integration — "Follow Our Journey"**
- 6–9 live posts pulled from the foundation IG. CTA: "Follow Us on Instagram".

**Testimonials — "Stories of Impact"**
- Mix of video + photo/quote testimonials (volunteer, community member, beneficiary).

**Featured Event — "Upcoming Community Outreach"**
- Card with summary, donation progress bar (goal $10,000, raised $4,300 example), and buttons: Learn More · Donate to This Event · Volunteer.

**Newsletter Signup — "Stay Connected"**
- Fields: Name, Email Address. Button: Subscribe.

### 3.2 About

- Our Legacy
- Mission · Vision · Core Values
- Global Presence Map (interactive — US, Ghana, Togo, Benin)
- Leadership
  - **Board of Directors:** Victoria Nyamadi, Bless Amago, Sarah Nyamai, Gladys Kplorla Nyamadi, R.E. Amedzekor, Daniel Gbetodeme, Togbui Gbe, Ama Baffoe, Sabrina Nyamadi
  - **Board of Advisors:** Prof Lebene, Dr. Kaledzi

### 3.3 Events (dynamic blog)

- Index page with intro paragraph and event cards (date, location, summary, Read More / Donate / Volunteer buttons).
- **Event Detail Page** sections:
  - Hero image + title
  - Event Overview
  - Program Activities
  - Expected Impact
  - Volunteer Opportunities
  - Photo Gallery
  - Donation Section (goal, raised, progress bar)
  - Buttons: Donate · Volunteer

### 3.4 Volunteer

- Headline + intro.
- Volunteer opportunities list (Community outreach, Education support, Event coordination, Administrative support, Media and communications).
- **Application form** fields: Full Name, Email, Phone, Country/Location, Areas of Interest (dropdown), Availability (Weekdays/Weekends/Flexible), Skills/Experience, Motivation, Consent checkbox.
- Button: Apply to Volunteer.

### 3.5 Contact

- Headline + intro.
- **Form** fields: Full Name, Email, Phone, Subject (dropdown: General Inquiry · Volunteer Information · Donation Inquiry · Partnership Opportunity), Message, Consent checkbox.
- Button: Send Message.

### 3.6 Footer (global)

- Foundation summary paragraph.
- Quick links: Home, About, Events, Volunteer, Contact.
- Social: Instagram, Facebook, YouTube.
- Newsletter signup (email + subscribe).

---

## 4. Admin Backend

### 4.1 Main menu
Dashboard · Beneficiaries · Events · Donations · Volunteers · Testimonials · Media Gallery · Instagram Sync · Reports · User Management · Settings

### 4.2 Dashboard (admin home)
- Impact Overview cards: Total Beneficiaries · Active Volunteers · Total Donations Received · Upcoming Events.
- Recent Activities feed: new volunteer applications, recent donations, new beneficiary records, latest reports.
- Quick Actions: Add Beneficiary · Create Event · Upload Media · Review Volunteer Applications.

### 4.3 Beneficiaries — "People We Help"

Subsections: All Beneficiaries · Add Beneficiary · Beneficiary Applications · Documents & Folders.

**Add Beneficiary form fields:** Full Name, Date of Birth, Gender, Phone, Email (optional), Country, Region/City, Category of Support (Widow Support · Child Education · Medical Assistance · Disability Support · Community Aid · Other), Description of Situation (long text), Support Status (Pending Review · Approved · Active Support · Completed), Assigned Staff Member, Notes, Initial Documents upload.

**Beneficiary Profile page**
- Left column: photo, basic info.
- Right column: case description, support history timeline, staff notes.
- Bottom: document folders.

**Document & Folder system**
- Default folders (admin-creatable): Medical Records, School Documents, Photos, Support Reports, Identification Documents.
- Folder actions: Create · Rename · Delete · Upload Files · Upload Folder.
- File types accepted: PDF, Images, Videos, Word, Excel.
- Each file stores: filename, uploaded by, upload date, description.

**Beneficiary Applications (public-facing form)**
- Statuses: New · Under Review · Approved · Rejected.
- Fields: Full Name, Phone, Email, Country, City/Region, Type of Assistance (Widow · Education · Medical · Disability · Community), Describe Your Situation, File uploads (medical/school/ID).

### 4.4 Events admin
- Create, Edit, Upload Images, Track Donations, Manage Volunteers.
- Event fields: Title, Date, Location, Description, Goal Amount, Gallery.

### 4.5 Donations admin
- Dashboard: total · per-event · recent.
- Donation record: Donor Name, Email, Amount, Payment Method, Event (optional), Date.
- Per-event fundraising tracker (goal · raised · % progress). Exportable reports.

### 4.6 Volunteers admin
- Submenu: Applications · Active Volunteers · Volunteer Roles.
- Actions: approve, reject, assign role (Event Volunteer · Community Outreach · Administrative · Media).

### 4.7 Testimonials admin
- Fields: Name, Role, Photo, Testimonial text, optional Video link.

### 4.8 Media Gallery admin
- Upload Photos, Videos, Event Galleries.
- Categories: Community Outreach · Education Programs · Events · Volunteers.

### 4.9 Instagram Sync
- Manual mode: paste post links.
- Auto mode: pull from IG API.
- Approve / hide posts for site display.

### 4.10 Reports
- Categories: Beneficiaries supported · Donations · Volunteer activity · Events.
- Export: PDF, Excel.

### 4.11 User Management
- Roles: Super Admin · Foundation Staff · Volunteer Coordinator · Media Manager.
- Permissions enforced (example: Volunteer coordinator can't delete beneficiaries; Media manager only manages gallery).

### 4.12 Settings
- Org details, branding, social links, donation goals, hero media, etc.

---

## 5. File Storage Structure

```
storage/app/
  beneficiaries/
    beneficiary_{id}/
      medical_records/
      photos/
      documents/
      school_documents/
  events/
    event_{id}/
      gallery/
  reports/
  volunteers/
    applications/
```

## 6. Security Baseline

- Per-file upload size limits (configurable; default 20MB for documents, 200MB for video).
- Virus scanning hook (queue job; pluggable backend — ClamAV or similar).
- Role-based access control on every admin route and Livewire action.
- Encrypted at-rest storage for sensitive folders (Medical Records, Identification Documents).

---

## 7. Optional / Recommended

- **Case Timeline System** per beneficiary — events: Application Received · Case Reviewed · Support Approved · Aid Delivered · Follow-up Visit. Surfaces in profile & reports.

---

## 8. Open Questions

> Track here. Resolve with stakeholder before the relevant phase.

- Payment processor for donations? (Stripe, Paystack, Flutterwave, manual?)
- Domain + brand assets (logo, fonts, photography) — pending delivery.
- Hosting target & file storage backend (local disk vs S3) — affects beneficiary doc encryption choice.
- Instagram API access — Graph API token availability vs scraping fallback.
- Email provider for newsletter + transactional mail.
- Languages — English only at launch, or also French (Togo/Benin)?
