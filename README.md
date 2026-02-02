# Visitor Management System

A PHP-based Visitor Management System (VMS) designed to track visitor check-ins, check-outs, and maintain a digital log of visits. This system includes authentication, reporting, and visitor profile management with photo capture capabilities.

## 🔑 Login Credentials

The system comes with a default administrator account created automatically on the first run.

- **Username**: `admin`
- **Password**: `password123`

> **Note**: You can change these credentials after logging in by visiting the **Profile** page.

## 🚀 Setup Instructions

### Installation
1. **Clone/Download**:
   - Place the project folder `visitor_sys` into your web server's root directory (e.g., `C:\xampp\htdocs\` or `/Applications/XAMPP/xamppfiles/htdocs/`).

2. **Database Configuration**:
   - The system is configured to work out-of-the-box with standard XAMPP settings:
     - **Host**: `localhost`
     - **User**: `root`
     - **Password**: (empty)
   - If your database credentials differ, open `config/db.php` and update the following variables:
     ```php
     $host = 'localhost';
     $username = 'YOUR_DB_USER';
     $password = 'YOUR_DB_PASSWORD';
     ```

3. **First Run**:
   - Open your browser and navigate to:  
     `http://localhost/visitor_sys/public/`
   - **Auto-Setup**: The system will automatically create the database (`visitor_sys`) and necessary tables (`Assessment_Users`, `Assessment_Visitors`) upon the first successful connection. It will also seed the default admin user.

## ✨ Features Implemented

### 1. Authentication & Security
- Secure Admin Login.
- Session Management.
- CSRF Protection on all forms.
- Password Hashing (BCRYPT).

### 2. Visitor Management
- **Dashboard**: Quick overview of recent activity.
- **Add Visitor**: 
  - Capture visitor details (Name, Email, Phone, Purpose).
  - **Photo Capture**: Integrated webcam support to take visitor photos instantly.
  - **File Upload**: Option to upload an existing photo.
- **Edit Visitor**: Update details and visit status.
- **Check-In / Check-Out**: Toggle visitor status easily.

### 3. Reporting & Logs
- **Search & Filter**: Real-time filtering of logs by Name and Date Range (From/To).
- **Export to CSV**: Download visitor logs for external analysis.
- **Print View**: Printer-friendly version of the visitor report.
- **AJAX Loading**: Smooth, page-reload-free filtering.

### 4. Admin Profile
- Update Admin Username.
- Change Admin Password securely.

## ⚠️ Known Issues / Notes

- **Camera Access**: The webcam feature relies on browser permissions. It works on `localhost` and via HTTPS. If you access the site via a local IP address (e.g., `192.168.x.x`) without HTTPS, the camera might be blocked by the browser.
- **Uploads Folder**: The system attempts to create the `public/uploads/` directory with `0755` permissions automatically. If images are not saving, ensure this directory exists and has write permissions.
- **Database Connection**: If you see a "Connection failed" error, ensure your MySQL server is running and the credentials in `config/db.php` match your environment.
