<?php

namespace App\Http\Controllers;

use App\Models\Control\SubscriptionPlan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    /**
     * Show the landing page with subscription plans
     * GET /
     */
    public function landing(): View
    {
        $plans = SubscriptionPlan::active()
            ->public()
            ->orderBy('price_amount', 'asc')
            ->get();
        
        return view('landing', [
            'plans' => $plans
        ]);
    }
    
    /**
     * Show the pricing/subscription selection page
     * GET /pricing
     */
    public function pricing(): View
    {
        $plans = SubscriptionPlan::active()
            ->public()
            ->orderBy('price_amount', 'asc')
            ->get();
        
        return view('subscription.select', [
            'plans' => $plans
        ]);
    }
    
    /**
     * Show the registration page with optional selected plan
     * GET /register
     */
    public function register(Request $request): View
    {
        $selectedPlanCode = $request->query('plan');
        $selectedPlan = null;
        
        if ($selectedPlanCode) {
            $selectedPlan = SubscriptionPlan::where('plan_code', $selectedPlanCode)
                ->active()
                ->public()
                ->first();
        }
        
        return view('auth.register', [
            'selectedPlan' => $selectedPlan
        ]);
    }
    
    /**
     * Show the login page
     * GET /login
     */
    public function login(): View
    {
        return view('auth.login');
    }

    /**
     * Show specialized logins
     */
    public function loginProcurement(): View
    {
        return view('auth.login-procurement');
    }

    public function loginWarehouse(): View
    {
        return view('auth.login-warehouse');
    }

    public function loginQuality(): View
    {
        return view('auth.login-quality');
    }

    public function loginAdmin(): View
    {
        return view('auth.login-admin');
    }
}
