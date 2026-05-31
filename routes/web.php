<?php

use App\Events\SystemAlertTriggered;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\ProfileController;
use App\Jobs\SendFriendRequest;
use App\Jobs\SendMessage;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

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
    SendMessage::dispatch(auth()->id(), [
        'sender' => 'CYBER_OPERATOR',
        'text' => 'Připojení k uzlu navázáno.',
    ])->delay(now()->addSeconds(1)); // Zpoždění 5 sekund

    return 'Zpráva naplánována za 5 sekund.';
});

Route::get('/test-friend', function () {
    // Dispatchne job, který se po 3 sekundách automaticky provede
    SendFriendRequest::dispatch(auth()->id(), 999)
        ->delay(now()->addSeconds(3));

    return 'Žádost o přátelství byla naplánována za 3 sekundy.';
});

Route::get('/dashboard', function () {
    return inertia('Dashboard'); // nebo co tam teď máš místo dashboardu
})->middleware(['auth', 'verified'])->name('dashboard');

Route::patch('/friendships/{id}', [FriendshipController::class, 'update'])->name('friendships.update');
Route::delete('/friendships/{id}', [FriendshipController::class, 'destroy']);

require __DIR__.'/auth.php';
