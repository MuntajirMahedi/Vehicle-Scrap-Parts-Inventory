# Vehicle Scrap Parts Inventory System

A web-based inventory management system for vehicle scrap parts. This system helps vendors manage their stock and generate sales reports efficiently.

## Features

- Admin Authentication & Authorization
- Brand Management
- Model Management
- Category Management
- Warehouse Management
- Parts Inventory
- Car Inventory
- Stock Management
- Sales Reports
- User-friendly Interface

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- XAMPP (recommended for local development)
- Modern web browser

## Installation

1. Clone or download this repository to your XAMPP's htdocs directory:
   ```
   git clone https://github.com/yourusername/vehicle-scrap-parts-inventory.git
   ```

2. Create a new MySQL database named 'noor_auto_scrap'

3. Import the database schema:
   - Open phpMyAdmin
   - Select the 'noor_auto_scrap' database
   - Import the `database.sql` file

4. Configure the database connection:
   - Open `config/database.php`
   - Update the database credentials if needed

5. Create an admin user:
   - Use phpMyAdmin to insert an admin record in the `tbl_admin` table
   - Make sure to hash the password using PHP's password_hash() function

6. Access the application:
   - Open your web browser
   - Navigate to `http://localhost/NOOR-AUTO-SCRAP`
   - Login with your admin credentials

## Directory Structure

```
NOOR-AUTO-SCRAP/
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
├── auth/
│   └── login.php
├── config/
│   └── database.php
├── dashboard/
├── includes/
├── uploads/
├── database.sql
├── index.php
└── README.md
```

## Security Considerations

- All passwords are hashed using PHP's password_hash() function
- SQL injection prevention using prepared statements
- XSS protection
- CSRF protection
- Input validation and sanitization
- Secure file upload handling

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Support

For support, please email support@example.com or open an issue in the repository.
