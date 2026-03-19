<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LogoutController extends Controller
{
    /**
     * Handle logout and redirect to the appropriate login page.
     * Department portals pass their login URL via 'redirect_to'.
     */
    public function logout(Request $request)
    {
        $redirectTo = $request->input('redirect_to');

        // Only allow redirects to department login pages within /org/
        if ($redirectTo && preg_match('#^/org/[^/]+/(procurement|warehouse|quality|security|admin)/login$#', $redirectTo)) {
            // Force path-based URL using APP_URL to avoid subdomain resolution issues
            $target = rtrim(config('app.url'), '/') . $redirectTo;
        } else {
            $target = rtrim(config('app.url'), '/') . '/login';
        }

        return redirect($target)
            ->withCookie(cookie()->forget('auth_token'))
            ->with('success', 'You have been logged out successfully');
    }
}
