<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class SettingsController extends Controller
{
    /**
     * Show the settings page.
     */
    public function index()
    {
        // We fetch the current values directly from the config,
        // which in turn reads from the .env file.
        $settings = [
            'shipping_origin_cep' => config('shipping.origin_cep'),
            'infinitepay_client_id' => config('infinitepay.client_id'),
            'infinitepay_client_secret' => config('infinitepay.client_secret'),
        ];

        return view('admin.settings.index', compact('settings'));
    }

    /**
     * Store the updated settings.
     */
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'shipping_origin_cep' => 'nullable|digits:8',
            'infinitepay_client_id' => 'nullable|string|max:255',
            'infinitepay_client_secret' => 'nullable|string|max:255',
        ]);

        // Update the .env file with the new values
        $this->updateEnvFile([
            'SHIPPING_ORIGIN_CEP' => $validatedData['shipping_origin_cep'],
            'INFINITEPAY_CLIENT_ID' => $validatedData['infinitepay_client_id'],
            'INFINITEPAY_CLIENT_SECRET' => $validatedData['infinitepay_client_secret'],
        ]);

        // Clear the config cache to ensure the new values are loaded
        Artisan::call('config:cache');

        return redirect()->route('admin.settings.index')->with('success', 'Settings updated successfully.');
    }

    /**
     * A helper function to safely update values in the .env file.
     */
    protected function updateEnvFile(array $data)
    {
        $envFilePath = base_path('.env');

        if (File::exists($envFilePath)) {
            $envFileContent = File::get($envFilePath);

            foreach ($data as $key => $value) {
                // Ensure the value is not null, default to empty string
                $value = $value ?? '';
                
                // Escape special characters for regex
                $key = addslashes($key);

                // Create a regex pattern to find the key and replace its value
                $pattern = "/^{$key}=.*/m";
                $replacement = "{$key}={$value}";

                if (preg_match($pattern, $envFileContent)) {
                    // Key exists, replace it
                    $envFileContent = preg_replace($pattern, $replacement, $envFileContent);
                } else {
                    // Key does not exist, append it
                    $envFileContent .= "\n{$replacement}";
                }
            }

            File::put($envFilePath, $envFileContent);
        }
    }
}
