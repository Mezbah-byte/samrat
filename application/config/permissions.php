<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Admin capability catalogue.
 *
 * A permission key is `module.action`. The grants that actually decide access
 * live per account in `admin_permissions`; the role only picks the checkboxes
 * a new account starts with. `super_admin` bypasses the whole thing and is
 * therefore never stored.
 *
 * The catalogue is also the whitelist: Admin_permission_model::sync() drops
 * anything posted that is not listed here, so a forged form field cannot mint
 * a grant.
 */

$config['admin_permissions'] = array(

	'deposits' => array(
		'label' => 'Deposits',
		'perms' => array(
			'deposits.view'    => 'View deposits',
			'deposits.approve' => 'Approve deposit',
			'deposits.reject'  => 'Reject deposit',
		),
	),

	'withdrawals' => array(
		'label' => 'Withdrawals',
		'perms' => array(
			'withdrawals.view'      => 'View withdrawals',
			'withdrawals.approve'   => 'Approve withdrawal',
			'withdrawals.mark_paid' => 'Mark as paid',
			'withdrawals.reject'    => 'Reject withdrawal',
		),
	),

	'investments' => array(
		'label' => 'Investments',
		'perms' => array(
			'investments.view'   => 'View investments',
			'investments.cancel' => 'Cancel investment',
		),
	),

	'transactions' => array(
		'label' => 'Transactions',
		'perms' => array(
			'transactions.view' => 'View transactions',
		),
	),

	'referrals' => array(
		'label' => 'Referrals',
		'perms' => array(
			'referrals.view' => 'View referral tree',
		),
	),

	'referral_levels' => array(
		'label' => 'Referral Levels',
		'perms' => array(
			'referral_levels.view'   => 'View levels',
			'referral_levels.manage' => 'Add / edit / delete levels',
		),
	),

	'team_bonus' => array(
		'label' => 'Team Bonus',
		'perms' => array(
			'team_bonus.view'      => 'View tiers',
			'team_bonus.manage'    => 'Add / edit / delete tiers',
			'team_bonus.recompute' => 'Recompute bonuses',
		),
	),

	'users' => array(
		'label' => 'Users',
		'perms' => array(
			'users.view'        => 'View users',
			'users.edit'        => 'Edit user profile',
			'users.adjust'      => 'Adjust balance',
			'users.status'      => 'Block / unblock user',
			'users.impersonate' => 'Log in as user or agent',
		),
	),

	'agent_applications' => array(
		'label' => 'Agentship Applications',
		'perms' => array(
			'agent_applications.view'    => 'View applications',
			'agent_applications.approve' => 'Approve application',
			'agent_applications.reject'  => 'Reject application',
			'agent_applications.nid'     => 'View applicant NID documents',
		),
	),

	'packages' => array(
		'label' => 'Packages',
		'perms' => array(
			'packages.view'   => 'View packages',
			'packages.manage' => 'Add / edit / delete packages',
		),
	),

	'deposit_methods' => array(
		'label' => 'Wallets',
		'perms' => array(
			'deposit_methods.view'   => 'View wallets',
			'deposit_methods.manage' => 'Add / edit / delete wallets',
		),
	),

	'ads' => array(
		'label' => 'Ads',
		'perms' => array(
			'ads.view'   => 'View ads',
			'ads.manage' => 'Add / edit / delete ads',
		),
	),

	'notices' => array(
		'label' => 'Notices',
		'perms' => array(
			'notices.view'   => 'View notices',
			'notices.manage' => 'Add / edit / delete notices',
		),
	),

	'notifications' => array(
		'label' => 'Notifications',
		'perms' => array(
			'notifications.view' => 'View sent notifications',
			'notifications.send' => 'Send / broadcast notification',
		),
	),

	'support_links' => array(
		'label' => 'Support Links',
		'perms' => array(
			'support_links.view'   => 'View channels',
			'support_links.manage' => 'Add / edit / delete channels',
		),
	),

	'settings' => array(
		'label' => 'Settings',
		'perms' => array(
			'settings.view'        => 'View settings',
			'settings.manage'      => 'Save settings',
			'settings.cron_secret' => 'Regenerate cron secret',
		),
	),

	'admins' => array(
		'label' => 'Admin Accounts',
		'perms' => array(
			'admins.view'   => 'View admin accounts',
			'admins.manage' => 'Create / edit / delete admins',
		),
	),

	'agents' => array(
		'label' => 'Agents',
		'perms' => array(
			'agents.view'   => 'View agents',
			'agents.manage' => 'Create / edit / delete agents',
			'agents.nid'    => 'View agent NID documents',
		),
	),

	'logs' => array(
		'label' => 'Activity Log',
		'perms' => array(
			'logs.view' => 'View activity log',
		),
	),

	// Reading a colleague's mailbox is a heavier act than editing a package,
	// so the capability is split four ways rather than the usual view/manage
	// pair: knowing a mailbox exists, editing its credentials, opening it, and
	// sending as it are each grantable on their own. `mail.domains` carries
	// the endpoint and is withheld from the `admin` preset.
	'mail' => array(
		'label' => 'Mail',
		'perms' => array(
			'mail.view'    => 'View mailbox list',
			'mail.manage'  => 'Add / edit / remove mailbox credentials',
			'mail.domains' => 'Manage mail domains and server endpoints',
			'mail.access'  => 'Open a mailbox and read its mail',
			'mail.send'    => 'Send mail from a mailbox',
		),
	),
);

/**
 * Starting checkboxes per role. `super_admin` is the '*' sentinel: it holds
 * everything by bypass and stores no rows at all.
 */
$config['admin_role_presets'] = array(

	'super_admin' => '*',

	'admin' => array(
		'deposits.view', 'deposits.approve', 'deposits.reject',
		'withdrawals.view', 'withdrawals.approve', 'withdrawals.mark_paid', 'withdrawals.reject',
		'investments.view', 'investments.cancel',
		'transactions.view',
		'referrals.view',
		'referral_levels.view', 'referral_levels.manage',
		'team_bonus.view', 'team_bonus.manage', 'team_bonus.recompute',
		'users.view', 'users.edit', 'users.adjust', 'users.status', 'users.impersonate',
		// Not `approve`: approving hands off to Agents::create(), which needs
		// `agents.manage`. Granting one without the other only dead-ends.
		'agent_applications.view', 'agent_applications.reject', 'agent_applications.nid',
		'packages.view', 'packages.manage',
		'deposit_methods.view', 'deposit_methods.manage',
		'ads.view', 'ads.manage',
		'notices.view', 'notices.manage',
		'notifications.view', 'notifications.send',
		'support_links.view', 'support_links.manage',
		'settings.view', 'settings.manage',
		'logs.view',
		// Not `mail.domains`: the endpoint and its credentials are an
		// infrastructure setting, and an admin who can point a domain at
		// another server could harvest every password typed after that.
		'mail.view', 'mail.manage', 'mail.access', 'mail.send',
	),

	// View-only. No approvals, no edits, no settings, no admin/agent screens.
	'moderator' => array(
		'deposits.view',
		'withdrawals.view',
		'investments.view',
		'transactions.view',
		'referrals.view',
		'referral_levels.view',
		'team_bonus.view',
		'users.view',
		'agent_applications.view',
		'packages.view',
		'deposit_methods.view',
		'ads.view',
		'notices.view',
		'notifications.view',
		'support_links.view',
		'logs.view',
		// The list only. No `mail.access`: a view-only role has no business
		// reading mail.
		'mail.view',
	),
);
