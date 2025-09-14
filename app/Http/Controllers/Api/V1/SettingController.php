<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSettingsRequest;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;

class SettingController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $settings = $user->setting ?? new Setting(['notification_prefs' => []]);

        return response()->json([
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'timezone' => $user->timezone,
                'time_format' => $user->time_format,
                'telegram_id' => $user->telegram_id,
            ],
            'settings' => [
                'notifications' => $settings->notification_prefs ?? [],
                'integrations' => [
                    'smsaero' => [
                        'email' => $settings->smsaero_email,
                        'api_key' => $settings->smsaero_api_key,
                    ],
                    'yookassa' => [
                        'shop_id' => $settings->yookassa_shop_id,
                        'secret_key' => $settings->yookassa_secret_key,
                    ],
                ],
                'work_days' => $settings->work_days,
                'work_hours' => $settings->work_hours,
                'holidays' => $user->holidays()->pluck('date')->map(fn($d) => Carbon::parse($d)->toDateString()),
                'address' => $settings->address,
                'map_point' => $settings->map_point,
            ],
        ]);
    }

    public function update(UpdateSettingsRequest $request)
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'timezone' => $data['timezone'],
            'time_format' => $data['time_format'],
        ])->save();

        if (!empty($data['new_password'])) {
            $user->password = Hash::make($data['new_password']);
            $user->save();
        }

        Setting::updateOrCreate(
            ['user_id' => $user->id],
            [
                'notification_prefs' => $data['notifications'] ?? [],
                'work_days' => $data['work_days'] ?? [],
                'work_hours' => $data['work_hours'] ?? [],
                'smsaero_email' => data_get($data, 'integrations.smsaero.email'),
                'smsaero_api_key' => data_get($data, 'integrations.smsaero.api_key'),
                'yookassa_shop_id' => data_get($data, 'integrations.yookassa.shop_id'),
                'yookassa_secret_key' => data_get($data, 'integrations.yookassa.secret_key'),
                'address' => $data['address'] ?? null,
                'map_point' => $data['map_point'] ?? null,
            ]
        );

        if (isset($data['holidays'])) {
            $user->holidays()->delete();
            foreach ($data['holidays'] as $date) {
                $user->holidays()->create(['date' => $date]);
            }
        }

        return $this->index($request);
    }
}
