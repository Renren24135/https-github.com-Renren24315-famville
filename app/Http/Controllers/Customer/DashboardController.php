<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\BilliardRateTier;
use App\Models\BilliardTable;
use App\Models\Lane;
use App\Models\Setting;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $lanes = Lane::orderedByName();
        $tables = BilliardTable::orderedByName();
        $rateTiers = BilliardRateTier::query()->where('is_active', true)->orderBy('start_time')->get();

        $contact = [
            'name' => Setting::get('company_name'),
            'address' => Setting::get('company_address'),
            'phone' => Setting::get('company_phone'),
            'email' => Setting::get('company_email'),
            'facebook' => Setting::get('company_facebook'),
            'hours' => Setting::get('opening_hours'),
        ];

        return view('customer.dashboard', compact('lanes', 'tables', 'rateTiers', 'contact'));
    }
}
