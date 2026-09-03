<?php

namespace App\Http\Controllers;

use App\Models\BilliardRateTier;
use App\Models\BilliardTable;
use App\Models\Lane;
use App\Models\Setting;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $lanes = Lane::query()->orderBy('name')->get();
        $tables = BilliardTable::query()->orderBy('name')->get();
        $rateTiers = BilliardRateTier::query()->where('is_active', true)->orderBy('start_time')->get();

        $contact = [
            'name' => Setting::get('company_name'),
            'address' => Setting::get('company_address'),
            'phone' => Setting::get('company_phone'),
            'email' => Setting::get('company_email'),
            'facebook' => Setting::get('company_facebook'),
            'hours' => Setting::get('opening_hours'),
        ];

        return view('welcome', compact('lanes', 'tables', 'rateTiers', 'contact'));
    }
}
