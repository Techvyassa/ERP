<?php

use Illuminate\Support\Facades\Route;

it('applies only store module permission to the mir approve route', function () {
    $route = Route::getRoutes()->match(
        Illuminate\Http\Request::create('/api/v1/material-issue-requests/12/approve', 'POST')
    );

    $middleware = $route->gatherMiddleware();

    expect($middleware)->toContain('check.module.permission:STORE');
    expect($middleware)->not->toContain('check.module.permission:PRODUCTION');
});
