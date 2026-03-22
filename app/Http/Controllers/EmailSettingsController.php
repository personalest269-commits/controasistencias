<?php

namespace App\Http\Controllers;

use App\Mail\TestMail;
use App\Models\EmailSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class EmailSettingsController extends Controller
{
    public $Response;

    public function __construct()
    {
        parent::__construct();
        $this->Response = new ResponseController();
    }

    public function index()
    {
        if (!Schema::hasTable('email_configuraciones')) {
            return $this->Response->prepareResult(500, [], [], null, 'view', 'errors.500', 'Missing table email_configuraciones. Please run migrations.');
        }
        $settings = EmailSetting::query()->first();
        if (!$settings) {
            $settings = EmailSetting::create([
                'mail_driver' => 'smtp',
                'mail_host' => config('mail.mailers.smtp.host'),
                'mail_port' => config('mail.mailers.smtp.port'),
                'mail_username' => config('mail.mailers.smtp.username'),
                'mail_password' => config('mail.mailers.smtp.password'),
                'mail_encryption' => config('mail.mailers.smtp.encryption'),
                'mail_from_address' => config('mail.from.address'),
                'mail_from_name' => config('mail.from.name'),
            ]);
        }

        return $this->Response->prepareResult(200, ['settings' => $settings], [], [], 'view', 'emailsettings.index');
    }

    public function update(Request $request)
    {
        if (!Schema::hasTable('email_configuraciones')) {
            return $this->Response->prepareResult(500, [], [], null, 'ajax', null, 'Missing table email_configuraciones. Please run migrations.');
        }
        $validator = Validator::make($request->all(), [
            'mail_driver' => 'required|string|max:50',
            'mail_host' => 'nullable|string|max:255',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:255',
            'mail_password' => 'nullable|string|max:2048',
            'mail_encryption' => 'nullable|string|max:20',
            'mail_from_address' => 'nullable|email|max:255',
            'mail_from_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return $this->Response->prepareResult(422, [], $validator, null, 'ajax', null, 'Validation error');
        }

        $settings = EmailSetting::query()->first() ?? new EmailSetting();
        $settings->fill($validator->validated());
        $settings->save();

        // Apply the new settings immediately for the next requests (and purge cached mailers).
        $this->applyMailConfig($settings);

        return $this->Response->prepareResult(200, ['settings' => $settings], [], 'Email settings saved successfully', 'ajax');
    }

    public function sendTest(Request $request)
    {
        if (!Schema::hasTable('email_configuraciones')) {
            return $this->Response->prepareResult(500, [], [], null, 'ajax', null, 'Missing table email_configuraciones. Please run migrations.');
        }
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return $this->Response->prepareResult(422, [], $validator, null, 'ajax', null, 'Validation error');
        }

        try {
            // Ensure the mailer uses the latest DB settings.
            $settings = EmailSetting::query()->first();
            if ($settings) {
                $this->applyMailConfig($settings);
            }
            Mail::to($request->input('email'))->send(new TestMail(config('app.name')));
            return $this->Response->prepareResult(200, [], [], 'Test email sent successfully', 'ajax');
        } catch (\Throwable $e) {
            return $this->Response->prepareResult(500, [], [], null, 'ajax', null, 'Could not send test email: ' . $e->getMessage());
        }
    }

    private function applyMailConfig(EmailSetting $settings): void
    {
        $driver = $settings->mail_driver ?: 'smtp';

        config()->set('mail.default', $driver);
        config()->set('mail.mailers.' . $driver . '.host', $settings->mail_host);
        config()->set('mail.mailers.' . $driver . '.port', $settings->mail_port);
        config()->set('mail.mailers.' . $driver . '.username', $settings->mail_username);
        config()->set('mail.mailers.' . $driver . '.password', $settings->mail_password);
        config()->set('mail.mailers.' . $driver . '.encryption', $settings->mail_encryption);

        config()->set('mail.from.address', $settings->mail_from_address);
        config()->set('mail.from.name', $settings->mail_from_name);

        // Purge cached mailers so the next send uses new config.
        if (app()->resolved('mail.manager')) {
            app('mail.manager')->forgetMailers();
        }
    }
}
