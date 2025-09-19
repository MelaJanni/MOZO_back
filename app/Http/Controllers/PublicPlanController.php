<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;

class PublicPlanController extends Controller
{
    public function index()
    {
        $plans = Plan::active()
            ->ordered()
            ->get();

        return view('public.plans.filament-index', compact('plans'));
    }

    public function show(Plan $plan)
    {
        if (!$plan->is_active) {
            abort(404);
        }

        return view('public.plans.filament-show', compact('plan'));
    }

    public function pricing()
    {
        $plans = Plan::active()
            ->ordered()
            ->get();

        $featuredPlan = $plans->where('is_featured', true)->first();
        $popularPlan = $plans->where('is_popular', true)->first();

        return view('public.plans.filament-pricing', compact('plans', 'featuredPlan', 'popularPlan'));
    }

    public function gracePeriod()
    {
        $user = auth()->user();
        $subscription = $user->subscriptions()->latest()->first();

        // Verificar si el usuario realmente necesita seleccionar un plan
        if (!$subscription || !$subscription->requires_plan_selection) {
            return redirect()->route('public.plans.index');
        }

        $availablePlans = Plan::active()->ordered()->get();

        return view('public.plans.grace-period', compact('subscription', 'availablePlans'));
    }
}