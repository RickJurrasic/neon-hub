<?php

use App\Events\SystemAlertTriggered;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\NeonHubController;
use App\Http\Controllers\ProfileController;
use App\Jobs\SendFriendRequest;
use App\Jobs\SendMessage;
use Illuminate\Support\Facades\Route;

Route::get('/', [NeonHubController::class, 'index'])->name('neon.hub');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/system/initialize-node', function () {
        SendFriendRequest::dispatch(auth()->id(), 'SENTINEL_01')
            ->delay(now()->addSeconds(4));

        return response()->json(['status' => 'NODE_INITIALIZED']);
    })->name('system.initialize');
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

Route::get('/dashboard', function () {
    return inertia('Dashboard'); // nebo co tam teď máš místo dashboardu
})->middleware(['auth', 'verified'])->name('dashboard');

Route::patch('/friendships/{id}', [FriendshipController::class, 'update'])->name('friendships.update');
Route::delete('/friendships/{id}', [FriendshipController::class, 'destroy']);

require __DIR__.'/auth.php';
