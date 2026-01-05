# Examination Website

A complete, production-ready online examination system built with PHP, MySQL, and modern web technologies.

## Features

### Student Features
- **Browse Subjects**: Explore available majors, materials, and chapters
- **Take Exams**: Interactive exam interface with timer and navigation
- **Instant Results**: Get immediate feedback with detailed explanations
- **Question Review**: See which questions you got right/wrong with explanations
- **Mobile Friendly**: Responsive design works on all devices
- **Retake Exams**: Practice multiple times to improve (if enabled)

### Admin Features
- **Comprehensive Dashboard**: Statistics, charts, and recent activity
- **Content Management**: Full CRUD for majors, materials, chapters, and questions
- **Bulk Operations**: Delete or export multiple questions at once
- **Exam Results**: View and manage all exam results with filtering
- **Settings Management**: Configure exam timers, passing scores, and more
- **Security**: CSRF protection, SQL injection prevention, XSS prevention

### Technical Features
- **Modern UI**: Clean, professional design with smooth animations
- **Performance Optimized**: Meets Core Web Vitals standards
- **Security Hardened**: Multiple layers of security protection
- **Responsive Design**: Mobile-first approach with progressive enhancement
- **Easy Installation**: One-click automatic installer

## Technology Stack

- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL 5.7+ with InnoDB engine
- **Frontend**: HTML5, CSS3 (Flexbox & Grid), Vanilla JavaScript
- **Styling**: Custom CSS with CSS Variables
- **Icons**: Font Awesome 6
- **Charts**: Chart.js for admin dashboard
- **Animations**: AOS (Animate On Scroll)

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- mod_rewrite enabled
- PHP extensions: PDO, PDO_MySQL, GD, JSON, Fileinfo, MBString
- Minimum 50MB disk space

## Installation

1. Upload all files to your web server
2. Create a MySQL database
3. Navigate to `http://yourdomain.com/installation.php`
4. Follow the step-by-step installation wizard
5. Login with default credentials: username: `admin`, password: `admin123`
6. **IMPORTANT**: Change the admin password immediately

For detailed installation instructions, see [INSTALLATION_GUIDE.md](INSTALLATION_GUIDE.md)

## Default Login

**Admin Panel:** `http://yourdomain.com/admin/login.php`
- Username: `admin`
- Password: `admin123`

## File Structure

```
examination-website/
├── public/              # Public website pages
│   ├── index.php       # Landing page
│   ├── major.php       # Major detail page
│   ├── material.php    # Material detail page
│   ├── exam.php        # Exam interface
│   ├── results.php     # Results page
│   └── submit_exam.php # Exam submission handler
├── admin/              # Admin panel
│   ├── login.php       # Admin login
│   ├── dashboard.php   # Admin dashboard
│   ├── logout.php      # Admin logout
│   ├── majors/         # Major management
│   ├── materials/      # Material management
│   ├── chapters/       # Chapter management
│   ├── questions/      # Question management
│   ├── results/        # Results management
│   └── includes/       # Admin includes
├── includes/           # Core includes
│   ├── functions.php   # Utility functions
│   ├── header.php      # Public header
│   └── footer.php      # Public footer
├── config/             # Configuration files
│   ├── database.php    # Database config (created by installer)
│   └── constants.php   # Site constants (created by installer)
├── assets/             # Static assets
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   └── images/        # Images and icons
├── uploads/           # Uploaded files
├── installation.php   # Automatic installer
├── database_schema.sql # Database schema
└── .htaccess         # Apache configuration
```

## Security Features

- **SQL Injection Prevention**: PDO prepared statements throughout
- **XSS Prevention**: htmlspecialchars() on all output with ENT_QUOTES
- **CSRF Protection**: Token-based form protection
- **Password Security**: password_hash() with PASSWORD_DEFAULT
- **File Upload Security**: Type validation, size limits, unique filenames
- **Session Security**: Session regeneration, secure cookies, timeout
- **Input Validation**: Server-side validation for all inputs
- **Security Headers**: X-Frame-Options, X-XSS-Protection, CSP

## Performance Features

- **GZIP Compression**: Enabled via .htaccess
- **Browser Caching**: Optimized cache headers
- **Image Optimization**: WebP support with fallbacks
- **CSS/JS Minification**: Compressed assets
- **Database Optimization**: Proper indexes and queries
- **Lazy Loading**: Images below the fold
- **Core Web Vitals**: LCP < 2.5s, FID < 100ms, CLS < 0.1

## Browser Support

- Chrome 80+
- Firefox 75+
- Safari 13+
- Edge 80+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Customization

The website uses CSS Variables for easy theming. Key variables are defined in `:root` and can be modified to change colors, spacing, and other design elements.

## License

This project is open-source and available under the MIT License.

## Support

For support and questions:
- Check the installation guide
- Review the troubleshooting section
- Check server error logs
- Ensure all requirements are met

## Contributing

Contributions are welcome! Please:
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Roadmap

- [ ] Student registration system
- [ ] Advanced analytics and reporting
- [ ] Question categories and difficulty levels
- [ ] Timer per question
- [ ] Exam scheduling
- [ ] Certificate generation
- [ ] Multi-language support
- [ ] API for mobile apps

---

Built with ❤️ by the Examination Website Team