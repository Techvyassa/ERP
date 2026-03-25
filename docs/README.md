# Laravel Multi-Tenant ERP Foundation - Documentation

Welcome to the documentation for the Laravel Multi-Tenant ERP Foundation system.

## Documentation Index

### For Developers

📘 **[Developer Onboarding Guide](DEVELOPER_GUIDE.md)**

- Project overview and architecture
- Development environment setup
- Multi-tenant architecture deep dive
- Middleware stack flow
- Database architecture
- Service layer patterns
- Testing strategy
- Common development tasks
- Coding standards and best practices

**Start here if you're new to the project!**

### For API Consumers

📗 **[API Documentation](API_DOCUMENTATION.md)**

- Complete API reference
- Authentication flow
- Error handling and status codes
- Request/response examples for all endpoints
- Rate limiting information
- Postman collection and OpenAPI spec

**Use this for integrating with the API.**

### For DevOps/Deployment

📕 **[Deployment Guide](DEPLOYMENT.md)**

- System requirements
- Environment setup instructions
- Database configuration
- Queue worker setup
- Scheduled jobs configuration
- Redis setup
- Payment gateway configuration
- Web server configuration (Nginx/Apache)
- SSL/TLS setup
- Monitoring and logging
- Backup strategy
- Deployment checklist

**Follow this for production deployment.**

## Quick Links

### Getting Started

1. **New Developer?** → Start with [Developer Onboarding Guide](DEVELOPER_GUIDE.md)
2. **Building an Integration?** → Check [API Documentation](API_DOCUMENTATION.md)
3. **Deploying to Production?** → Follow [Deployment Guide](DEPLOYMENT.md)

### Architecture Documents

Located in `.kiro/specs/laravel-multi-tenant-erp-foundation/`:

- **[Requirements Document](../.kiro/specs/laravel-multi-tenant-erp-foundation/requirements.md)** - Detailed system requirements
- **[Design Document](../.kiro/specs/laravel-multi-tenant-erp-foundation/design.md)** - Technical design specifications
- **[Tasks Document](../.kiro/specs/laravel-multi-tenant-erp-foundation/tasks.md)** - Implementation task breakdown

### Key Concepts

**Multi-Tenant Architecture**: Each organization gets its own isolated database (`erp_{org_slug}`), ensuring complete data separation.

**Two-Database Pattern**:

- **Control Database** (`ERP_saas_control`): Manages all tenants, subscriptions, billing
- **Tenant Databases** (`erp_*`): Store each organization's ERP operational data

**Middleware Stack**: Every API request flows through:

1. JWT Authentication
2. Tenant Resolution
3. Subscription Validation
4. RBAC Permission Check
5. Rate Limiting

**Subscription-Based Access**: All API access is gated by active subscription with module-level entitlements.

## Project Structure

```
material-management/
├── app/
│   ├── Console/Commands/      # Artisan commands
│   ├── Contracts/             # Service interfaces
│   ├── Exceptions/            # Custom exceptions
│   ├── Helpers/               # Helper classes
│   ├── Http/
│   │   ├── Controllers/       # API controllers
│   │   └── Middleware/        # Custom middleware
│   ├── Jobs/                  # Queue jobs
│   ├── Models/
│   │   ├── Control/           # Control DB models
│   │   └── Tenant/            # Tenant DB models
│   └── Services/              # Business logic
├── config/                    # Configuration
├── database/
│   ├── factories/             # Model factories
│   ├── migrations/
│   │   ├── control/           # Control DB migrations
│   │   └── tenant/            # Tenant DB migrations
│   └── seeders/               # Database seeders
├── docs/                      # Documentation (you are here!)
├── routes/                    # Route definitions
├── storage/logs/              # Application logs
└── tests/                     # Test suites
```

## Technology Stack

- **Framework**: Laravel 10.x
- **Language**: PHP 8.1+
- **Database**: MySQL 8.0+
- **Cache/Queue**: Redis 6.0+
- **Authentication**: JWT (tymon/jwt-auth)
- **Payment Gateways**: Razorpay, Stripe
- **Testing**: Pest PHP
- **API**: RESTful JSON APIs

## Support

### Development Support

- **Slack**: #erp-dev
- **Email**: dev-team@your-domain.com

### API Support

- **Email**: api-support@your-domain.com
- **Documentation**: [API_DOCUMENTATION.md](API_DOCUMENTATION.md)

### DevOps Support

- **Email**: devops@your-domain.com
- **Documentation**: [DEPLOYMENT.md](DEPLOYMENT.md)

## Contributing

We welcome contributions! Please:

1. Read the [Developer Onboarding Guide](DEVELOPER_GUIDE.md)
2. Follow our coding standards
3. Write tests for new features
4. Update documentation
5. Submit pull requests for review

## License

[Your License Here]

---

**Last Updated**: January 2024

For the most up-to-date information, always refer to the latest version of these documents in the repository.
