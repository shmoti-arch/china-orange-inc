# China Orange Inc - Docker Website

A complete nginx-based website for ordering premium Chinese oranges, built with Docker, PHP, PostgreSQL, and Bootstrap.

## Features

### User Frontend
- **Beautiful Landing Page**: Modern, responsive design with orange-themed styling
- **Order System**: Minimum 5kg orders at $2.50/kg with real-time price calculation
- **User Registration/Login**: Secure authentication with $1000 starting balance
- **User Dashboard**: Order management, tracking, and balance display
- **Live Chat Support**: Real-time messaging with admin support

### Admin Panel
- **Admin Dashboard**: Complete overview of orders, users, and revenue
- **Order Management**: View, update order status, and track all orders
- **User Management**: View user details, update balances, and user statistics
- **Chat Support**: Respond to user messages and manage conversations
- **Real-time Updates**: Auto-refreshing data and notifications

### Technical Features
- **Docker Containerized**: Easy deployment with docker-compose
- **PostgreSQL Database**: Reliable data storage with proper relationships
- **Responsive Design**: Mobile-friendly Bootstrap 5 interface
- **Security**: Password hashing, session management, SQL injection protection
- **RESTful API**: Clean API endpoints for frontend-backend communication

## Quick Start

1. **Clone and Navigate**
   ```bash
   git clone <repository>
   cd china-orange-inc
   ```

2. **Start with Docker**
   ```bash
   docker-compose up -d
   ```

3. **Access the Website**
   - Website: http://localhost
   - Database: localhost:5432

## Default Accounts

### Admin Account
- **Username**: admin
- **Password**: admin123
- **Access**: Full admin panel access

### Test User Account
- **Username**: testuser
- **Password**: user123
- **Balance**: $1000

## Project Structure

```
├── docker-compose.yml          # Docker services configuration
├── nginx/                      # Nginx configuration
│   ├── nginx.conf
│   └── conf.d/default.conf
├── php/                        # PHP-FPM configuration
│   ├── Dockerfile
│   └── php.ini
├── database/                   # Database initialization
│   └── init.sql
└── web/                        # Website files
    ├── index.php               # Landing page
    ├── login.php               # User login
    ├── register.php            # User registration
    ├── dashboard.php           # User dashboard
    ├── logout.php              # Logout handler
    ├── config/                 # Configuration files
    │   ├── database.php
    │   └── session.php
    ├── includes/               # Shared components
    │   ├── header.php
    │   └── footer.php
    ├── assets/                 # Static assets
    │   ├── css/style.css
    │   └── js/main.js
    ├── api/                    # API endpoints
    │   ├── orders.php
    │   ├── chat.php
    │   └── dashboard.php
    └── admin/                  # Admin panel
        ├── index.php           # Admin dashboard
        ├── orders.php          # Order management
        ├── users.php           # User management
        └── chat.php            # Chat support
```

## Database Schema

### Users Table
- User authentication and profile information
- Balance tracking ($1000 starting balance)
- Admin role management

### Orders Table
- Order details (weight, price, status, address)
- Order status tracking (pending → processing → shipped → delivered)
- Automatic balance deduction

### Chat Messages Table
- User-admin messaging system
- Message history and timestamps
- Admin response tracking

## API Endpoints

### Orders API (`/api/orders.php`)
- `GET` - Retrieve order details
- `POST` - Create new order
- `DELETE` - Cancel pending order

### Chat API (`/api/chat.php`)
- `GET` - Retrieve chat messages
- `POST` - Send new message

### Dashboard API (`/api/dashboard.php`)
- `GET` - Get user balance and order statistics

## Configuration

### Environment Variables
- `DB_HOST`: PostgreSQL host (default: postgres)
- `DB_NAME`: Database name (default: orange_db)
- `DB_USER`: Database user (default: orange_user)
- `DB_PASS`: Database password (default: orange_pass123)

### Customization
- **Pricing**: Modify `$pricePerKg` in `/web/api/orders.php`
- **Starting Balance**: Update in `/database/init.sql` and registration
- **Styling**: Edit `/web/assets/css/style.css`
- **Features**: Extend API endpoints and database schema

## Development

### Local Development
```bash
# Start services
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

### Database Access
```bash
# Connect to PostgreSQL
docker-compose exec postgres psql -U orange_user -d orange_db
```

### File Permissions
Ensure proper permissions for web files:
```bash
chmod -R 755 web/
```

## Production Deployment

1. **Update Environment Variables**
   - Change default passwords
   - Set secure database credentials
   - Configure proper domain names

2. **SSL Configuration**
   - Add SSL certificates to nginx configuration
   - Update nginx to redirect HTTP to HTTPS

3. **Security Hardening**
   - Disable debug modes
   - Set up proper firewall rules
   - Regular security updates

## Troubleshooting

### Common Issues

1. **Database Connection Failed**
   - Check PostgreSQL container is running
   - Verify database credentials
   - Ensure database initialization completed

2. **Permission Denied**
   - Check file permissions on web directory
   - Verify PHP-FPM can access files

3. **Styles Not Loading**
   - Check nginx static file configuration
   - Verify asset file paths

### Logs
```bash
# Nginx logs
docker-compose logs nginx

# PHP logs
docker-compose logs php

# Database logs
docker-compose logs postgres
```

## License

This project is open source and available under the MIT License.

## Support

For support and questions:
- Check the troubleshooting section
- Review Docker and nginx documentation
- Submit issues for bugs or feature requests