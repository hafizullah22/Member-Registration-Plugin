# Member Only - WordPress Membership Plugin

A custom WordPress membership plugin that allows users to register as **Member Only** users, upload government identification, automatically log in after registration, and access a dedicated member dashboard with WooCommerce integration.

## Features

### Member Registration

- Custom registration form via shortcode
- First Name & Last Name
- Email Address
- Phone Number
- Company Name (Optional)
- Event Attended Date
- Government ID upload (JPG, PNG, PDF)
- Password validation
- Duplicate email checking
- WordPress nonce security

### Automatic User Management

- Creates a custom **Member Only** user role
- Automatically assigns the role during registration
- Stores additional user information using User Meta

### Auto Login

After successful registration:

- Automatically logs the user in
- Redirects to a WooCommerce product page

### Login System

Provides a custom login form shortcode for members.

### Custom Redirects

#### Login Redirect

- Administrator → WordPress Dashboard
- Member Only User → Member Profile

#### Logout Redirect

- Administrator → Home Page
- Member → Member Landing Page

### Member Dashboard

The plugin provides a complete member dashboard including:

- Profile Information
- Update Profile
- Password Reset
- Order History
- Order Details
- Logout

### WooCommerce Integration

If WooCommerce is installed, members can:

- View all orders
- View individual order details
- Check payment status
- See purchased products

### Security

- WordPress Nonce Verification
- Data Sanitization
- Secure File Upload
- Password Validation
- User Authentication
- Protected Profile Access

---

# Shortcodes

## Registration Form

```text
[member_only_form]
```

Displays the custom member registration form.

---

## Login Form

```text
[member_only_login_form]
```

Displays the member login form.

---

## Member Dashboard

```text
[member_profile]
```

Displays the complete member dashboard.

---

## Logout Button

```text
[logout_button]
```

Displays a logout button for logged-in users.

---

# User Registration Flow

```
Visitor
      │
      ▼
Registration Form
      │
      ▼
Validation
      │
      ▼
Upload Government ID
      │
      ▼
Create Member User
      │
      ▼
Save User Meta
      │
      ▼
Auto Login
      │
      ▼
Redirect to Membership Product
```

---

# Dashboard Sections

- Profile
- Update Profile
- Password Reset
- Your Orders
- Logout

---

# Stored User Meta

The plugin stores the following information:

| Meta Key | Description |
|----------|-------------|
| billing_first_name | First Name |
| billing_last_name | Last Name |
| billing_email | Email |
| billing_phone | Phone Number |
| billing_company | Company |
| attend_date | Event Date |
| gov_id | Uploaded Government ID URL |

---

# Requirements

- WordPress 6.x+
- PHP 7.4+
- WooCommerce (Optional, required for Orders section)

---

# Installation

1. Upload the plugin folder to:

```
wp-content/plugins/
```

2. Activate the plugin from:

```
WordPress Dashboard
→ Plugins
```

3. Create pages and insert the required shortcodes.

Example:

### Register Page

```
[member_only_form]
```

### Login Page

```
[member_only_login_form]
```

### Member Dashboard

```
[member_profile]
```

---

# Plugin Structure

```
member-only/
│
├── member-only.php
├── style.css
└── README.md
```

---

# Current Features

- Custom Member Role
- Registration Form
- Government ID Upload
- Automatic Login
- Login Redirect
- Logout Redirect
- Member Dashboard
- Profile Update
- Password Reset
- WooCommerce Order History
- Order Details
- Secure Form Processing

---

# Future Improvements

- Email Verification
- Admin Approval Workflow
- Government ID Review Panel
- AJAX Registration
- Custom Dashboard UI
- Member Expiration
- Membership Levels
- Payment Integration
- Email Notifications
- Admin Member Management

---

# Author

**Md. Hafiz Ullah**

PHP & WordPress Developer



GPL v2 or later

This project is licensed under the GNU General Public License.
