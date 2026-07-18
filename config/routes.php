<?php

use Core\Router;

/** @var Router $router */

// ─── Pages HTML (SaaS) — auth gérée côté JS via localStorage ────────────────
$router->get('/login',          'PageController@login');
$router->get('/register',       'PageController@register');
$router->get('/forgot-password', 'PageController@forgotPassword');
$router->get('/reset-password',  'PageController@resetPassword');
$router->get('/install',        'PageController@install');
$router->get('/saas',           'PageController@saas');
$router->get('/saas/planning',  'PageController@planning');
$router->get('/saas/rooms',     'PageController@rooms');
$router->get('/saas/bookings',  'PageController@bookings');
$router->get('/saas/clients',   'PageController@clients');
$router->get('/saas/invoices',  'PageController@invoices');
$router->get('/saas/payments',  'PageController@payments');
$router->get('/saas/expenses',  'PageController@expenses');
$router->get('/saas/payouts',   'PageController@payouts');
$router->get('/saas/reports',   'PageController@reports');
$router->get('/saas/settings',  'PageController@settings');
$router->get('/saas/help',      'PageController@help');
$router->get('/docs',           'PageController@docs');
$router->get('/saas/checkout',  'PageController@checkout');

// ─── Pages HTML (Admin plateforme) — réservées au superadmin, garde de rôle côté JS ──
$router->get('/admin',                'AdminPageController@dashboard');
$router->get('/admin/establishments', 'AdminPageController@establishments');
$router->get('/admin/owners',         'AdminPageController@owners');
$router->get('/admin/payouts',        'AdminPageController@payouts');

// ─── Pages HTML (Vitrine) ─────────────────────────────────────────────────────
$router->get('/',               'PageController@home');
$router->get('/apropos',        'PageController@apropos');
$router->get('/contact',        'PageController@contact');
$router->get('/tarifs',         'PageController@pricing');
$router->get('/cgu',            'PageController@terms');
$router->get('/confidentialite', 'PageController@privacy');
$router->get('/search',         'PageController@search');
$router->get('/property/{slug}', 'PageController@property');
$router->get('/booking/{slug}', 'PageController@bookingPage');

// ─── API Auth ─────────────────────────────────────────────────────────────────
$router->post('/api/auth/webauthn/register-options', 'AuthController@webauthnRegisterOptions');
$router->post('/api/auth/webauthn/register-verify',  'AuthController@webauthnRegisterVerify');
$router->post('/api/auth/login',    'AuthController@login');
$router->post('/api/auth/logout',   'AuthController@logout',  ['auth']);
$router->post('/api/auth/register', 'AuthController@register');
$router->get('/api/auth/me',        'AuthController@me',      ['auth']);
$router->post('/api/auth/forgot-password', 'AuthController@forgotPassword');
$router->post('/api/auth/reset-password',  'AuthController@resetPassword');

// ─── API Establishments ───────────────────────────────────────────────────────
$router->get('/api/establishments',       'EstablishController@index',   ['auth']);
$router->post('/api/establishments',      'EstablishController@store',   ['auth', 'role:owner|superadmin']);
$router->get('/api/establishments/{id}',  'EstablishController@show',    ['auth']);
$router->put('/api/establishments/{id}',    'EstablishController@update',      ['auth', 'role:owner|superadmin']);
$router->post('/api/establishments/{id}/photo', 'EstablishController@uploadPhoto', ['auth', 'role:owner|superadmin']);
$router->delete('/api/establishment-photos/{id}', 'EstablishController@destroyPhoto', ['auth', 'role:owner|superadmin']);
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

// ─── API Équipe (membres — rôle receptionist) ───────────────────────────────────
$router->get('/api/team',         'TeamController@index',   ['auth', 'role:owner|superadmin']);
$router->post('/api/team',        'TeamController@store',   ['auth', 'role:owner|superadmin']);
$router->put('/api/team/{id}',    'TeamController@update',  ['auth', 'role:owner|superadmin']);
$router->delete('/api/team/{id}', 'TeamController@destroy', ['auth', 'role:owner|superadmin']);

// ─── API Invoices ─────────────────────────────────────────────────────────────
// Gestion de la Comptabilité (Factures) — réservée owner/superadmin, cf. ACCES_PROFILS_ABONNEMENT.md.
$router->get('/api/invoices',         'InvoiceController@index',    ['auth', 'role:owner|superadmin']);
$router->post('/api/invoices',        'InvoiceController@store',    ['auth', 'role:owner|superadmin']);
$router->get('/api/invoices/{id}',    'InvoiceController@show',     ['auth', 'role:owner|superadmin']);
$router->put('/api/invoices/{id}',    'InvoiceController@update',   ['auth', 'role:owner|superadmin']);
$router->get('/api/invoices/{id}/pdf',   'InvoiceController@pdf',        ['auth', 'role:owner|superadmin']);
$router->post('/api/invoices/{id}/send', 'InvoiceController@sendByMail', ['auth', 'role:owner|superadmin']);

// ─── API Payments ─────────────────────────────────────────────────────────────
// L'encaissement (POST) reste ouvert à tous les rôles : action opérationnelle de base
// (front-desk), indépendante de la Comptabilité — cf. ACCES_PROFILS_ABONNEMENT.md.
$router->get('/api/payments',      'InvoiceController@payments',       ['auth', 'role:owner|superadmin']);
$router->post('/api/payments',     'InvoiceController@storePayment',   ['auth']);
$router->put('/api/payments/{id}', 'InvoiceController@updatePayment',  ['auth', 'role:owner|superadmin']);

// ─── API Expenses ─────────────────────────────────────────────────────────────
$router->get('/api/expenses',         'ExpenseController@index',    ['auth', 'role:owner|superadmin']);
$router->post('/api/expenses',        'ExpenseController@store',    ['auth', 'role:owner|superadmin']);
$router->put('/api/expenses/{id}',    'ExpenseController@update',   ['auth', 'role:owner|superadmin']);
$router->delete('/api/expenses/{id}', 'ExpenseController@destroy',  ['auth', 'role:owner|superadmin']);

// ─── API Retraits (paiements en ligne) ─────────────────────────────────────────
$router->get('/api/payouts/balance',      'PayoutController@balance',  ['auth', 'role:owner|superadmin']);
$router->get('/api/payouts',              'PayoutController@index',    ['auth', 'role:owner|superadmin']);
$router->post('/api/payouts',             'PayoutController@store',    ['auth', 'role:owner|superadmin']);
$router->post('/api/payouts/{id}/pay',    'PayoutController@markPaid', ['auth', 'role:superadmin']);
$router->post('/api/payouts/{id}/reject', 'PayoutController@reject',   ['auth', 'role:superadmin']);

// ─── API Admin plateforme (superadmin uniquement) ──────────────────────────────
$router->get('/api/admin/overview', 'AdminController@overview', ['auth', 'role:superadmin']);
$router->get('/api/admin/owners',   'AdminController@owners',   ['auth', 'role:superadmin']);

// ─── API Reports ──────────────────────────────────────────────────────────────
$router->get('/api/reports/summary', 'ReportController@summary', ['auth', 'role:owner|superadmin']);
$router->get('/api/reports/compare', 'ReportController@compare', ['auth', 'role:owner|superadmin']);
$router->get('/api/reports/pdf',     'ReportController@pdf',     ['auth', 'role:owner|superadmin']);

// ─── API Subscriptions ────────────────────────────────────────────────────────
$router->get('/api/subscriptions/plans',          'SubscriptionController@plans');
$router->get('/api/subscriptions/status',         'SubscriptionController@status',  ['auth']);
$router->post('/api/subscriptions/initiate',      'SubscriptionController@initiate', ['auth', 'role:owner|superadmin']);
$router->post('/api/subscriptions/callback',      'SubscriptionController@callback');
$router->get('/api/subscriptions/verify/{ref}',   'SubscriptionController@verify',   ['auth']);
$router->get('/api/subscriptions/history',        'SubscriptionController@history',  ['auth']);
$router->post('/api/subscriptions/cancel',        'SubscriptionController@cancel',   ['auth', 'role:owner|superadmin']);
$router->post('/api/subscriptions/downgrade',     'SubscriptionController@downgrade', ['auth', 'role:owner|superadmin']);

// ─── API Notifications ────────────────────────────────────────────────────────
$router->get('/api/notifications',             'NotificationController@index',       ['auth']);
$router->get('/api/notifications/count',       'NotificationController@count',       ['auth']);
$router->post('/api/notifications/read-all',   'NotificationController@markAllRead', ['auth']);
$router->put('/api/notifications/{id}/read',   'NotificationController@markRead',    ['auth']);
$router->get('/api/notifications/preferences', 'NotificationController@preferences',       ['auth']);
$router->put('/api/notifications/preferences', 'NotificationController@updatePreferences', ['auth']);

// ─── API Push (notifications hors application) ──────────────────────────────
$router->get('/api/push/public-key',   'PushController@publicKey',  ['auth']);
$router->post('/api/push/subscribe',   'PushController@subscribe',  ['auth']);
$router->post('/api/push/unsubscribe', 'PushController@unsubscribe', ['auth']);

// ─── API Dashboard ────────────────────────────────────────────────────────────
$router->get('/api/dashboard/stats',    'DashboardController@stats',   ['auth']);
$router->get('/api/dashboard/planning', 'DashboardController@planning', ['auth']);

// ─── API Vitrine Publique (sans auth) ─────────────────────────────────────────
$router->get('/api/public/rooms/{id}',       'PublicController@room');
$router->get('/api/public/search',          'PublicController@search');
$router->get('/api/public/establishments',  'PublicController@establishments');
$router->get('/api/public/property/{id}',   'PublicController@property');
$router->get('/api/public/availability/{id}', 'PublicController@availability');
$router->post('/api/public/booking',                        'PublicController@bookingRequest');
$router->post('/api/public/booking-payment/initiate',       'BookingPaymentController@initiate');
$router->post('/api/public/booking-payment/callback',       'BookingPaymentController@callback');
$router->get('/api/public/booking-payment/verify/{ref}',    'BookingPaymentController@verify');
$router->get('/api/public/destinations',    'PublicController@destinations');
$router->post('/api/public/contact',        'PublicController@sendContact');
$router->get('/api/public/push/public-key', 'PushController@publicKey');
$router->post('/api/public/push/subscribe', 'PushController@subscribeGuest');
