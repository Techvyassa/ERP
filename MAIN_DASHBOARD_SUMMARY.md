# Main Dashboard Summary

## Overview

The main dashboard (`/dashboard`) serves as the **central navigation hub** for the entire application. After login, users land here and can navigate to any module or section of the application.

---

## 🎯 Purpose

1. **Navigation Hub** - Central point for accessing all modules
2. **Progress Tracking** - Shows profile and master data completion
3. **Quick Overview** - Displays key statistics and metrics
4. **User Management** - Access to profile, settings, and logout

---

## 🏗️ Structure

### Header Section
- **Logo & Organization Name** - Displays current organization
- **Notification Icon** - Quick access to notifications
- **Help Icon** - Access to help resources
- **User Dropdown** - Profile, settings, and logout

### Profile Completion Banner (Conditional)
- Shows only if profile or master data is incomplete
- Displays overall completion percentage
- Progress bar visualization
- Dismissible by user

### Quick Stats Cards (4 Cards)
1. **Active Materials** - Count of materials in inventory
2. **Production Orders** - Active production orders
3. **Active Vendors** - Approved vendor count
4. **Team Members** - Total users in organization

### Navigation Cards (7 Cards)

#### 1. Go to Dashboard (Featured)
- **Style:** Blue gradient, prominent
- **Icon:** dashboard
- **Route:** `/org/{org_slug}/dashboard`
- **Description:** Main workspace with sidebar navigation

#### 2. Organization Profile
- **Style:** White with blue gradient icon
- **Icon:** business
- **Route:** `/org/{org_slug}/profile-completion`
- **Progress:** Shows profile completion percentage

#### 3. Master Data Setup
- **Style:** White with emerald gradient icon
- **Icon:** database
- **Route:** `/org/{org_slug}/master-setup`
- **Progress:** Shows master data completion percentage

#### 4. Departments
- **Style:** White with purple gradient icon
- **Icon:** apartment
- **Route:** `/org/{org_slug}/departments`

#### 5. Users & Roles
- **Style:** White with pink gradient icon
- **Icon:** groups
- **Route:** `/org/{org_slug}/users`

#### 6. Production
- **Style:** White with orange gradient icon
- **Icon:** precision_manufacturing
- **Route:** `/org/{org_slug}/production`
- **Status:** Coming soon

#### 7. Inventory
- **Style:** White with cyan gradient icon
- **Icon:** inventory
- **Route:** `/org/{org_slug}/inventory`
- **Status:** Coming soon

---

## 🔄 User Flow

### First-Time User
```
Login
  ↓
Main Dashboard
  ↓ (See completion banner)
Click "Organization Profile"
  ↓
Complete profile setup
  ↓
Return to main dashboard
  ↓
Click "Master Data Setup"
  ↓
Configure master data
  ↓
Return to main dashboard
  ↓
Click "Go to Dashboard"
  ↓
Access full application
```

### Returning User
```
Login
  ↓
Main Dashboard
  ↓ (No completion banner)
Click "Go to Dashboard"
  ↓
Access full application
```

### Quick Access User
```
Login
  ↓
Main Dashboard
  ↓
Click specific module card
  ↓
Direct access to that module
```

---

## 💾 Data Loading

### On Page Load
1. Check authentication (access_token in localStorage)
2. Load user data from localStorage
3. Fetch profile completion status from API
4. Fetch master data status from API
5. Update UI with organization name
6. Update progress bars
7. Show/hide completion banner

### API Calls
```javascript
// Profile completion status
GET /api/v1/profile-completion/status
Headers: {
    'Authorization': 'Bearer {access_token}',
    'X-Org-Slug': '{org_slug}'
}

// Master data status
GET /api/v1/profile-completion/master-data-status
Headers: {
    'Authorization': 'Bearer {access_token}',
    'X-Org-Slug': '{org_slug}'
}
```

---

## 🎨 Design System

### Colors
- **Primary:** #193261 (Navy Blue)
- **Background:** #F9FAFB (Light Gray)
- **Cards:** #FFFFFF (White)
- **Text:** #111827 (Dark Gray)
- **Borders:** #E5E7EB (Light Gray)

### Typography
- **Font Family:** Inter
- **Headings:** Bold, 700-900 weight
- **Body:** Regular, 400-500 weight
- **Small Text:** 12-14px

### Icons
- **Library:** Material Symbols Outlined
- **Size:** 20-24px (standard), 32px (large)
- **Style:** Outlined, rounded

### Card Styles
- **Featured Card:** Gradient background, white text
- **Standard Card:** White background, hover shadow
- **Progress Card:** White background with progress bar

---

## 📱 Responsive Design

### Desktop (1024px+)
- 3-column grid for navigation cards
- 4-column grid for quick stats
- Full header with all elements

### Tablet (768px - 1023px)
- 2-column grid for navigation cards
- 2-column grid for quick stats
- Condensed header

### Mobile (< 768px)
- 1-column stack for all cards
- Simplified header
- Touch-optimized buttons

---

## 🔧 JavaScript Functions

### loadDashboardData()
Loads all dashboard data on page load:
- User information
- Organization details
- Profile completion status
- Master data status

### updateProfileProgress()
Updates profile completion progress bar and percentage.

### updateMasterProgress()
Updates master data completion progress bar and percentage.

### updateCompletionBanner()
Shows/hides completion banner based on overall progress.

### dismissBanner()
Hides completion banner and stores preference in localStorage.

### navigateTo(section)
Navigates to specified section with org_slug validation:
- Checks for org_slug in localStorage
- Builds route with org_slug
- Redirects to target page

### toggleUserMenu()
Shows/hides user dropdown menu.

### logout()
Logs out user:
- Clears localStorage
- Calls logout API
- Redirects to login page

---

## 🔐 Security

### Authentication Check
```javascript
const accessToken = localStorage.getItem('access_token');
if (!accessToken) {
    window.location.href = '/login';
}
```

### Organization Validation
```javascript
const orgSlug = localStorage.getItem('org_slug');
if (!orgSlug) {
    alert('Organization not found. Please login again.');
    window.location.href = '/login';
}
```

### API Headers
```javascript
headers: {
    'Authorization': `Bearer ${accessToken}`,
    'X-Org-Slug': orgSlug,
    'Accept': 'application/json'
}
```

---

## 🚀 Performance

### Optimization Strategies
1. **Lazy Loading** - Load data only when needed
2. **LocalStorage Caching** - Cache user and org data
3. **Conditional Rendering** - Show/hide elements based on state
4. **Debounced API Calls** - Prevent excessive API requests
5. **Progressive Enhancement** - Basic functionality without JavaScript

### Load Time Targets
- **Initial Load:** < 1 second
- **API Response:** < 500ms
- **Navigation:** Instant (client-side)

---

## 🧪 Testing Checklist

### Functionality
- [ ] Login redirects to main dashboard
- [ ] Organization name displays correctly
- [ ] User name displays correctly
- [ ] All navigation cards work
- [ ] Progress bars update correctly
- [ ] Completion banner shows/hides correctly
- [ ] User dropdown menu works
- [ ] Logout works correctly

### Data Loading
- [ ] Profile completion status loads
- [ ] Master data status loads
- [ ] Quick stats display (even if 0)
- [ ] Fallback to localStorage works

### Navigation
- [ ] "Go to Dashboard" navigates correctly
- [ ] All module cards navigate correctly
- [ ] org_slug validation works
- [ ] Invalid org_slug shows error

### Responsive
- [ ] Desktop layout works
- [ ] Tablet layout works
- [ ] Mobile layout works
- [ ] Touch interactions work

### Security
- [ ] Unauthenticated users redirect to login
- [ ] Invalid tokens redirect to login
- [ ] API calls include proper headers
- [ ] Logout clears all data

---

## 📝 Maintenance

### Adding New Card
1. Add HTML card in main dashboard
2. Add route to `navigateTo()` function
3. Add icon from Material Symbols
4. Test navigation
5. Update documentation

### Updating Progress Tracking
1. Modify API endpoint
2. Update `loadDashboardData()` function
3. Update progress calculation
4. Test with various completion states

### Changing Design
1. Update Tailwind classes
2. Test responsive behavior
3. Verify accessibility
4. Update design system documentation

---

## 🐛 Common Issues

### Issue: Organization name not showing
**Solution:** Check if `org_data` is stored in localStorage after login.

### Issue: Navigation not working
**Solution:** Verify `org_slug` exists in localStorage and is valid.

### Issue: Progress bars not updating
**Solution:** Check API endpoints are returning correct data structure.

### Issue: Completion banner always showing
**Solution:** Verify progress calculation logic and API responses.

---

## 🔮 Future Enhancements

### Planned Features
1. **Real-time Stats** - Live updates for quick stats cards
2. **Notifications** - Real notification system
3. **Search** - Global search functionality
4. **Shortcuts** - Keyboard shortcuts for navigation
5. **Customization** - User-customizable dashboard layout
6. **Widgets** - Draggable widget system
7. **Analytics** - Usage analytics and insights
8. **Multi-language** - Internationalization support

### Technical Improvements
1. **State Management** - Implement Vue.js or Alpine.js
2. **API Optimization** - Batch API calls
3. **Caching Strategy** - Implement service worker
4. **Error Handling** - Better error messages and recovery
5. **Loading States** - Skeleton screens and spinners

---

## 📚 Related Documentation

- [LOGIN_FLOW.md](./LOGIN_FLOW.md) - Complete login and navigation flow
- [ROUTE_REFERENCE.md](./ROUTE_REFERENCE.md) - All application routes
- [FOLDER_STRUCTURE.md](./FOLDER_STRUCTURE.md) - Complete folder organization
- [NAVIGATION_FLOW.md](./NAVIGATION_FLOW.md) - Navigation patterns

---

**Last Updated:** March 5, 2026  
**Version:** 1.0  
**Status:** Production Ready ✅
