<?php

use Illuminate\Support\Facades\Route;
use NewSong\PodcastLinkFinder\Http\Controllers\PodcastSearchController;

Route::prefix('podcast-link-finder')->name('podcast-link-finder.')->group(function () {
    Route::get('/search-episodes', [PodcastSearchController::class, 'searchEpisodes'])
        ->name('search');

    Route::post('/find-links', [PodcastSearchController::class, 'findLinks'])
        ->name('find-links');

    Route::post('/search-platforms', [PodcastSearchController::class, 'searchPlatforms'])
        ->name('search-platforms');
});
