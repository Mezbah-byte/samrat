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
$route['team-bonus']            = 'team_bonus/index';
$route['team-bonus/claim/(:num)'] = 'team_bonus/claim/$1';
$route['profile']               = 'profile/index';
$route['profile/password']      = 'profile/password';
$route['profile/avatar']        = 'profile/avatar';
$route['transactions']          = 'transactions/index';
$route['notifications']         = 'notifications/index';
$route['notifications/read/(:num)'] = 'notifications/read/$1';
$route['agentship']             = 'agentship/index';
$route['agentship/apply']       = 'agentship/apply';

/* -----------------------------------------------------------------
 | Agent panel  -> application/controllers/agent/
 |
 | Explicit, because CI would otherwise resolve agent/login to a
 | controllers/agent/Login.php that does not exist.
 | ----------------------------------------------------------------- */
$route['agent']                 = 'agent/dashboard/index';
$route['agent/login']           = 'agent/auth/login';
$route['agent/logout']          = 'agent/auth/logout';

/* -----------------------------------------------------------------
 | Admin
 | ----------------------------------------------------------------- */
$route['admin']                 = 'admin/dashboard/index';
$route['admin/login']           = 'admin/auth/login';
$route['admin/logout']          = 'admin/auth/logout';

/* Impersonation. Starting is admin-side; stopping is not, because the request
 | comes from inside the user or agent panel. */
$route['admin/impersonate/user/(:num)']  = 'admin/impersonate/user/$1';
$route['admin/impersonate/agent/(:num)'] = 'admin/impersonate/agent/$1';
$route['impersonate/stop']               = 'impersonate/stop';
$route['admin/deposit-methods/(:any)/(:num)'] = 'admin/deposit_methods/$1/$2';
$route['admin/deposit-methods/(:any)']       = 'admin/deposit_methods/$1';
$route['admin/deposit-methods']              = 'admin/deposit_methods/index';
$route['admin/referral-levels/(:any)/(:num)'] = 'admin/referral_levels/$1/$2';
$route['admin/referral-levels/(:any)']       = 'admin/referral_levels/$1';
$route['admin/referral-levels']              = 'admin/referral_levels/index';
$route['admin/team-bonus/(:any)/(:num)']     = 'admin/team_bonus/$1/$2';
$route['admin/team-bonus/(:any)']            = 'admin/team_bonus/$1';
$route['admin/team-bonus']                   = 'admin/team_bonus/index';
$route['admin/agent-applications/(:any)/(:num)/(:any)'] = 'admin/agent_applications/$1/$2/$3';
$route['admin/agent-applications/(:any)/(:num)']        = 'admin/agent_applications/$1/$2';
$route['admin/agent-applications/(:any)']               = 'admin/agent_applications/$1';
$route['admin/agent-applications']                      = 'admin/agent_applications/index';

/* -----------------------------------------------------------------
 | API v1  -> application/controllers/api/V1.php
 | ----------------------------------------------------------------- */
$route['api/v1/ads/watch']       = 'api/v1/ads_watch';
$route['api/v1/deposit/methods'] = 'api/v1/deposit_methods';
$route['api/v1/team-bonus']      = 'api/v1/team_bonus';
$route['api/v1/(:any)/(:num)']   = 'api/v1/$1/$2';
$route['api/v1/(:any)']          = 'api/v1/$1';

/* -----------------------------------------------------------------
 | Cron
 | ----------------------------------------------------------------- */
$route['cron/run']               = 'cron/run';
