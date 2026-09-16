# Agent float system

Turns the agent from a reviewer into a money mover. An agent buys balance from
the platform, spends it settling user deposits, pays user withdrawals out of
their own pocket, and cashes the proceeds back out.

The old agent panel is untouched and still runs beside this: team review
screens, the recommendation columns, and the team-based commission accrual
(`agent_deposit_percent`, `agent_profit_percent`) all behave exactly as before.

## The three wallets

| Wallet | Filled by | Drained by |
|---|---|---|
| `deposit_balance` | buying float from the admin, at par | settling a user deposit |
| `withdraw_balance` | paying a user's withdrawal out of pocket | cashing out from the admin |
| `commission_balance` | commission on each deposit settled and withdrawal paid | cashing out, or transferring into float |

Only `Agent_wallet_lib` may write these columns. Every movement writes a row
into `agent_ledger` in the same transaction, so `SUM(agent_ledger.amount)` per
`(agent_id, wallet)` always equals the stored column — which is what
`reconcile()` checks, and what the admin wallet screen shows per agent.

`commission -> deposit` is the only transfer between an agent's own wallets.
Float must be bought from the admin and collected withdrawals must be cashed
out, or the platform's liability and the agent's float would drift apart.

## The flows

**Float purchase.** Agent sends USDT to a company wallet, records the hash at
`agent/float/create`. An admin verifies it at `admin/agent-float` and approves;
`deposit_balance` goes up by the exact amount. Sold at par — the agent's
earnings come from the commission percentages, never a purchase discount.

**Deposit.** User picks a package, picks an agent, sends money to that agent's
own address and submits the hash. The deposit row carries `agent_id`,
`agent_wallet_id` and `agent_status = 'pending'`; nothing is credited. The
agent confirms at `agent/requests/deposit/<id>`, and in one transaction their
float pays for the plan, the user is credited, the investment opens, the
referral chain and team bonus pay out, and the agent's commission lands in
their commission wallet. There is no admin step after that.

**Withdrawal.** User requests a withdrawal and picks an agent. The user's
balance is held immediately, exactly as on the admin route. The agent sends the
net amount from their own pocket, then confirms with the hash at
`agent/requests/withdrawal/<id>`: `withdraw_balance` goes up by the net amount
and their commission is booked. The platform's withdrawal fee stays the
platform's.

**Cash-out.** Agent requests from the withdraw or commission wallet; the amount
is held on submit. An admin approves, sends, and marks it paid at
`admin/agent-payouts`. Rejecting returns the gross to the wallet it came from.

## Escalation

An agent who does not answer is not a dead end. `agent_accept_timeout_hours`
(default 6) after the request was made, the sweep marks it `expired` and it
lands in the ordinary admin queue. Declining does the same thing immediately,
with a note. Neither moves a balance — an unaccepted deposit never spent float,
and an unpaid withdrawal never credited anything.

On an expired or declined deposit the user has already sent money to the
agent's address, so the admin screen warns before approving: approving there
credits the user from the platform, not from the agent's float.

Schedule the sweep separately from the nightly job, because the window is
measured in hours:

```
*/15 * * * *  curl -s "https://<site>/cron/agent-timeouts?key=<cron_secret>"
```

The daily `cron/run` sweeps too, just less promptly, so a platform with one
cron entry still works.

## Settings

Under **Admin → Settings → Agent**.

| Key | Default | What it does |
|---|---|---|
| `agent_float_enabled` | `0` | Master switch. Off = the panel is review-only, exactly as before. |
| `deposit_route` | `admin` | `admin` / `agent` / `both` |
| `withdraw_route` | `admin` | `admin` / `agent` / `both` |
| `agent_deposit_commission_percent` | `1` | Earned per deposit settled |
| `agent_withdraw_commission_percent` | `1` | Earned per withdrawal paid |
| `agent_accept_timeout_hours` | `6` | Before a request escalates |
| `agent_payout_fee_percent` | `0` | Fee on an agent cash-out |
| `agent_min_float` | `0` | Float an agent must hold to be listed at all |

Per-agent overrides live on the agent form: `commission_settle_percent` and
`commission_withdraw_percent`, blank meaning "use the platform setting". The
older `commission_deposit_percent` and `commission_profit_percent` still drive
the team-based accrual and are unrelated to these.

An agent is offered to a user only when they are active, have
`accepting_deposits` on, hold at least the package price (and `agent_min_float`)
in float, and have at least one active receive wallet. The accept action
re-checks all of it rather than trusting the picker.

## Rolling it out

1. Back up the database. The migration rewrites `agents`, `deposits` and
   `withdrawals`; that cannot be undone from inside MySQL.
2. `mysql -u <user> -p <database> < database/upgrade_agent_float.sql`
   Additive and re-runnable: every change checks `information_schema` first,
   nothing is dropped, and the currently deployed code keeps working against
   the new schema.
3. Deploy the code. Still nothing changes for users —
   `agent_float_enabled` ships as `0` and both routes as `admin`.
4. Turn `agent_float_enabled` on. The agent panel grows its Money section; the
   user-facing screens are unchanged while the routes stay on `admin`.
5. Give an agent float (approve a float order, or credit it by hand from
   **Admin → Agents → Wallets**) and have them add a receive wallet.
6. Move `deposit_route` to `both`, watch a live settle, then decide whether to
   go to `agent`. Same for `withdraw_route`.

**Rollback** is setting the routes back to `admin`, or
`agent_float_enabled` to `0`. No code revert and no schema change: in-flight
rows keep their `agent_status`, and anything the agents did not finish
escalates to the admin queue on the next sweep.

## Permissions

New keys, all granted to the `admin` preset and view-only to `moderator`:

- `agent_float.view` / `.approve` / `.reject`
- `agent_payouts.view` / `.approve` / `.mark_paid` / `.reject`
- `agents.adjust_balance` — writing an agent wallet by hand. Deliberately
  separate from `agents.manage`: editing an account is not moving money. The
  reason field is mandatory and lands in both the ledger row and the admin log.

## Where the code lives

```
database/upgrade_agent_float.sql      migration (run this on live)
application/libraries/
  Agent_wallet_lib.php                the only writer of agent balances
  Agent_settle_lib.php                settle / pay / decline / expire
application/models/
  Agent_wallet_model.php              agent receive addresses
  Agent_ledger_model.php              read side of agent_ledger
  Agent_float_order_model.php         float purchases
  Agent_payout_model.php              cash-outs
application/controllers/
  admin/Agent_float.php               approve / reject float orders
  admin/Agent_payouts.php             approve / pay / reject cash-outs
  admin/Agents.php                    + wallets() and adjust()
  agent/Requests.php                  accept deposits, pay withdrawals
  agent/Float_orders.php              buy float          (route: agent/float)
  agent/Payouts.php                   cash out, transfer
  agent/Wallets.php                   receive addresses
  agent/Ledger.php                    wallet history
  Cron.php                            + agent_timeouts()
```

`agent/Deposits.php` and `agent/Withdrawals.php` are the old review-only
screens. They still load no money library, and that absence is the safety
property — the float system's writes live in `agent/Requests.php` instead.
