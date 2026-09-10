<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectorController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('/', function () {
        $user = auth()->user();

        if ($user && ($user->email === 'steeve@gmail.com' || (isset($user->role) && $user->role === 'directeur'))) {
            return redirect()->route('director.dashboard');
        }

        return app(DashboardController::class)->index();
    })->name('dashboard');
});

Route::middleware(['auth', 'redirect.auditeur'])->group(function () {
    Route::get('/rapports', [ReportController::class, 'index'])->name('reports.index');

    Route::get('/comptes', [AccountController::class, 'index'])->name('accounts.index');
    Route::post('/comptes', [AccountController::class, 'store'])->name('accounts.store');
    Route::get('/comptes/{account}', [AccountController::class, 'show'])->name('accounts.show');
    Route::post('/comptes/{account}/bloquer', [AccountController::class, 'bloquer'])->name('accounts.bloquer');
    Route::post('/comptes/{account}/debloquer', [AccountController::class, 'debloquer'])->name('accounts.debloquer');
    Route::get('/comptes/{account}/transactions', [TransactionController::class, 'index'])->name('accounts.transactions');
    Route::post('/comptes/{account}/depot', [TransactionController::class, 'deposer'])->name('accounts.depot');
    Route::post('/comptes/{account}/retrait', [TransactionController::class, 'retirer'])->name('accounts.retrait');

    Route::get('/membres', [MemberController::class, 'index'])->name('members.index');
    Route::get('/membres/creer', [MemberController::class, 'create'])->name('members.create');
    Route::post('/membres', [MemberController::class, 'store'])->name('members.store');
    Route::get('/membres/{member}/editer', [MemberController::class, 'edit'])->name('members.edit');
    Route::put('/membres/{member}', [MemberController::class, 'update'])->name('members.update');
    Route::delete('/membres/{member}', [MemberController::class, 'destroy'])->name('members.destroy');

    Route::get('/prets', [LoanController::class, 'index'])->name('loans.index');
    Route::get('/prets/creer', [LoanController::class, 'create'])->name('loans.create');
    Route::post('/prets', [LoanController::class, 'store'])->name('loans.store');
    Route::get('/prets/{loan}', [LoanController::class, 'show'])->name('loans.show');
    Route::post('/prets/{loan}/approuver', [LoanController::class, 'approuver'])->name('loans.approuver');
    Route::post('/prets/{loan}/rejeter', [LoanController::class, 'rejeter'])->name('loans.rejeter');
    Route::post('/prets/{loan}/decaisser', [LoanController::class, 'decaisser'])->name('loans.decaisser');
    Route::post('/prets/{loan}/echeances/{echeance}/rembourser', [LoanController::class, 'rembourser'])->name('loans.rembourser');
});

Route::middleware(['auth', 'ensure.auditeur'])->prefix('direction')->name('director.')->group(function () {
    Route::get('/', [DirectorController::class, 'dashboard'])->name('dashboard');
    Route::get('/rapports', [DirectorController::class, 'rapports'])->name('rapports');
    Route::get('/historique', [DirectorController::class, 'historique'])->name('historique');
    Route::get('/parametres', [DirectorController::class, 'parametres'])->name('parametres');
    Route::post('/parametres/mot-de-passe', [DirectorController::class, 'changerMotDePasse'])->name('motdepasse');
    Route::post('/purger-donnees', [DirectorController::class, 'purgerDonnees'])
        ->middleware('throttle:3,1')
        ->name('purger-donnees');
    Route::get('/utilisateurs', [DirectorController::class, 'utilisateurs'])->name('utilisateurs');
    Route::post('/utilisateurs/{utilisateur}/mot-de-passe', [DirectorController::class, 'reinitialiserMotDePasse'])->name('utilisateurs.motdepasse');
});
