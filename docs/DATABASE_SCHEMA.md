# Database Schema

> All tables include `id`, `created_at`, `updated_at` unless noted. SQLite + MySQL/Postgres compatible. Soft deletes only where listed.

## Auth (existing)

- `users` (Laravel default + 2FA columns from Fortify scaffold)
  - Add column: `role` enum default `volunteer` — see `App\Enums\UserRole`
  - Add column: `phone` nullable string
  - Add column: `avatar_path` nullable string

## Beneficiaries

### `beneficiaries`
| column                  | type                              | notes                                                              |
| ----------------------- | --------------------------------- | ------------------------------------------------------------------ |
| `full_name`             | string                            |                                                                    |
| `date_of_birth`         | date nullable                     |                                                                    |
| `gender`                | enum (male/female/other) nullable |                                                                    |
| `phone`                 | string nullable                   |                                                                    |
| `email`                 | string nullable                   |                                                                    |
| `country`               | string                            |                                                                    |
| `region`                | string nullable                   |                                                                    |
| `category`              | enum                              | widow_support · child_education · medical · disability · community · other |
| `description`           | text                              |                                                                    |
| `status`                | enum                              | pending_review · approved · active · completed                     |
| `assigned_to_user_id`   | foreignId nullable → users.id     | staff member responsible                                           |
| `photo_path`            | string nullable                   |                                                                    |
| `notes`                 | text nullable                     | internal staff notes                                               |
| `source_application_id` | foreignId nullable → beneficiary_applications.id | populated if created from a public application       |
| soft deletes            |                                   |                                                                    |

Indexes: `status`, `category`, `country`.

### `beneficiary_folders`
| column            | type                          | notes                                       |
| ----------------- | ----------------------------- | ------------------------------------------- |
| `beneficiary_id`  | foreignId → beneficiaries.id  | cascade delete                              |
| `name`            | string                        |                                             |
| `slug`            | string                        | unique per beneficiary                      |
| `created_by`      | foreignId nullable → users.id |                                             |

Default folders auto-created on beneficiary insert: Medical Records, School Documents, Photos, Support Reports, Identification Documents.

### `beneficiary_documents`
| column            | type                                 | notes                                                |
| ----------------- | ------------------------------------ | ---------------------------------------------------- |
| `beneficiary_id`  | foreignId → beneficiaries.id         |                                                      |
| `folder_id`       | foreignId nullable → beneficiary_folders.id |                                                |
| `disk`            | string                               | typically `beneficiaries`                            |
| `path`            | string                               |                                                      |
| `original_name`   | string                               |                                                      |
| `mime_type`       | string                               |                                                      |
| `size_bytes`      | unsignedBigInteger                   |                                                      |
| `description`     | text nullable                        |                                                      |
| `uploaded_by`     | foreignId nullable → users.id        |                                                      |
| `scan_status`     | enum                                 | pending · clean · infected · failed                  |
| `scan_checked_at` | timestamp nullable                   |                                                      |

### `beneficiary_applications` (public submissions)
| column                 | type                       | notes                                                                  |
| ---------------------- | -------------------------- | ---------------------------------------------------------------------- |
| `full_name`            | string                     |                                                                        |
| `phone`                | string                     |                                                                        |
| `email`                | string nullable            |                                                                        |
| `country`              | string                     |                                                                        |
| `region`               | string nullable            |                                                                        |
| `assistance_type`      | enum                       | same set as `beneficiaries.category`                                   |
| `situation`            | text                       |                                                                        |
| `status`               | enum                       | new · under_review · approved · rejected                               |
| `reviewer_id`          | foreignId nullable → users.id |                                                                     |
| `reviewed_at`          | timestamp nullable         |                                                                        |
| `converted_beneficiary_id` | foreignId nullable → beneficiaries.id | set once approved & converted to active beneficiary record |

### `beneficiary_application_documents`
Same shape as `beneficiary_documents` but FK is `beneficiary_application_id`. Stays separate so we don't pollute the main doc table with unreviewed uploads.

### `beneficiary_timeline_entries` (case timeline — PRD §7)
| column           | type                          | notes                                                |
| ---------------- | ----------------------------- | ---------------------------------------------------- |
| `beneficiary_id` | foreignId → beneficiaries.id  |                                                      |
| `type`           | enum                          | application_received · case_reviewed · support_approved · aid_delivered · followup_visit · note |
| `description`    | text nullable                 |                                                      |
| `occurred_at`    | timestamp                     |                                                      |
| `recorded_by`    | foreignId nullable → users.id |                                                      |

## Events

### `events`
| column          | type                                       | notes                                  |
| --------------- | ------------------------------------------ | -------------------------------------- |
| `title`         | string                                     |                                        |
| `slug`          | string unique                              |                                        |
| `starts_at`     | timestamp                                  |                                        |
| `ends_at`       | timestamp nullable                         |                                        |
| `location`      | string                                     |                                        |
| `country`       | string                                     |                                        |
| `description`   | longText                                   |                                        |
| `activities`    | longText nullable                          | rendered as bulleted list              |
| `expected_impact` | longText nullable                        |                                        |
| `volunteer_opportunities` | longText nullable                |                                        |
| `goal_amount`   | unsignedInteger nullable                   | cents                                  |
| `hero_image_path` | string nullable                          |                                        |
| `status`        | enum                                       | draft · published · archived           |
| `published_at`  | timestamp nullable                         |                                        |
| soft deletes    |                                            |                                        |

### `event_images`
| column   | type                  |
| -------- | --------------------- |
| `event_id` | foreignId → events.id |
| `path`   | string                |
| `caption`| string nullable       |
| `sort`   | unsignedSmallInteger  |

## Donations

### `donations`
| column              | type                              | notes                          |
| ------------------- | --------------------------------- | ------------------------------ |
| `event_id`          | foreignId nullable → events.id    |                                |
| `donor_name`        | string                            |                                |
| `donor_email`       | string nullable                   |                                |
| `amount_cents`      | unsignedBigInteger                | store in minor units           |
| `currency`          | string(3)                         | default `USD`                  |
| `payment_method`    | string                            | manual · stripe · paystack …  |
| `external_reference`| string nullable                   | gateway reference id           |
| `received_at`       | date                              |                                |
| `recorded_by`       | foreignId nullable → users.id     |                                |
| `notes`             | text nullable                     |                                |

Per-event raised totals are computed (`SUM(amount_cents) WHERE event_id = ?`).

## Volunteers

### `volunteer_applications`
| column            | type                       | notes                                                 |
| ----------------- | -------------------------- | ----------------------------------------------------- |
| `full_name`       | string                     |                                                       |
| `email`           | string                     |                                                       |
| `phone`           | string                     |                                                       |
| `country`         | string                     |                                                       |
| `interests`       | json                       | array of slugs from VolunteerInterest enum            |
| `availability`    | enum                       | weekdays · weekends · flexible                        |
| `skills`          | text nullable              |                                                       |
| `motivation`      | text                       |                                                       |
| `consented_at`    | timestamp                  | when consent checkbox accepted                        |
| `status`          | enum                       | new · approved · rejected                             |
| `reviewer_id`     | foreignId nullable → users.id |                                                    |
| `reviewed_at`     | timestamp nullable         |                                                       |
| `converted_volunteer_id` | foreignId nullable → volunteers.id |                                              |

### `volunteers` (approved + active roster)
| column            | type                            |
| ----------------- | ------------------------------- |
| `user_id`         | foreignId nullable → users.id   |
| `full_name`       | string                          |
| `email`           | string                          |
| `phone`           | string nullable                 |
| `country`         | string                          |
| `role`            | enum (event · community · admin · media) |
| `assigned_at`     | date                            |
| `notes`           | text nullable                   |

## Testimonials

### `testimonials`
| column         | type                       | notes                                              |
| -------------- | -------------------------- | -------------------------------------------------- |
| `author_name`  | string                     |                                                    |
| `author_role`  | string                     | e.g. "Volunteer", "Community Member"               |
| `photo_path`   | string nullable            |                                                    |
| `quote`        | text                       |                                                    |
| `video_url`    | string nullable            |                                                    |
| `featured`     | boolean default false      | show on home page                                  |
| `sort`         | unsignedSmallInteger       |                                                    |

## Media Gallery

### `media_items`
| column      | type                                          | notes                                |
| ----------- | --------------------------------------------- | ------------------------------------ |
| `type`      | enum (image · video)                          |                                      |
| `category`  | enum (community · education · events · volunteers) |                                 |
| `event_id`  | foreignId nullable → events.id                | optional grouping                    |
| `disk`      | string default `media`                        |                                      |
| `path`      | string                                        |                                      |
| `poster_path` | string nullable                             | for videos                           |
| `caption`   | string nullable                               |                                      |
| `sort`      | unsignedSmallInteger                          |                                      |

## Instagram

### `instagram_posts`
| column         | type                       | notes                                                  |
| -------------- | -------------------------- | ------------------------------------------------------ |
| `external_id`  | string unique              | IG post id (auto mode) or sha1(url) (manual)           |
| `permalink`    | string                     |                                                        |
| `caption`      | text nullable              |                                                        |
| `media_url`    | string nullable            | image / video URL from IG (auto mode)                 |
| `media_type`   | string nullable            |                                                        |
| `thumbnail_url`| string nullable            |                                                        |
| `posted_at`    | timestamp nullable         |                                                        |
| `is_approved`  | boolean default true       |                                                        |
| `is_hidden`    | boolean default false      |                                                        |
| `source`       | enum (manual · api)        |                                                        |

## Marketing & contact

### `newsletter_subscribers`
| column         | type                       |
| -------------- | -------------------------- |
| `name`         | string nullable            |
| `email`        | string unique              |
| `subscribed_at`| timestamp                  |
| `unsubscribed_at` | timestamp nullable      |
| `source`       | string nullable            | "home · footer · contact"  |

### `contact_messages`
| column         | type                                                                          |
| -------------- | ----------------------------------------------------------------------------- |
| `full_name`    | string                                                                        |
| `email`        | string                                                                        |
| `phone`        | string nullable                                                               |
| `subject`      | enum (general · volunteer · donation · partnership)                           |
| `message`      | text                                                                          |
| `consented_at` | timestamp                                                                     |
| `status`       | enum (new · in_progress · resolved · archived)                                |
| `handled_by`   | foreignId nullable → users.id                                                 |

## Misc

### `settings` (key/value)
| column | type    | notes                              |
| ------ | ------- | ---------------------------------- |
| `key`  | string unique | dot-namespaced key            |
| `value`| longText nullable | JSON-encoded payload     |

Holds hero media, social URLs, donation processor toggles, etc.

## Relationships overview

```
User ──┬─< Beneficiary (assigned_to)
       ├─< BeneficiaryDocument (uploaded_by)
       ├─< BeneficiaryApplication (reviewer)
       ├─< Donation (recorded_by)
       ├─< VolunteerApplication (reviewer)
       └─< ContactMessage (handled_by)

Beneficiary ──< BeneficiaryFolder ──< BeneficiaryDocument
            ──< BeneficiaryTimelineEntry
            ──< BeneficiaryDocument (direct, when no folder)

BeneficiaryApplication ──< BeneficiaryApplicationDocument
                       ──> Beneficiary (converted_beneficiary_id, after approval)

Event ──< EventImage
      ──< Donation
      ──< MediaItem

VolunteerApplication ──> Volunteer (converted_volunteer_id)
```

## Migrations order

1. `add_role_phone_avatar_to_users`
2. `create_beneficiaries`
3. `create_beneficiary_folders`
4. `create_beneficiary_documents`
5. `create_beneficiary_applications`
6. `create_beneficiary_application_documents`
7. `create_beneficiary_timeline_entries`
8. `create_events`
9. `create_event_images`
10. `create_donations`
11. `create_volunteer_applications`
12. `create_volunteers`
13. `create_testimonials`
14. `create_media_items`
15. `create_instagram_posts`
16. `create_newsletter_subscribers`
17. `create_contact_messages`
18. `create_settings`
