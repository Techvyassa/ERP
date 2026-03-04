# Bugfix Requirements Document

## Introduction

Users attempting to authenticate via Google OAuth are encountering a "No authentication token found" error when redirected to the dashboard after successful Google authentication. The bug occurs because the frontend JavaScript successfully receives the JWT access token from the Laravel backend (`/api/v1/auth/firebase-login`) and sets it as a cookie, but there is a timing issue where the cookie is not properly available when the page redirects to `/dashboard`. The dashboard route is protected by the `WebJWTAuth` middleware, which checks for the `auth_token` cookie and fails to find it, resulting in the error message and redirect back to login.

Email/password authentication works correctly because it follows the same flow but may have different timing characteristics. The Firebase authentication with Google completes successfully (returns user info and JWT token), but the subsequent cookie-based authentication fails.

## Bug Analysis

### Current Behavior (Defect)

1.1 WHEN a user completes Google OAuth authentication and the frontend receives a successful response with `access_token` from `/api/v1/auth/firebase-login` THEN the system sets the `auth_token` cookie via JavaScript and immediately redirects to `/dashboard`, but the cookie is not available to the `WebJWTAuth` middleware

1.2 WHEN the `WebJWTAuth` middleware processes the `/dashboard` request after Google authentication THEN the system fails to find the `auth_token` cookie and returns "No authentication token found" error, redirecting the user back to login

1.3 WHEN the cookie is set via JavaScript `document.cookie` and the page immediately redirects via `window.location.href` THEN the browser may not have sufficient time to persist the cookie before the navigation occurs

### Expected Behavior (Correct)

2.1 WHEN a user completes Google OAuth authentication and the frontend receives a successful response with `access_token` from `/api/v1/auth/firebase-login` THEN the system SHALL ensure the `auth_token` cookie is properly set and available before redirecting to `/dashboard`

2.2 WHEN the `WebJWTAuth` middleware processes the `/dashboard` request after Google authentication THEN the system SHALL successfully retrieve the `auth_token` cookie and validate the JWT token, allowing access to the dashboard

2.3 WHEN the authentication token needs to be stored for web routes THEN the system SHALL use a reliable mechanism that ensures the cookie is available for subsequent requests, either by using server-side cookie setting or ensuring proper timing for client-side cookie operations

### Unchanged Behavior (Regression Prevention)

3.1 WHEN a user authenticates via email/password and the authentication is successful THEN the system SHALL CONTINUE TO set the `auth_token` cookie and redirect to dashboard successfully

3.2 WHEN the `WebJWTAuth` middleware processes requests for protected routes with a valid `auth_token` cookie THEN the system SHALL CONTINUE TO successfully validate the JWT token and allow access

3.3 WHEN the `/api/v1/auth/firebase-login` endpoint receives valid Firebase authentication credentials THEN the system SHALL CONTINUE TO return a successful response with `access_token`, `refresh_token`, user data, and organization data

3.4 WHEN a user accesses protected routes without a valid authentication token THEN the system SHALL CONTINUE TO redirect to the login page with an appropriate error message

3.5 WHEN the `auth_token` cookie is set and the user navigates between protected routes THEN the system SHALL CONTINUE TO maintain the authenticated session without requiring re-authentication
