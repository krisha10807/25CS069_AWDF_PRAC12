# Practical 12: Event Management, Poster File Upload & Role-Based Access Control

**Course:** Web Development Frameworks (WDF)  
**Project Base:** StudentHub Portal  
**Student Name:** Krisha Patel  
**Student ID:** 25CS069  
**Institution:** CHARUSAT - CSPIT (Computer Science & Engineering)  
**Due Date:** 19 October 2026, 23:59 PM  
**Submission Package:** `Practical_12_25CS069.zip`

---

## 1. Practical Overview & Objectives
This practical implements an institutional Event Management System with secure poster file uploads, server-side MIME type and size validations, database persistence with prepared statements, and strict Role-Based Access Control (RBAC).

### Key Technical Capabilities:
1. **Administrative Authentication (`authenticate.php`)**: Validating credentials against BCRYPT hashes and enforcing role verification.
2. **Event CRUD Operations (`admin_events.php`, `event_actions.php`)**: Full lifecycle management of events (Create, Read, Update, Delete).
3. **Secure File Upload Handling**: Moving uploaded poster images to a dedicated repository (`uploads/posters/`) with randomized unique filenames.
4. **MIME Type & Extension Whitelisting**: Strict filtering allowing only genuine image formats (`image/jpeg`, `image/png`, `image/webp`).
5. **File Size Limit Validation**: Strict 2 MB boundary enforcement (`MAX_POSTER_SIZE = 2 * 1024 * 1024`).
6. **Role-Based Access Control (RBAC)**: Enforcing administrative isolation with immediate HTTP `403 Forbidden` response for unauthorized student attempts.

---

## 2. Requirements Compliance Checklist

| # | Practical Instruction Requirement | Implementation & Verification Evidence | Status |
| :-: | :--- | :--- | :-: |
| 1 | **Admin login and dashboard** | Admin logs in via `login.php` with `admin@charusat.ac.in` and accesses metrics and control center on `admin_events.php`. | Verified |
| 2 | **Successful event creation** | Submitting event details and a valid image poster executes an SQL prepared `INSERT` and saves the poster file. | Verified |
| 3 | **Event list with uploaded posters** | Responsive card grid displaying all campus events alongside high-resolution poster artwork thumbnails. | Verified |
| 4 | **Successful event update** | Edit modal pre-populates existing data; updating text and/or poster image updates database record. | Verified |
| 5 | **Successful event deletion** | Delete modal with confirmation cleans up database record and unlinks uploaded poster files. | Verified |
| 6 | **Valid poster upload** | File selector accepts JPG, PNG, and WEBP under 2 MB, renders real-time client-side preview, and uploads securely. | Verified |
| 7 | **Invalid file type validation** | Intercepts non-image extensions (.pdf, .txt, .exe) and MIME types; returns error notice. | Verified |
| 8 | **File size limit validation** | Intercepts files exceeding 2 MB boundary; returns clear size limit error banner. | Verified |
| 9 | **Success and error messages** | Prominent green/red Bootstrap/custom alert banners dynamically inform user of all operational outcomes. | Verified |
| 10 | **Unauthorized user access restriction** | Student users attempting to visit `admin_events.php` are intercepted by the RBAC guard with a dedicated **403 Forbidden** security screen. | Verified |

---

## 3. Test Credentials

| Role | Institutional Email | Plain Password | Hashed Password Format | Dashboard Access |
|---|---|---|---|---|
| **Administrator** | `admin@charusat.ac.in` | `Admin@123` | BCRYPT (`$2y$10$...`) | Full Event Management (`admin_events.php`) |
| **Student** | `student@charusat.ac.in` | `Student@123` | BCRYPT (`$2y$10$...`) | Read-Only Showcase (`student_events.php`) |

---

## 4. Test Report & Screenshot Evidence Inventory

All 10 required screenshots have been captured and saved to the `screenshots/` directory:

### 1. Admin Login and Dashboard
- **File:** `screenshots/01_admin_login_and_dashboard.png`
- **Verification:** Administrator logs in and lands on the Event Management Dashboard showing live event count metrics, technical hackathons, workshops, and the event repository.

### 2. Successful Event Creation
- **File:** `screenshots/02_successful_event_creation.png`
- **Verification:** After submitting the creation form with a valid poster, the page displays a green alert: *"Event Created Successfully! Valid poster image has been uploaded and stored in the repository."*

### 3. Event List with Uploaded Posters
- **File:** `screenshots/03_event_list_with_uploaded_posters.png`
- **Verification:** Displays the responsive grid of events with their respective posters (Hackathon, AI Workshop, Cultural Fest), category badges, date, venue, and action buttons.

### 4. Successful Event Update
- **File:** `screenshots/04_successful_event_update.png`
- **Verification:** Editing an event (modifying venue/title) saves cleanly and displays: *"Event Updated Successfully! The database record and poster information have been updated."*

### 5. Successful Event Deletion
- **File:** `screenshots/05_successful_event_deletion.png`
- **Verification:** Confirming deletion from the modal removes the card and displays: *"Event Deleted Successfully! The event record and associated files have been removed."*

### 6. Valid Poster Upload
- **File:** `screenshots/06_valid_poster_upload.png`
- **Verification:** Demonstrates selecting a valid image poster (`valid_poster.png`), displaying live real-time preview and file size badge inside the upload card before submission.

### 7. Invalid File Type Validation
- **File:** `screenshots/07_invalid_file_type_validation.png`
- **Verification:** Submitting a PDF file format (`invalid_file_type.pdf`) is intercepted by the server-side validator, displaying: *"Invalid File Type Validation Failed! Only JPG, JPEG, PNG, and WEBP poster images are permitted."*

### 8. File Size Limit Validation
- **File:** `screenshots/08_file_size_limit_validation.png`
- **Verification:** Submitting an oversized image (5.7 MB) is intercepted by the 2 MB limit validator, displaying: *"File Size Limit Validation Failed! Uploaded poster exceeds the 2 MB maximum allowable limit."*

### 9. Success and Error Messages
- **File:** `screenshots/09_success_and_error_messages.png`
- **Verification:** Demonstrates prominent visual contrast of feedback banners for both error alerts and success confirmation messages.

### 10. Unauthorized User Access Restriction
- **File:** `screenshots/10_unauthorized_user_access_restriction.png`
- **Verification:** Logged in as Student (`student@charusat.ac.in`), navigating to `admin_events.php` triggers an immediate **403 Forbidden - Unauthorized Access Denied** security block.

---

## 5. Faculty Review / Viva Questions & Answers

### Q1: Why is validating both file extension and MIME type critical for file uploads?
**Answer:** File extensions (e.g., `.png`) can easily be spoofed by renaming malicious files (e.g. renaming `malware.php` to `malware.png`). Checking the MIME type using `finfo_file()` reads the magic bytes of the file content, ensuring that the file is genuinely an image and preventing arbitrary server-side code execution.

### Q2: Why are uploaded filenames sanitized and renamed with random tokens?
**Answer:** Original filenames can contain path traversal characters (e.g., `../../shell.php`), spaces, or special characters. Generating a sanitized name such as `poster_YYYYMMDD_HHMMSS_<random>.ext` prevents directory traversal attacks and avoids file collision when two users upload files with the same name.

### Q3: How is the 2 MB file size limit enforced?
**Answer:** Both client-side and server-side checks are employed. On the server side, PHP checks `$_FILES['poster']['size'] > 2 * 1024 * 1024`. If exceeded, the file is rejected immediately before saving.

---
*Created for StudentHub Practical 12 Submission & Faculty Review 2026*  
*Student: Krisha Patel (25CS069)*
