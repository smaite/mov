# Sasto Hub - Multi-Vendor E-commerce Platform

A comprehensive multi-vendor e-commerce platform built with PHP, MySQL, and Tailwind CSS. Similar to platforms like Daraz and Amazon, Sasto Hub allows multiple vendors to sell their products while providing customers with a seamless shopping experience.

## 🚀 Features

### For Customers
- **User Registration & Authentication** - Secure login/register system
- **Product Browsing** - Browse products by categories with advanced filtering
- **Search Functionality** - Powerful search with relevance scoring
- **Shopping Cart** - Add/remove items, update quantities
- **Secure Checkout** - Multiple payment methods (COD, Bank Transfer)
- **Order Management** - Track orders, view order history
- **Wishlist** - Save favorite products for later
- **Product Reviews** - Rate and review purchased products
- **Responsive Design** - Works on all devices

### For Vendors
- **Vendor Registration** - Apply to become a vendor
- **Shop Management** - Manage shop profile and settings
- **Product Management** - Add, edit, and manage products
- **Order Processing** - View and manage orders
- **Sales Analytics** - Track sales and performance
- **Inventory Management** - Stock tracking and alerts

### For Administrators
- **User Management** - Manage customers and vendors
- **Product Oversight** - Review and approve products
- **Order Management** - Monitor all platform orders
- **Vendor Approval** - Approve/reject vendor applications
- **Platform Analytics** - Overall platform statistics
- **Content Management** - Manage categories and site content

## 🛠 Technology Stack

- **Backend**: PHP 8+ with PDO
- **Database**: MySQL 8+
- **Frontend**: HTML5, Tailwind CSS, JavaScript
- **Icons**: Font Awesome 6
- **Security**: Password hashing, CSRF protection, input sanitization

## 📦 Installation

### Prerequisites
- PHP 8.0 or higher
- MySQL 8.0 or higher
- Web server (Apache/Nginx)
- Composer (optional, for dependencies)

### Setup Instructions

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd sasto-hub
   ```

2. **Database Setup**
   ```bash
   # Create database
   mysql -u root -p
   ```
   
   ```sql
   CREATE DATABASE sasto_hub;
   USE sasto_hub;
   SOURCE database/schema.sql;
   SOURCE database/demo_data.sql;  # Optional: Load demo data
   ```

3. **Configuration**
   ```php
   # Update database credentials in config/database.php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'sasto_hub');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

4. **Set Permissions**
   ```bash
   chmod 755 uploads/
   chmod 755 uploads/products/
   chmod 755 uploads/users/
   chmod 755 uploads/vendors/
   ```

5. **Access the Application**
   - Open your web browser
   - Navigate to `http://localhost/your-project-folder`
   - Start exploring Sasto Hub!

## 🎮 Demo Accounts

The demo data includes several test accounts:

| Account Type | Email | Password | Description |
|-------------|-------|----------|-------------|
| Admin | admin@sastohub.com | password | Full platform access |
| Customer | customer@test.com | password | Regular customer account |
| Vendor | vendor@test.com | password | TechHub Nepal vendor |
| Vendor | vendor2@test.com | password | Fashion Forward vendor |

## 📁 Project Structure

```
sasto-hub/
├── config/                 # Configuration files
│   ├── config.php          # Main configuration
│   └── database.php        # Database connection
├── database/               # Database files
│   ├── schema.sql          # Database structure
│   └── demo_data.sql       # Sample data
├── includes/               # Reusable components
│   ├── header.php          # Site header
│   ├── footer.php          # Site footer
│   └── product-card.php    # Product card component
├── pages/                  # Main application pages
│   ├── auth/               # Authentication pages
│   ├── products/           # Product pages
│   ├── cart/               # Shopping cart
│   ├── checkout/           # Checkout process
│   ├── orders/             # Order management
│   ├── vendor/             # Vendor dashboard
│   └── admin/              # Admin panel
├── ajax/                   # AJAX endpoints
│   ├── cart.php            # Cart operations
│   └── wishlist.php        # Wishlist operations
├── assets/                 # Static assets
│   └── css/                # Custom styles
├── uploads/                # File uploads
│   ├── products/           # Product images
│   ├── users/              # User avatars
│   └── vendors/            # Vendor logos
├── index.php               # Main entry point
└── README.md               # This file
```

## 🔧 Configuration

### Environment Setup
Update `config/config.php` with your specific settings:
- Site URL and paths
- Upload directories
- Email settings
- Security settings

### Database Configuration
Update `config/database.php` with your database credentials.

### File Permissions
Ensure the uploads directory is writable:
```bash
chmod -R 755 uploads/
```

## 🔒 Security Features

- **Password Hashing**: Secure password storage using PHP's password_hash()
- **CSRF Protection**: Cross-site request forgery protection
- **Input Sanitization**: All user inputs are sanitized and validated
- **SQL Injection Prevention**: Using prepared statements with PDO
- **Session Security**: Secure session management
- **File Upload Security**: Restricted file types and sizes

## 🎨 UI/UX Features

- **Responsive Design**: Mobile-first approach with Tailwind CSS
- **Modern Interface**: Clean and intuitive design
- **Interactive Elements**: Smooth animations and transitions
- **User Feedback**: Toast notifications and loading states
- **Accessibility**: Keyboard navigation and screen reader friendly

## 📱 Mobile Responsiveness

The platform is fully responsive and works seamlessly on:
- Desktop computers
- Tablets
- Mobile phones
- Various screen sizes and orientations

## 🚦 Getting Started

1. **For Customers**: Register an account and start browsing products
2. **For Vendors**: Apply for vendor account and get approved by admin
3. **For Admins**: Use the admin account to manage the platform

## 🔄 Future Enhancements

- Payment gateway integration (Stripe, PayPal, eSewa)
- Real-time notifications
- Advanced analytics dashboard
- Multi-language support
- Mobile app development
- API for third-party integrations
- Advanced search with Elasticsearch
- Product comparison feature
- Live chat support

## 🐛 Known Issues

- Payment gateway integration is not yet implemented
- Email notifications are not yet functional
- Advanced vendor analytics in development

## 🤝 Contributing

We welcome contributions! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Submit a pull request

## 📄 License

This project is open source and available under the [MIT License](LICENSE).

## 📞 Support

For support and questions:
- Create an issue on GitHub
- Contact: info@sastohub.com

## 🙏 Acknowledgments

- Tailwind CSS for the amazing utility-first CSS framework
- Font Awesome for the comprehensive icon library
- PHP community for excellent documentation and resources

---

**Sasto Hub** - Making online shopping accessible and affordable for everyone in Nepal! 🇳🇵
