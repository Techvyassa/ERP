# ERP API - Postman Collection Guide

## Overview

This directory contains a comprehensive Postman collection for the ERP system API. The collection is organized into logical modules covering all aspects of the ERP system.

## File Location

- **Postman Collection**: `erp-api-postman-collection.json`
- **Base URL Variable**: `http://localhost:8000` (default, configurable)

## Import into Postman

1. Open Postman
2. Click on **Import** button (top left)
3. Select **File** and choose `erp-api-postman-collection.json`
4. The collection will appear in your Collections sidebar

## Environment Setup

### Required Environment Variables

The collection uses the following environment variables:

| Variable | Default Value | Description |
|----------|---------------|-------------|
| `base_url` | `http://localhost:8000` | Your API base URL |
| `access_token` | (empty) | JWT access token (auto-populated after login) |
| `refresh_token` | (empty) | JWT refresh token (auto-populated after login) |

### Setup Instructions

1. In Postman, click on the **Environments** icon (left sidebar)
2. Create a new environment or select an existing one
3. Set the `base_url` variable to match your API server
4. Save the environment

## Collection Structure

The collection is organized into the following modules:

### 1. Health & Status
- Health Check - Verify API is running
- Rate Limit Status - Check current rate limit

### 2. Authentication
- Login - Authenticate and receive tokens
- Refresh Token - Refresh expired access token
- Logout - Invalidate tokens
- Forgot Password - Request password reset
- Reset Password - Set new password
- Firebase Login - Authenticate via Firebase
- Get Current User - Fetch authenticated user details
- Debug My Permissions - View user permissions

### 3. Organizations
- Register Organization - Create new organization
- Check Slug Availability - Verify organization slug
- Suggest Slug - Get suggested slugs
- Get All Slugs - List all organization slugs

### 4. Subscription Plans
- List All Plans - View available subscription plans
- Get Plan By Code - Fetch specific plan details

### 5. Profile Completion
- Get Profile Status - Check profile completion status
- Update Organization Profile - Complete organization details
- Get Master Data Status - Check master data setup progress

### 6. Subscriptions
- Get Current Subscription - View active subscription
- Get Available Plans - Browse upgrade options
- Upgrade Subscription - Change subscription plan
- Cancel Subscription - Terminate subscription

### 7. Master Dashboard
- Get Master Stats - Dashboard statistics

### 8. Departments (ADMIN)
- CRUD operations for departments
- Import/Export functionality
- Department roles lookup

### 9. Roles (ADMIN)
- Role management (CRUD)
- Role permissions configuration
- Permission matrix updates

### 10. Users (ADMIN)
- User management (CRUD)
- CSV import functionality
- User activation/deactivation

### 11. HSN Codes (ADMIN)
- HSN code master data management

### 12. GST Taxes (ADMIN)
- GST tax configuration

### 13. Currencies (ADMIN)
- Currency master data

### 14. UOMs (ADMIN)
- Unit of Measure management
- Barcode generation

### 15. Warehouses (ADMIN)
- Warehouse master data
- Barcode support

### 16. Bin Locations (ADMIN)
- Warehouse bin location management
- Barcode support

### 17. Materials (ADMIN)
- Material master data
- Barcode search and generation
- Material search functionality

### 18. Products (ADMIN)
- Product master data
- Barcode support

### 19. Vendors (ADMIN)
- Vendor master data
- Vendor blacklisting

### 20. Vendor Contacts (ADMIN)
- Vendor contact person management

### 21. Vendor Material Map / AVL (ADMIN)
- Approved Vendor List management
- Vendor-material mapping

### 22. Purchase Requisitions (STORE)
- Create and manage purchase requisitions
- Approval workflow
- Master data lookups

### 23. Purchase Orders (STORE)
- Purchase order management
- Status transitions (DRAFT → PENDING → APPROVED → OPEN → CLOSED)
- Email to vendor functionality

### 24. ASN - Advance Shipping Notice (STORE)
- Create and track advance shipping notices
- Status workflow (DRAFT → SENT → IN_TRANSIT → ARRIVED)
- CSV upload support

### 25. Gate Entries (SECURITY)
- Record material gate entries
- Link to PO and ASN
- Vehicle tracking

### 26. Material Receipts (STORE)
- View material receipts
- Legacy GRN flow support

### 27. GRN - Goods Receipt Note (STORE)
- Create and manage GRN
- Status workflow (PROVISIONAL → QC_PENDING → ACCEPTED/REJECTED)
- Post-QC updates

### 28. QC Test Types (ADMIN)
- Quality control test type configuration

### 29. QC Parameters (ADMIN)
- QC parameter setup per material
- Min/max/target value configuration

### 30. Quality Control (QC)
- Record QC inspections
- Test result recording
- QC decision making (ACCEPT/REJECT)
- Status workflow (PENDING → IN_PROGRESS → COMPLETED → DECISION_MADE)

### 31. Putaway (STORE)
- Create putaway tasks
- Track putaway progress
- Bin scanning functionality
- Status workflow (PENDING → IN_PROGRESS → COMPLETED)

### 32. Stock (STORE)
- Available stock check (ATP)
- Stock snapshot by buckets
- Stock history/audit trail
- Warehouse stock summary
- Bucket drill-down

### 33. BOM Headers (ADMIN)
- Bill of Materials header management
- Revision control

### 34. BOM Details (ADMIN)
- BOM line item management
- Scrap percentage configuration

### 35. Production Orders
- Create and manage production orders
- Start production
- Confirm finished goods
- Variance analysis

### 36. Material Issue Requests
- Request material issues from warehouse
- Approval workflow
- Barcode scanning for issues

### 37. Packing Orders
- Create packing orders
- Carton management
- Scan items into cartons
- Seal cartons
- Complete packing

### 38. Feature Controls (ADMIN)
- Feature flag management
- Enable/disable features

### 39. Webhooks (Public)
- Razorpay webhook endpoint
- Stripe webhook endpoint

## Middleware & Permissions

Most endpoints require specific module permissions. The middleware stack includes:

1. **validate.jwt** - JWT token validation
2. **resolve.tenant** - Multi-tenant database resolution
3. **validate.subscription** - Subscription status validation
4. **check.module.permission** - RBAC permission checks

### Module Permission Codes

- `ADMIN` - Administration module
- `STORE` - Store/Inventory module
- `QC` - Quality Control module
- `SECURITY` - Security/Gate module

## Usage Examples

### Authentication Flow

1. **Login**
   ```
   POST {{base_url}}/api/v1/auth/login
   Body: { "email": "admin@example.com", "password": "password" }
   ```
   
   Response automatically saves tokens to environment variables.

2. **Make Authenticated Request**
   ```
   GET {{base_url}}/api/v1/auth/me
   Authorization: Bearer {{access_token}}
   ```

3. **Refresh Token** (when access token expires)
   ```
   POST {{base_url}}/api/v1/auth/refresh
   Body: { "refresh_token": "{{refresh_token}}" }
   ```

### Creating a Purchase Order

```http
POST {{base_url}}/api/v1/purchase-orders
Authorization: Bearer {{access_token}}
Content-Type: application/json

{
  "po_date": "2024-01-15",
  "vendor_id": 1,
  "warehouse_id": 1,
  "lines": [
    {
      "material_id": 1,
      "quantity": 100,
      "unit_price": 10.00,
      "gst_tax_id": 1
    }
  ]
}
```

### Recording QC Test Results

```http
POST {{base_url}}/api/v1/qc/1/test-results
Authorization: Bearer {{access_token}}
Content-Type: application/json

{
  "qc_parameter_id": 1,
  "observed_value": 145.5,
  "remarks": "Within tolerance"
}
```

## Testing Workflows

### Complete Procure-to-Pay Flow

1. Create Purchase Requisition → Submit → Approve
2. Create Purchase Order → Submit → Approve → Release
3. Create ASN → Send → Mark In Transit → Mark Arrived
4. Create Gate Entry
5. Create GRN → Approve (moves to QC Pending)
6. Create QC Record → Start Inspection → Record Results → Complete → Make Decision
7. Create Putaway Task → Start → Complete (scan bin)

### Production Order Flow

1. Create Production Order
2. Create Material Issue Request → Approve → Scan Materials
3. Start Production
4. Confirm Finished Goods
5. Create Packing Order → Create Cartons → Scan Items → Seal → Complete

## Tips

1. **Auto-save Tokens**: The login request automatically saves tokens to environment variables using Postman's test scripts.

2. **Use Collections Runner**: Run entire workflows or specific folders using Postman's Collection Runner.

3. **Environment Switching**: Easily switch between development, staging, and production by changing environments.

4. **Pre-request Scripts**: Add pre-request scripts for dynamic data generation if needed.

5. **Test Scripts**: Add test scripts to validate responses and chain requests.

## Troubleshooting

### 401 Unauthorized
- Ensure you're logged in
- Check if access token is set in environment variables
- Try refreshing the token

### 403 Forbidden
- Your user doesn't have the required module permission
- Contact admin to grant appropriate permissions

### 404 Not Found
- Verify the base_url is correct
- Check if tenant database is properly configured

### 422 Validation Error
- Review request body format
- Ensure all required fields are provided
- Check data types match API expectations

## Additional Resources

- Full API Documentation: See `docs/API_DOCUMENTATION.md`
- Authentication Flow: See `md_files/AUTHENTICATION_FLOW.md`
- Multi-Tenancy Setup: See `md_files/MULTI_TENANCY_SETUP_GUIDE.md`

## Support

For issues or questions about the API, refer to the project documentation or contact the development team.
