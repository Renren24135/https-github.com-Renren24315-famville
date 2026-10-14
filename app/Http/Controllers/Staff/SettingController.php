<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function edit(): View
    {
        $this->authorize('manage-settings');

        $settings = [
            'company_name' => Setting::get('company_name'),
            'company_address' => Setting::get('company_address'),
            'company_phone' => Setting::get('company_phone'),
            'company_email' => Setting::get('company_email'),
            'company_facebook' => Setting::get('company_facebook'),
            'opening_hours' => Setting::get('opening_hours'),
            'report_recipients' => Setting::get('report_recipients'),
            'gcash_number' => Setting::get('gcash_number'),
            'gcash_account_name' => Setting::get('gcash_account_name'),
            'gcash_qr_filename' => Setting::get('gcash_qr_filename', 'gcash-qr.jpg'),
        ];

        return view('staff.settings.edit', compact('settings'));
    }

    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $qrFile = $request->file('gcash_qr');
        unset($data['gcash_qr']);

        foreach ($data as $key => $value) {
            Setting::set($key, $value);
        }

        if ($qrFile) {
            $oldFilename = Setting::get('gcash_qr_filename', 'gcash-qr.jpg');
            $newFilename = 'gcash-qr-'.time().'.'.$qrFile->getClientOriginalExtension();

            $qrFile->move(public_path('assets/img'), $newFilename);
            Setting::set('gcash_qr_filename', $newFilename);

            // Only clean up a previously uploaded QR, never the original
            // bundled default image, in case the admin wants to fall back.
            if ($oldFilename !== 'gcash-qr.jpg' && File::exists(public_path('assets/img/'.$oldFilename))) {
                File::delete(public_path('assets/img/'.$oldFilename));
            }
        }

        return back()->with('status', 'Company info updated.');
    }
}
