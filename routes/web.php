<?php

Route::get('/', 'BitbucketWebhookController@index');

Route::post('/webhooks/bitbucket', 'BitbucketWebhookController@receive');

Route::get('/config/parse/servers', 'ConfigController@parseServers')->middleware(['auth'])->name('config.servers');
Route::get('/config/parse/tasks', 'ConfigController@parseTasks')->middleware(['auth'])->name('config.tasks');

Route::get('/tasks/get', 'TaskController@get')->middleware(['auth'])->name('tasks.get');
Route::post('/tasks/execute', 'TaskController@execute')->middleware(['auth'])->name('tasks.execute');

Route::get('/phpinfo', function () {
    phpinfo();
})->middleware('auth');

Auth::routes();

Route::get('/{view?}', 'HomeController@index')->where('view', '(.*)')->name('telescope')->middleware('auth');
