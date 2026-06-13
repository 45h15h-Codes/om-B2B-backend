<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

// Bootstrap request context first
$request = Illuminate\Http\Request::create('/jewelery', 'POST', [
    'sku' => 'JW-SIM-999',
    'name' => 'Simulated Jewelry Ring',
    'type' => 'Ring',
    'price' => '2999',
    'location' => 'London',
]);

// Bind request to container before bootstrap
$app->instance('request', $request);

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

// Start session and generate/retrieve CSRF token
$session = $app['session']->driver();
$session->start();
$request->setLaravelSession($session);
$token = $session->token();
$request->merge(['_token' => $token]);

// Authenticate as User ID 1 (OM Normal Admin)
$user = App\Models\User::find(1);
Auth::login($user);
session(['admin_role' => 'normal_admin']);

// Create a fake file and set it in the request
use Illuminate\Http\UploadedFile;
$file = UploadedFile::fake()->create('test_jewelry.jpg', 100, 'image/jpeg');
$request->files->set('images', [$file]);

// Handle request
try {
    $response = $kernel->handle($request);
    echo "Response Status: " . $response->getStatusCode() . "\n";
    if ($response->isRedirection()) {
        echo "Redirect URL: " . $response->headers->get('Location') . "\n";
        $session = $request->getSession();
        if ($session && $session->has('errors')) {
            echo "Errors in session:\n";
            print_r($session->get('errors')->getBag('default')->messages());
        }
    } else {
        echo "Response Content:\n" . substr($response->getContent(), 0, 1000) . "\n";
    }
} catch (\Throwable $e) {
    echo "Exception occurred: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
