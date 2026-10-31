# FINAL PROJECT REPORT
## NSBM NewsDesk — Campus News Management System
**Institution:** NSBM Green University  
**Faculty:** Faculty of Computing  
**Department:** Department of Computer Science & Software Engineering  
**Module Code & Title:** SE102.3 — Web and Mobile Application Development  
**Academic Year & Batch:** 1st Year 2nd Semester | Batch 26.1 IFSL  
**Submission Date:** [SUBMISSION DATE - e.g., November 2026]  

---

## 1. Project Information & Group Details

### 1.1 Project Title
**NSBM NewsDesk — Dynamic Campus News & Announcement Management System**

### 1.2 Team Leader & Contact Details
* **Team Leader Name:** [MEMBER 1 NAME]
* **Student Registration ID:** [STUDENT ID 1]
* **University Email:** [STUDENT EMAIL 1]
* **Contact Number:** [PHONE NUMBER]

### 1.3 Group Members & Assigned Roles
| No. | Student Registration ID | Member Full Name | Role & Assigned Module |
| :---: | :--- | :--- | :--- |
| **1** | `[STUDENT ID 1]` | `[MEMBER 1 NAME]` | **Team Leader & System Architect** — Database Schema, Core Authentication & RBAC Security |
| **2** | `[STUDENT ID 2]` | `[MEMBER 2 NAME]` | **UI/UX & Frontend Lead** — Master Design System, Live Breaking Marquee Ticker & SVG Graphics |
| **3** | `[STUDENT ID 3]` | `[MEMBER 3 NAME]` | **Public Portal Developer** — Homepage Editorial Grid, Search/Filter Engine & Article Reader |
| **4** | `[STUDENT ID 4]` | `[MEMBER 4 NAME]` | **Student Portal Lead** — Student Dashboard, Submission Form & Secure Uploads Handling |
| **5** | `[STUDENT ID 5]` | `[MEMBER 5 NAME]` | **Engagement Subsystem Lead** — AJAX Bookmarking, Category Subscriptions & Reader Discussions |
| **6** | `[STUDENT ID 6]` | `[MEMBER 6 NAME]` | **Admin Operations Lead** — Article Management CRUD, Category Manager & Editorial Review Desk |
| **7** | `[STUDENT ID 7]` | `[MEMBER 7 NAME]` | **Analytics, Announcements & QA/Docs Lead** — Campus Announcements, Engagement Reports & Documentation |

### 1.4 Project Deliverable Links
* **GitHub Repository URL:** `https://github.com/[YOUR-GITHUB-USERNAME]/nsbm-newsdesk` *(Ensure repository is public or module lecturer is added as collaborator)*
* **Live Hosted Application (Heroku):** `https://nsbm-newsdesk.herokuapp.com` *(or your custom Heroku app URL)*
* **Video Demonstration URL (YouTube Unlisted):** `https://youtu.be/[YOUR-VIDEO-ID]`

---

## 2. Introduction

### 2.1 Background
NSBM Green University is a rapidly expanding higher education institute with thousands of undergraduates across multiple faculties (Computing, Business, Engineering, and Science). Effective communication of academic circulars, club events, sports fixtures, and research accomplishments is paramount to vibrant campus life. Historically, announcements have been fragmented across disparate social media groups and static notice boards.

### 2.2 System Concept
**NSBM NewsDesk** is a centralized, role-based web application tailored to the university's ecosystem. It empowers university administrators to curate verified campus news while enabling undergraduates to actively contribute stories, personalize their news feeds, bookmark articles for later reading, and participate in constructive academic discourse.

---

## 3. Project Objectives

1. **Centralized News & Circular Distribution:** Provide a single trusted digital gateway for university administration, faculties, and clubs to broadcast news.
2. **Role-Based Access Control (RBAC):** Deliver distinct, secure environments for Public Visitors, Authenticated Students, and System Administrators.
3. **Student Content Co-Creation:** Implement an editorial submission pipeline where undergraduates submit articles for review before public publishing.
4. **Personalized News Experience:** Allow students to subscribe to preferred categories (Academic, Events, Sports, General) and save articles into a personal bookmark repository.
5. **Modern, Accessible UI/UX:** Offer an editorial broadcast aesthetic with dual-theme compatibility (Dark Mode default and Light Mode toggle), live marquee feeds, and full mobile responsiveness.
6. **Robust Data Security & CRUD Integrity:** Implement industry-standard password encryption (bcrypt), PDO prepared statements against SQL injection, and sanitized output against Cross-Site Scripting (XSS).

---

## 4. System Architecture & Technology Stack

```
+-------------------------------------------------------------------------+
|                              PRESENTATION TIER                           |
|  HTML5  |  CSS3 (Custom Design System v2.0 - 1900+ lines)  |  JavaScript|
|  • Responsive Layouts  • Dark/Light Theme Engine  • Broadcast Marquee  |
+-------------------------------------------------------------------------+
                                    │
                                    ▼ (HTTP / HTTPS Requests)
+-------------------------------------------------------------------------+
|                             APPLICATION TIER                            |
|                       Native PHP 8.x Architecture                       |
|  • Session Auth & RBAC          • Article & Category CRUD Controllers   |
|  • Submission Moderation Queue  • Image Upload Sanitizer & MIME Verifier |
|  • Analytics & Report Generator • Dynamic SVG Fallback Resolver         |
+-------------------------------------------------------------------------+
                                    │
                                    ▼ (PDO Prepared Statements)
+-------------------------------------------------------------------------+
|                                DATA TIER                                |
|                        MySQL Relational Database                        |
|  • 7 Normalized Tables (InnoDB) • Foreign Key Constraints (CASCADE)     |
|  • Hosted on JawsDB MySQL (Heroku) / Local MySQL (XAMPP)                |
+-------------------------------------------------------------------------+
```

### 4.1 Technology Stack Details
* **Frontend:** HTML5, Vanilla CSS3 (Custom Design System with CSS variables and responsive media queries), Modern ES6 JavaScript (Fetch API, DOM manipulation).
* **Backend:** PHP 8.x using native object-oriented / procedural security best practices with PHP Data Objects (PDO).
* **Database:** MySQL 8.x with InnoDB engine, utf8mb4 character set, and strict relational integrity.
* **Hosting & Web Server:** Heroku Cloud Platform (`heroku-php-apache2` dyno) with JawsDB MySQL database add-on.

---

## 5. Database Design & Entity Relationship

The database schema (`database/schema.sql`) consists of 7 normalized relational tables:

```
+---------------+       1:N       +-------------------+       1:N       +---------------+
|     users     | ──────────────< |     articles      | >────────────── |  categories   |
+---------------+                 +-------------------+                 +---------------+
  │       │                                 │
  │       │ 1:N                             │ 1:N
  │       ▼                                 ▼
  │  +------------------+             +-------------------+
  │  |  announcements   |             |     comments      |
  │  +------------------+             +-------------------+
  │                                         │
  │ 1:N                                     │
  ├───────────────────────────────────┐     │
  ▼                                   ▼     ▼
+---------------------+       +---------------------------+
|      bookmarks      |       |   category_subscriptions  |
+---------------------+       +---------------------------+
```

### 5.1 Tables Summary
1. `users` — Stores administrators and students with bcrypt password hashes, student IDs, and departments.
2. `categories` — Classifies content into Academic, Events, Sports, and General with custom badge colors.
3. `articles` — Primary content entity storing title, slug, excerpt, body, cover image, status, views, and breaking/featured flags.
4. `announcements` — Administrative notices with priority tiers (Urgent, High, Normal) and expiry scheduling.
5. `comments` — Reader discussions linked to articles and users with moderation status.
6. `bookmarks` — Many-to-many relationship linking students to their saved stories.
7. `category_subscriptions` — Many-to-many relationship tracking personalized category feeds per student.

---

## 6. Key Features & Interface Walkthrough

### 6.1 Public & Student Interface
1. **Broadcast Breaking Ticker:**
   - Features a live pulsing status indicator (`LIVE FEED`), high-priority `BREAKING` badge, and continuous marquee scrolling.
   - Automatically pauses on mouse hover for easy reading and clicking.
2. **Homepage Hero Editorial Grid (`index.php`):**
   - Showcases the primary breaking story in an expansive lead card with secondary spotlight stories and active announcements rail.
3. **Live Search & Filter Engine (`news.php`):**
   - Real-time keyword search with category filtering and sorting by "Latest" or "Most Viewed".
4. **Full Article Reader & Social Sharing (`article.php`):**
   - Rich typography, live view tracking, WhatsApp direct share, link copying, and reader comment stream.
5. **Student NewsDesk Dashboard (`student/dashboard.php`):**
   - KPI metrics cards, quick submission links, recent bookmarks repository, and profile info.
6. **Student Article Submission Desk (`student/submit_article.php`):**
   - Structured submission form with client-side image preview and server-side image upload validation.
7. **Personalized Subscriptions Feed (`student/subscriptions.php`):**
   - Students tailor their feed by toggling categories of interest.

### 6.2 Administrator Management Console
1. **Overview Analytics Hub (`admin/index.php`):**
   - Key university metrics (total readership, pending reviews, active announcements, total comments).
2. **Article Management CRUD (`admin/articles.php`, `admin/article_create.php`):**
   - Full authoring, editing, deletion, slug generation, and featured/breaking toggling.
3. **Student Submissions Review Desk (`admin/submissions.php`):**
   - Editorial inspection panel with one-click Approve / Reject with mandatory feedback notes for students.
4. **Category Manager (`admin/categories.php`):**
   - Add/edit news categories with color pickers and slug management.
5. **Campus Announcements CRUD (`admin/announcements.php`):**
   - Priority categorization (Urgent, High, Normal) with expiry date controls.
6. **Executive Engagement Reports (`admin/reports.php`):**
   - Category engagement shares, top 5 read stories leaderboard, and `@media print` optimized printable report generator.

---

## 7. Cloud Deployment on Heroku

The application is engineered for zero-friction cloud deployment on Heroku:
1. **Dynamic Database Detection (`config/db.php`):** Automatically extracts host, port, username, password, and database name from Heroku's `JAWSDB_URL` environment variable.
2. **Apache Server Configuration (`Procfile`):** Instructs Heroku dyno to run `heroku-php-apache2`.
3. **Buildpack Metadata (`composer.json`):** Declares PHP 8.x requirement with PDO MySQL extensions.
4. **Assets & SVG Caching (`assets/images/.htaccess`):** Enforces SVG MIME types for crisp vector graphics across all web browsers.

*(Refer to `HEROKU_DEPLOYMENT_GUIDE.md` for exact terminal commands to provision Heroku and JawsDB).*

---

## 8. Security Implementations

* **Password Security:** All authentication utilizes PHP `password_hash()` with `PASSWORD_DEFAULT` (bcrypt 60-character salt) preventing rainbow table and brute-force compromise.
* **SQL Injection Prevention:** 100% of database interactions leverage PDO prepared statements with positional or named placeholders.
* **Cross-Site Scripting (XSS) Defense:** All user-supplied inputs displayed in the DOM are sanitized through `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
* **Access Control & Session Hardening:** Strict session checks guard all `/student/*` and `/admin/*` directories with role verification before page rendering.
* **File Upload Hardening:** Uploaded cover photos are validated against permitted MIME types (JPEG, PNG, WEBP), renamed with cryptographically unique UUIDs, and kept within a designated public upload directory.

---

## 9. Conclusion & Self-Evaluation

The NSBM NewsDesk system successfully fulfills all functional and non-functional requirements specified for Topic 1 of the SE102.3 Web and Mobile Application Development module. The division of responsibilities across the 7 group members demonstrated effective collaborative software engineering, resulting in a production-ready, accessible, and high-performance campus web portal.

### Evaluation Criteria Verification Checklist
- [x] **UI Design & Frontend (20%):** Custom tokenized design system, dark/light theme, live ticker, fully responsive.
- [x] **Backend & Database Integration (20%):** Robust PHP PDO architecture, 7 normalized MySQL tables, complete CRUD.
- [x] **Admin Panel Functionality (15%):** Full article CRUD, category manager, student submission review, announcements, and reports.
- [x] **Student/User Panel Functionality (15%):** Registration, dashboard, article submissions, bookmarks, and subscriptions.
- [x] **Project Report & Hosting (15%):** Formatted report document, GitHub repository, and live Heroku deployment.
- [x] **Video Demonstration & Teamwork (15%):** Screen-recorded walkthrough highlighting individual member contributions.

