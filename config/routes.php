<?php

use Core\Router;

/** @var Router $router */

// ─── Pages HTML (SaaS) — auth gérée côté JS via localStorage ────────────────
$router->get('/login',          'PageController@login');
$router->get('/register',       'PageController@register');
$router->get('/install',        'PageController@install');
$router->get('/saas',           'PageController@saas');
$router->get('/saas/planning',  'PageController@planning');
$router->get('/saas/rooms',     'PageController@rooms');
$router->get('/saas/bookings',  'PageController@bookings');
$router->get('/saas/clients',   'PageController@clients');
$router->get('/saas/invoices',  'PageController@invoices');
$router->get('/saas/payments',  'PageController@payments');
$router->get('/saas/expenses',  'PageController@expenses');
$router->get('/saas/reports',   'PageController@reports');
$router->get('/saas/settings',  'PageController@settings');
$router->get('/saas/help',      'PageController@help');
$router->get('/saas/checkout',  'PageController@checkout');

// ─── Pages HTML (Vitrine) ─────────────────────────────────────────────────────
$router->get('/',               'PageController@home');
$router->get('/apropos',        'PageController@apropos');
$router->get('/contact',        'PageController@contact');
$router->get('/tarifs',         'PageController@pricing');
$router->get('/search',         'PageController@search');
$router->get('/property/{id}',  'PageController@property');
$router->get('/booking/{id}',   'PageController@bookingPage');

// ─── API Auth ─────────────────────────────────────────────────────────────────
$router->post('/api/auth/login',    'AuthController@login');
$router->post('/api/auth/logout',   'AuthController@logout',  ['auth']);
$router->post('/api/auth/register', 'AuthController@register');
$router->get('/api/auth/me',        'AuthController@me',      ['auth']);

// ─── API Establishments ───────────────────────────────────────────────────────
$router->get('/api/establishments',       'EstablishController@index',   ['auth']);
$router->post('/api/establishments',      'EstablishController@store',   ['auth', 'role:owner|superadmin']);
$router->get('/api/establishments/{id}',  'EstablishController@show',    ['auth']);
$router->put('/api/establishments/{id}',    'EstablishController@update',      ['auth', 'role:owner|superadmin']);
$router->post('/api/establishments/{id}/photo', 'EstablishController@uploadPhoto', ['auth', 'role:owner|superadmin']);
$router->delete('/api/establishments/{id}', 'EstablishController@destroy',    ['auth', 'role:superadmin']);

// ─── API Room Types ───────────────────────────────────────────────────────────
$router->get('/api/room-types',       'RoomController@indexTypes',  ['auth']);
$router->post('/api/room-types',      'RoomController@storeType',   ['auth', 'role:owner|superadmin']);
$router->put('/api/room-types/{id}',  'RoomController@updateType',  ['auth', 'role:owner|superadmin']);
$router->delete('/api/room-types/{id}', 'RoomController@destroyType', ['auth', 'role:owner|superadmin']);

// ─── API Rooms ────────────────────────────────────────────────────────────────
$router->get('/api/rooms',        'RoomController@index',    ['auth']);
$router->post('/api/rooms',       'RoomController@store',    ['auth', 'role:owner|superadmin']);
$router->get('/api/rooms/{id}',   'RoomController@show',     ['auth']);
$router->put('/api/rooms/{id}',   'RoomController@update',   ['auth', 'role:owner|superadmin']);
$router->delete('/api/rooms/{id}', 'RoomController@destroy', ['auth', 'role:owner|superadmin']);
$router->post('/api/rooms/{id}/photos',      'RoomController@uploadPhoto',  ['auth']);
$router->delete('/api/room-photos/{id}',     'RoomController@destroyPhoto', ['auth']);
$router->post('/api/rooms/{id}/status',      'RoomController@updateStatus', ['auth']);

// ─── API Bookings ─────────────────────────────────────────────────────────────
$router->get('/api/bookings',         'BookingController@index',    ['auth']);
$router->post('/api/bookings',        'BookingController@store',    ['auth']);
$router->get('/api/bookings/{id}',    'BookingController@show',     ['auth']);
$router->put('/api/bookings/{id}',    'BookingController@update',   ['auth']);
$router->delete('/api/bookings/{id}', 'BookingController@destroy',  ['auth']);
$router->post('/api/bookings/{id}/checkin',   'BookingController@checkIn',  ['auth']);
$router->post('/api/bookings/{id}/checkout',  'BookingController@checkOut', ['auth']);
$router->get('/api/planning',         'BookingController@planning', ['auth']);

// ─── API Clients ──────────────────────────────────────────────────────────────
$router->get('/api/clients',       'ClientController@index',   ['auth']);
$router->get('/api/clients/{id}',  'ClientController@show',    ['auth']);
$router->put('/api/clients/{id}',  'ClientController@update',  ['auth']);
$router->delete('/api/clients/{id}', 'ClientController@destroy', ['auth', 'role:owner|superadmin']);

// ─── API Invoices ─────────────────────────────────────────────────────────────
$router->get('/api/invoices',         'InvoiceController@index',    ['auth']);
$router->post('/api/invoices',        'InvoiceController@store',    ['auth']);
$router->get('/api/invoices/{id}',    'InvoiceController@show',     ['auth']);
$router->put('/api/invoices/{id}',    'InvoiceController@update',   ['auth']);
$router->get('/api/invoices/{id}/pdf', 'InvoiceController@pdf',     ['auth']);

// ─── API Payments ─────────────────────────────────────────────────────────────
$router->get('/api/payments',      'InvoiceController@payments',       ['auth']);
$router->post('/api/payments',     'InvoiceController@storePayment',   ['auth']);
$router->put('/api/payments/{id}', 'InvoiceController@updatePayment',  ['auth']);

// ─── API Expenses ─────────────────────────────────────────────────────────────
$router->get('/api/expenses',         'ExpenseController@index',    ['auth']);
$router->post('/api/expenses',        'ExpenseController@store',    ['auth']);
$router->put('/api/expenses/{id}',    'ExpenseController@update',   ['auth']);
$router->delete('/api/expenses/{id}', 'ExpenseController@destroy',  ['auth']);

// ─── API Subscriptions ────────────────────────────────────────────────────────
$router->get('/api/subscriptions/plans',          'SubscriptionController@plans');
$router->get('/api/subscriptions/status',         'SubscriptionController@status',  ['auth']);
$router->post('/api/subscriptions/initiate',      'SubscriptionController@initiate', ['auth']);
$router->post('/api/subscriptions/callback',      'SubscriptionController@callback');
$router->get('/api/subscriptions/verify/{ref}',   'SubscriptionController@verify',   ['auth']);

// ─── API Dashboard ────────────────────────────────────────────────────────────
$router->get('/api/dashboard/stats',    'DashboardController@stats',   ['auth']);
$router->get('/api/dashboard/planning', 'DashboardController@planning', ['auth']);

// ─── API Vitrine Publique (sans auth) ─────────────────────────────────────────
$router->get('/api/public/rooms/{id}',       'PublicController@room');
$router->get('/api/public/search',          'PublicController@search');
$router->get('/api/public/establishments',  'PublicController@establishments');
$router->get('/api/public/property/{id}',   'PublicController@property');
$router->get('/api/public/availability/{id}', 'PublicController@availability');
$router->post('/api/public/booking',        'PublicController@bookingRequest');
$router->get('/api/public/destinations',    'PublicController@destinations');
