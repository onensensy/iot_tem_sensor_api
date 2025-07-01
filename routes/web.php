<?php

use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Sensy\Scrud\app\Http\Helpers\Model;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('settings.profile');
    Route::get('settings/password', Password::class)->name('settings.password');
    Route::get('settings/appearance', Appearance::class)->name('settings.appearance');
});

Route::get('sms/webhook', function () {

    $req = request();

    $req->merge([
        "SmsStatus" => 'success',
        "SmsSid" => Str::random(10),
        "SmsMessageSid" => Str::random(10),
        "MessageSid" => Str::random(10),
        "From" => '256783940334',
        "Body" => $req->Body,
    ]);

    return Model::call(request(), 'Sms', 'webhook');
})->name('smsWebhook');


##FOR TESTING ONLY
Route::get('sendSms', function () {

    $req = request();
    $req->merge([
        "to" => env('SENSOR_PHONE_NO'),
        "message" => 'sensor=1;temp=25.6;type=alert;status=normal',
    ]);

    return Model::call(request(), 'Sms', 'sendMessage');
});

##FOR TESTING ONLY
Route::get('temp-overide/{temp}', function ($temp) {

    if (is_string($temp) && $temp == 'normal') {
        $c_config = l_config('m_temp');
        $c_config->k_value = l_config('d_temp')->k_value;

        $c_config->save();

        $dmy_config = l_config('dmy_temp');
        $dmy_config->k_value = 0;
        $dmy_config->save();

        return dd('TEMPERATURE HAS BEEN NORMALIZED');
    } else {
        if (!is_numeric($temp)) return dd('INVALID TEMPERATURE');

        #1. Backup default temp.
        $c_config = l_config('m_temp');

        ##is dmy
        $dmy_config = l_config('dmy_temp');
        if (!$dmy_config->k_value) {
            $d_config = l_config('d_temp');

            $d_config->k_value = $c_config->k_value;
            $d_config->save();

            $dmy_config->k_value = true;
            $dmy_config->save();

        }

        #2. Update current temp.

        $c_config->k_value = (float)$temp;
        $c_config->save();

        #3. return
        return dd("TEMP UPDATED TO: $temp");
    }

});
##FOR TESTING ONLY
require __DIR__ . '/auth.php';
