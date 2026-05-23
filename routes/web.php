<?php

use App\Events\FriendRequestReceived;
use App\Events\MessageReceived;
use App\Events\SystemAlertTriggered;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

 // 🚨 TENTO ŘÁDEK TADY MUSÍ BÝT!

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/test-signal', function () {
    // Teď posíláme ID uživatele, aby Event věděl, kam broadcastovat
    event(new SystemAlertTriggered(auth()->id(), 'Krizový stav aktivován!'));

    return 'Signál odpálen!';
});

Route::get('/test-message', function () {
    event(new MessageReceived(auth()->id(), [
        'sender' => 'CYBER_OPERATOR',
        'text' => 'Připojení k uzlu navázáno.',
    ]));

    return 'Zpráva odeslána přes event!';
});

Route::get('/test-friend', function () {
    event(new FriendRequestReceived(auth()->id(), [
        'id' => 999, // Unikátní ID uživatele, co žádá
        'name' => 'CYBER_UNIT_01',
    ]));

    return 'Žádost odeslána!';
});

require __DIR__.'/auth.php';
