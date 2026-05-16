# DNSC REC — Research Ethics Committee Review and Approval System
### Davao Del Norte State College

---

## 📌 Overview

**DNSC REC** is a web-based Research Ethics Committee Review and Approval System developed for **Davao Del Norte State College (DNSC)**. It streamlines the submission, review, and approval process of research protocols through a role-based access control system.

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| Backend | PHP 8+ |
| Database | MySQL (via PDO) |
| Frontend | HTML5, CSS3, Bootstrap 5 |
| Icons | Font Awesome 6.4 |
| Alerts | SweetAlert2 |
| Fonts | Plus Jakarta Sans, Montserrat |
| Server | Apache (XAMPP) |
| Mailer | PHPMailer (Brevo SMTP) |
| URL Routing | `.htaccess` Clean URLs |

---

## 🔑 API & Integrations

The system uses the following external services for notifications and core functionality:

### ✉️ Brevo (formerly Sendinblue) — Email API
Used for sending protocol updates, password resets, and author notifications.
- **Service**: Brevo SMTP Relay
- **Key Location**: `includes/send_email.php`
- **SMTP Login/User**: `a34cd5001@smtp-brevo.com`
- **Current SMTP Key**: `[YOUR_BREVO_SMTP_KEY]`
- **Rotation**: If the key expires, update the `Password` variable in `includes/send_email.php`.

### 🖋️ Signature Pad API
Used for digital signing of protocols and registration forms.
- **Provider**: Signature Pad v4.0.0 (CDN-Integrated)
- **Key**: None (Open Souce)

---

## 📞 Institutional Contact Info

For official inquiries and support regarding the DNSC REC system:

- **Address**: DNSC, Brgy. New Visayas, Panabo City, Davao del Norte, 8105
- **Phone**: 0995 573 8237
- **Email**: rec@dnsc.edu.ph
- **Facebook**: [DNSC- Research Ethics Committee](https://www.facebook.com/profile.php?id=61566750576733)

---

## ⚙️ Installation (Local / XAMPP)

1. **Clone or copy** the project to `C:/xampp/htdocs/recras2.0`
2. **Import the database:**
   - Open `phpMyAdmin` → Create a database named `rec_system`
   - Import `database/rec_system.sql`
3. **Configure database** in `config/database.php`:
   ```php
   $host = 'localhost';
   $dbname = 'rec_system';
   $user = 'root';
   $pass = '';
   ```
4. **Enable Apache `mod_rewrite`** for clean URLs (already configured via `.htaccess`)
5. **Access the system** at: `http://localhost/recras2.0`

---

## 🌐 Clean URLs

The system uses `.htaccess` rewriting — no `.php` extensions needed in URLs.

| Page | URL |
|---|---|
| Login | `http://localhost/recras2.0/login` |
| Admin Dashboard | `http://localhost/recras2.0/admin/` |
| Staff Dashboard | `http://localhost/recras2.0/rec_staff/` |
| Chair Dashboard | `http://localhost/recras2.0/rec_chair/` |
| Member Dashboard | `http://localhost/recras2.0/rec_member/` |
| Author Dashboard | `http://localhost/recras2.0/author/` |

---

## 👤 Default Accounts

> **Default Password for all accounts:** `password123`

| # | Name | Email | Role | Note |
|---|---|---|---|---|
| 1 | System Admin | admin@dnsc.edu.ph | `admin` | Full control + Identity Mgmt |
| 2 | Dr. Robert Miller | robert.miller@dnsc.edu.ph | `chair` | **Multi-Role**: CEO of DNSC REC |
| 3 | Maria Santos | m.santos@dnsc.edu.ph | `staff` | Protocol Intake & Registry |
| 4 | Juan Dela Cruz | j.delacruz@dnsc.edu.ph | `member` | Peer Reviewer |
| 17 | Ana Reyes | a.reyes@dnsc.edu.ph | `member` | Peer Reviewer |
| 18 | Carlos Bautista | c.bautista@dnsc.edu.ph | `member` | Peer Reviewer |
| 19 | Mark Van Buladaco | markvan.buladaco@dnsc.edu.ph | `chair` | **Multi-Role**: Chairperson |

---

## 🔐 Role Permissions

| Feature | Admin | Chair | Staff | Member |
|---|:---:|:---:|:---:|:---:|
| Manage Users | ✅ | ❌ | ❌ | ❌ |
| View Audit Logs | ✅ | ❌ | ❌ | ❌ |
| Set New Passwords | ✅ | ❌ | ❌ | ❌ |
| View All Protocols | ✅ | ✅ | ✅ | ❌ |
| Submit Protocols | ❌ | ✅ | ✅ | ❌ |
| Assign Reviewers | ❌ | ✅ | ❌ | ❌ |
| Self-Assignment | ❌ | ✅ | ❌ | ❌ |
| Make Decisions | ❌ | ✅ | ❌ | ❌ |
| Review Protocols | ❌ | ✅ | ❌ | ✅ |
| View Analytics | ✅ | ❌ | ✅ | ❌ |

---

## 🔑 New Features & Workflows

### 🛡️ Secure Self-Service Registration
- **Mirror Layout**: Sign-in is on the Left, Registration is on the Right.
- **Digital Signatures**: Users can draw or upload PNG signatures during signup.
- **Admin Verification**: Registered accounts stay "Awaiting Approval" until prioritized by the Admin.

### 🆘 Password Recovery
- **Request Flow**: Found at `forgot_password.php`. Users provide email + full name to request a reset.
- **Admin Notification**: Admins see real-time alerts on their dashboard for pending resets.
- **Manual Reset**: Admins can force a new password via the **Security Override** panel in User Management.

### 👑 Chairperson Sovereignty
- **Role Hubs**: The Chair has access to "Secretariat Tools" (Staff) and "Reviewer Tools" (Member) to ensure workflow continuity even during personnel shortages.
- **Self-Review**: The Chair can assign themselves as one of the 3 required reviewers for any protocol.

---

## 📁 Project Structure

```
recras2.0/
├── admin/               # Admin pages (users, protocols, audit, config)
├── rec_chair/           # Chairperson pages (assign, decisions, protocols)
├── rec_staff/           # Staff pages (manage protocols, document screening)
├── rec_member/          # Member/reviewer pages (evaluation worksheets)
├── author/              # Researcher/Author pages (submission & tracking)
├── assets/
│   ├── css/style.css    # Global stylesheet
│   ├── js/script.js     # Global scripts
│   └── images/          # Logo, background, assets
├── config/database.php  # DB connection & BASE_URL constant
├── includes/
│   ├── auth_check.php   # Role-based authentication guard
│   ├── header.php       # Global HTML head & nav assets
│   ├── footer.php       # Global footer & script loader
│   └── sidebar.php      # Role-aware navigation sidebar
├── forms/               # REC form generators (PDF output)
├── uploads/             # Uploaded protocol documents
├── database/            # Database schema directory
│   └── rec_system.sql   # Main MySQL dump
├── .htaccess            # Clean URL rewriting rules
├── login.php            # Login page
├── logout.php           # Session destroy & redirect
└── index.php            # Root entry point (auto-redirects by role)
```

---

---

## 📋 REC Submission Process (12 Steps)

The system is designed to strictly follow the **Davao Del Norte State College** official research submission workflow:

1.  **Inquiry Stage**: Researcher sends an inquiry; Staff provides the submission link and instructions.
2.  **Submission of Requirements**: Researcher downloads, completes, and submits all required documents through the portal.
3.  **Initial Checking**: **REC Staff** verifies document completeness. If incomplete, notes are added for researcher resubmission.
4.  **Initial Assessment**: **REC Chair** determines the Review Type: **Exempt**, **Expedited**, or **Full Review**.
5.  **Assignment of Reviewers**: RA Chair assigns expert reviewers based on the study field (e.g., Education reviewers for education research).
6.  **Evaluation by Reviewers**: Assigned members evaluate using **REC Form 10** (Research) and **Form 12** (Informed Consent).
7.  **Evaluation Deadline**: Staff sets a deadline (typically 2 weeks) for reviewers to submit their completed forms.
8.  **Sending Evaluation Results**: Staff sends Form 10, Form 12, and **Form 15** (Resubmission) to the researcher.
9.  **Resubmission by Researcher**: Researcher uploads the revised manuscript and Form 15 addressing all comments.
10. **Checking of Resubmitted Documents**: Staff verifies that all previous reviewer revisions have been adequately addressed.
11. **Final Approval**: RA Chair performs the final check and issues the **Ethical Clearance Certificate**.
12. **Issuance of Final Documents**: The system releases the final bundle: Clearance Certificate, Progress/Final Report Forms, and Approval Letter.

---

## 📄 Official REC Forms (Standardized)

The system includes 10 unique, standardized forms meticulously mapped to the workflow:

| Form No. | Name | Primary Use |
| :--- | :--- | :--- |
| **FORM 9** | Expedited Evaluation Form | Specialized evaluation for expedited reviews |
| **FORM 10** | Research Evaluation Worksheet | Main scientific/ethical assessment form |
| **FORM 12** | Informed Consent Checklist | Specific checklist for participant consent forms |
| **FORM 13** | Checklist of Requirements | Intake verification form for staff |
| **FORM 14a** | Certificate of Exemption | Institutional letter for non-human research |
| **FORM 15** | Resubmission Form | Tracker for researcher responses to reviewers |
| **FORM 16** | Approval Letter | Formal notification of institutional approval |
| **FORM 18a** | Progress Report Form | Mid-study reporting requirement |
| **FORM 19** | Final Report Form | End-of-study closure requirement |
| **FORM 25** | Ethical Clearance | Premium institutional certificate for clearance |

---

## 📝 Maintenance & Setup Notes

- **Database Clean Start**: If you need to wipe all submissions for fresh testing, use the `TRUNCATE` command on the protocols and assignments tables.
- **File Permissions**: The `uploads/protocols/` directory must be writable by XAMPP (Apache).
- **Mod_Rewrite**: Ensure `httpd.conf` has `LoadModule rewrite_module modules/mod_rewrite.so` enabled for clean URLs.

---

*DNSC REC © 2026 — Davao Del Norte State College. All rights reserved.*
