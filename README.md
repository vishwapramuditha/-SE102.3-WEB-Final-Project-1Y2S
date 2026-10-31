# NSBM NewsDesk – Campus News Management System

**Faculty of Computing | NSBM Green University**  
**Module:** Web and Mobile Application Development (SE102.3)  
**Batch:** 26.1 IFSL | **Year:** 1st Year, 2nd Semester Final Project  

---

## 1. Project Overview

**NSBM NewsDesk** is a web-based Campus News Management System tailored specifically for NSBM Green University. The system empowers university administration, faculties, and student societies to publish, organize, and manage campus news, circulars, and announcements, while providing undergraduates with an interactive, verified, and personalized news platform.

---

## 2. Implemented Features (According to Assignment Guidelines)

### 2.1. Administrator Features
- **Secure Authentication:** Role-based access control with hashed passwords and protected sessions.
- **News Article Management (CRUD):**
  - Create new articles with title, category, excerpt, full body, and cover photo.
  - Edit existing articles and update images.
  - Delete articles with cascading removal of associated comments.
  - Toggle **Featured Hero Story** and **Breaking News Ticker** with 1 click.
- **News Category Management (CRUD):**
  - Manage beats: *Academic*, *Events*, *Sports*, and *General*.
  - Add custom categories, edit slugs, descriptions, and assign badge colors.
  - Delete categories with safeguards against orphaned articles.
- **Student & Club Submissions Approval:**
  - Inspect student-submitted articles (author, student ID, department, cover image, and body).
  - One-click **Approve & Publish Live**.
  - **Reject with Feedback:** Provide custom revision notes so students know how to improve their submission.
- **Campus Announcements Management:**
  - Publish official circulars with priority tags (**Urgent**, **High**, **Normal**) and expiry dates.
  - Toggle active/disabled status and delete notices.
- **Reader Comments Moderation:**
  - View all student discussions across articles.
  - Approve, hide, or permanently remove inappropriate comments.
- **Readership & Engagement Reports:**
  - High-level KPIs: Total articles, cumulative views, total comments, and active students.
  - Category-by-category readership percentage and visual progress bars.
  - Top 5 most-read campus stories ranking.
  - Top active student contributors.
  - **Print / Export PDF** formatted report.

### 2.2. Student / User Features
- **Registration & Login:**
  - Student sign-up with Full Name, Student ID, Email, Faculty/Department, and Password.
  - Secure login with session preservation and auto-redirection.
- **Browse News & Announcements:**
  - Real-time breaking news banner ticker.
  - Featured hero story with reading time and author byline.
  - Filterable recent news card grid.
  - Announcements widget with priority highlights.
- **Search & Filtering:**
  - Search articles by keyword in title or content.
  - Filter by category (*Academic*, *Events*, *Sports*, *General*).
  - Filter by publication date range (`from_date` to `to_date`).
  - Sort by *Latest* or *Most Viewed*.
- **Submit News & Announcements:**
  - Form for undergraduates and clubs to draft and submit stories for editorial approval.
  - Cover photo file upload with real-time browser preview.
- **Personal Submissions Portal (`my_articles.php`):**
  - Track submission status: `Pending`, `Approved`, or `Rejected`.
  - View editorial feedback from administrators when revisions are requested.
- **Comment on News Articles:**
  - Post comments under approved news stories.
  - View discussion threads with student avatars and timestamps.
- **Bookmark & Saved Reading List (`bookmarks.php`):**
  - Save articles for later reading with 1 click (AJAX or direct).
  - Manage and remove saved articles in the student reading list.
- **Category Subscriptions (`subscriptions.php`):**
  - Choose preferred news topics to subscribe to.
  - Personalized campus feed tailored to active subscriptions.

---

## 3. Technology Stack

- **Frontend:** HTML5, CSS3 (NSBM Green University design system), Vanilla JavaScript (Form validations, modals, live image preview, AJAX bookmarking).
- **Backend:** PHP 8+ (Procedural + Functional, PDO, prepared statements, session security, password hashing).
- **Database:** MySQL / MariaDB (Relational schema with foreign keys and cascading deletes).

---

## 4. Database Setup & Installation

### Option A: 1-Click Automated Installer (Recommended)
1. Ensure Apache and MySQL are running in your **XAMPP Control Panel**.
2. Place the project folder into your XAMPP web root:
   ```
   C:\xampp\htdocs\-SE102.3-WEB-Final-Project-1Y2S\
   ```
3. Open your web browser and navigate to:
   ```
   http://localhost/-SE102.3-WEB-Final-Project-1Y2S/setup.php
   ```
4. The installer will automatically connect to MySQL, create the `nsbm_newsdesk` database, import all tables, and seed verified sample data.

### Option B: Manual phpMyAdmin Import
1. Open **phpMyAdmin** (`http://localhost/phpmyadmin`).
2. Create a database named `nsbm_newsdesk`.
3. Click the **Import** tab.
4. Select `database/schema.sql` from the project repository and click **Import**.

---

## 5. Evaluation Credentials

For academic grading and demonstration, the following default accounts are pre-configured:

| Role | Email | Password | Details |
| :--- | :--- | :--- | :--- |
| **Administrator** | `admin@nsbm.ac.lk` | `admin123` | Full access to Admin Control Centre |
| **Student** | `student@nsbm.ac.lk` | `student123` | Student portal, submissions, bookmarks |

*(Note: The login page includes quick autofill buttons for 1-click testing during demonstrations).*

---

## 6. Directory Structure

```
├── admin/                     # Administrator Control Centre
│   ├── announcements.php      # Manage campus notices (CRUD)
│   ├── article_create.php     # Author new campus story
│   ├── article_edit.php       # Edit existing article
│   ├── articles.php           # Manage news articles (CRUD)
│   ├── categories.php         # Manage beats & topics (CRUD)
│   ├── comments.php           # Moderate reader discussions
│   ├── index.php              # Admin dashboard & overview
│   ├── reports.php            # Readership & engagement analytics
│   └── submissions.php        # Approve / Reject student stories
├── api/
│   ├── index.php              # API status index
│   └── toggle_bookmark.php    # AJAX endpoint for article bookmarks
├── assets/
│   ├── css/
│   │   └── style.css          # Clean NSBM Emerald & Gold styling
│   ├── images/                # Brand SVGs and sample article covers
│   ├── js/
│   │   ├── app.js             # JS entry
│   │   └── main.js            # Validation, AJAX, and modals
│   └── uploads/               # Destination for student/admin cover photos
├── config/
│   ├── db.php                 # PDO database connection configuration
│   └── functions.php          # Auth, sanitization, image upload, dates
├── database/
│   └── schema.sql             # Complete MySQL schema & initial seed data
├── includes/
│   ├── admin_header.php       # Admin top header bar
│   ├── admin_sidebar.php      # Admin navigation sidebar
│   ├── footer.php             # Public & student footer
│   └── header.php             # Public & student navigation bar
├── student/                   # Student Portal
│   ├── bookmarks.php          # Saved reading list
│   ├── dashboard.php          # Student hub & overview
│   ├── my_articles.php        # Submission tracking & feedback
│   ├── submit_article.php     # Submit story / club event
│   └── subscriptions.php      # Manage category preferences
├── announcements.php          # Public campus circulars board
├── article.php                # Single article detail & comments
├── dashboard.html             # Quick portal redirect
├── index.html                 # Fallback browser redirect
├── index.php                  # Main campus homepage
├── login.php                  # Unified login portal
├── logout.php                 # Session destroy handler
├── news.php                   # All news search & filter archive
├── register.php               # Student account registration
├── setup.php                  # Automated 1-click database installer
└── README.md                  # Project documentation
```

---

## 7. Submission Checklist Verification
- [x] HTML, CSS, JavaScript, PHP, MySQL stack used.
- [x] Separate Admin and Student interfaces implemented.
- [x] Secure login with `password_hash()` and session roles.
- [x] Full CRUD operations on News Articles, Categories, Announcements, and Submissions.
- [x] Search and filtering by category and date.
- [x] Client-side and server-side form validations.
- [x] Automated database setup script (`setup.php`) and SQL dump (`database/schema.sql`).
