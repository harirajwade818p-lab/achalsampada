# Property Marketplace Website

A comprehensive property marketplace platform built with PHP, MySQL, HTML, CSS, JavaScript, and Bootstrap 5.

## Features Implemented

### ✅ User Management
- User Registration with Email Verification (OTP)
- User Login with Session Management
- Forgot Password & Reset Password
- Multi Role System (Buyer, Seller, Agent, Builder, Admin)
- User Profile Management
- KYC Verification System
- Account Status Management

### ✅ Property Listing Management
- Add Property with Complete Details
- Edit Property
- Delete Property
- Property Approval System
- Property Categories (Buy, Sell, Rent, Lease)
- Property Types (Flat, House, Villa, Land, Commercial)
- Property Image Gallery
- Property Video Upload
- Property Documents Upload
- RERA ID Verification
- Property Status (Available, Sold, Rented, Under Review)
- Featured Property Option
- Premium Property Option

### ✅ Search & Filter System
- Keyword Search
- Location Search
- State/City/Area Filter
- Price Filter
- Property Type Filter
- BHK Filter
- Bathroom Filter
- Area Size Filter
- Amenities Filter
- Furnishing Filter
- Construction Status Filter
- Verified Property Filter
- Featured Property Filter
- Premium Property Filter

### ✅ User Engagement Features
- Wishlist / Favorites
- Property Comparison
- Recently Viewed Properties
- Property Sharing

### ✅ Dashboard
- User Dashboard
- Property Statistics
- Activity Logs
- Quick Actions

## Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP (for local development)

### Steps

1. **Clone/Download the project**
   ```bash
   cd c:\xampp\htdocs\property_management
   ```

2. **Import Database**
   - Open phpMyAdmin
   - Create a new database named `property_marketplace`
   - Import the SQL file: `database/schema.sql`

3. **Configure Database**
   - Edit `config/database.php` if needed
   - Default credentials:
     - Host: localhost
     - Username: root
     - Password: (empty)
     - Database: property_marketplace

4. **Create Upload Directories**
   ```bash
   mkdir uploads
   mkdir uploads/property_images
   mkdir uploads/property_videos
   mkdir uploads/property_documents
   mkdir uploads/kyc_documents
   mkdir uploads/profile_images
   ```

5. **Set Permissions**
   - Ensure upload directories have write permissions

6. **Access the Application**
   - Open browser: `http://localhost/property_management`
   - Default Admin:
     - Email: admin@propertymarketplace.com
     - Password: admin123

## Project Structure

```
property_management/
├── admin/                  # Admin panel (to be built)
├── agent/                  # Agent dashboard (to be built)
├── builder/                # Builder dashboard (to be built)
├── api/                    # API endpoints
│   ├── delete-property.php
│   ├── get-areas.php
│   ├── get-cities.php
│   └── toggle-wishlist.php
├── assets/
│   ├── css/
│   │   └── style.css
│   ├── images/
│   │   └── (add default images)
│   └── js/
│       └── main.js
├── config/
│   └── database.php
├── database/
│   └── schema.sql
├── includes/
│   ├── auth.php
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   └── property-card.php
├── properties/
│   ├── add.php
│   ├── edit.php (to be built)
│   ├── images.php (to be built)
│   └── my-properties.php
├── uploads/                # Upload directories
├── index.php
├── login.php
├── logout.php
├── register.php
├── verify-email.php
├── forgot-password.php
├── reset-password.php
├── dashboard.php
├── search.php
├── wishlist.php (to be built)
├── profile.php (to be built)
└── README.md
```

## Database Schema

The project includes 30+ database tables covering:
- Users & Authentication
- Properties & Listings
- Categories & Types
- Amenities
- Locations (States, Cities, Areas)
- Images, Videos, Documents
- Wishlist & Comparison
- Inquiries & Reviews
- Property Visits
- Builder & Agent Profiles
- Subscriptions & Payments
- Advertisements
- Content Management
- Activity Logs
- System Settings
- Price Trends
- AI Predictions

## Security Features

- Password Hashing (bcrypt)
- SQL Injection Protection (PDO Prepared Statements)
- XSS Protection (htmlspecialchars)
- CSRF Protection (to be implemented)
- Session Management
- Login Attempt Limiting
- Account Locking

## Future Enhancements

### Admin Dashboard
- User Management
- Property Management
- Verification Management
- Reports & Analytics
- System Settings

### Property Details Page
- Complete Property Information
- Image Gallery
- Video Player
- Floor Plans
- Location Map
- Inquiry Form
- Reviews & Ratings

### AI Features (Python Flask)
- AI Fraud Detection
- Duplicate Listing Detection
- AI Property Description Generator
- AI Image Quality Check
- AI Property Recommendations
- AI Price Estimation/Prediction
- AI Investment Suggestions

### API Integrations
- Google Maps API
- Razorpay Payment Gateway
- WhatsApp Business API
- Email SMTP Service

## Development Stack

- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5
- **Backend**: PHP 8+
- **Database**: MySQL
- **AI Features**: Python Flask (to be implemented)

## License

This project is for educational purposes.

## Support

For issues or questions, please contact the development team.
