<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\MonthlyRevenueArchive;
use Illuminate\View\View;

class MonthlyArchiveController extends Controller
{
    public function index(): View
    {
        $archives = MonthlyRevenueArchive::query()
            ->latest('month')
            ->paginate(24);

        return view('staff.monthly-archive.index', compact('archives'));
    }
}
