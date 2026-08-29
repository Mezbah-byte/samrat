<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$route['default_controller'] = 'home';
$route['404_override']       = '';
$route['translate_uri_dashes'] = FALSE;

/* -----------------------------------------------------------------
 | Public
 | ----------------------------------------------------------------- */
$route['about']                 = 'home/about';
$route['plans']                 = 'home/plans';
$route['notices']               = 'home/notices';
$route['notices/(:any)']        = 'home/notice/$1';

/* -----------------------------------------------------------------
 | User auth
 | ----------------------------------------------------------------- */
$route['login']                 = 'auth/login';
$route['register']              = 'auth/register';
$route['register/(:any)']       = 'auth/register/$1';
$route['logout']                = 'auth/logout';
$route['forgot-password']       = 'auth/forgot_password';
$route['reset-password/(:any)'] = 'auth/reset_password/$1';

/* -----------------------------------------------------------------
 | User panel
 | ----------------------------------------------------------------- */
$route['dashboard']             = 'dashboard/index';
$route['packages']              = 'packages/index';
$route['packages/buy/(:num)']   = 'packages/buy/$1';
$route['deposit']               = 'deposit/index';
$route['deposit/create/(:num)'] = 'deposit/create/$1';
$route['deposit/history']       = 'deposit/history';
$route['ads']                   = 'ads/index';
$route['ads/watch/(:num)']      = 'ads/watch/$1';
$route['ads/complete/(:num)']   = 'ads/complete/$1';
$route['withdraw']              = 'withdraw/index';
$route['withdraw/history']      = 'withdraw/history';
$route['referral']              = 'referral/index';
$route['profile']               = 'profile/index';
$route['profile/password']      = 'profile/password';
$route['profile/avatar']        = 'profile/avatar';
$route['transactions']          = 'transactions/index';
$route['notifications']         = 'notifications/index';
$route['notifications/read/(:num)'] = 'notifications/read/$1';

/* -----------------------------------------------------------------
 | Admin
 | ----------------------------------------------------------------- */
$route['admin']                 = 'admin/dashboard/index';
$route['admin/login']           = 'admin/auth/login';
$route['admin/logout']          = 'admin/auth/logout';
$route['admin/deposit-methods/(:any)/(:num)'] = 'admin/deposit_methods/$1/$2';
$route['admin/deposit-methods/(:any)']       = 'admin/deposit_methods/$1';
$route['admin/deposit-methods']              = 'admin/deposit_methods/index';

/* -----------------------------------------------------------------
 | API v1  -> application/controllers/api/V1.php
 | ----------------------------------------------------------------- */
$route['api/v1/ads/watch']       = 'api/v1/ads_watch';
$route['api/v1/deposit/methods'] = 'api/v1/deposit_methods';
$route['api/v1/(:any)/(:num)']   = 'api/v1/$1/$2';
$route['api/v1/(:any)']          = 'api/v1/$1';

/* -----------------------------------------------------------------
 | Cron
 | ----------------------------------------------------------------- */
$route['cron/run']               = 'cron/run';
