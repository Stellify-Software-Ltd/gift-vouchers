# Gift Vouchers

A free gift voucher system for small businesses. A voucher here is **stored value,
not a discount**: the business was paid in full when it sold the voucher, and
redeeming one draws down money the business already owes. That's why the heart of
this app is a ledger, not a status column — every issue, redemption and void is a
row, and a voucher's balance is always the sum of its rows. A stored balance that
can drift is worse than no system.

Built with [Stellify](https://stellisoft.com) — and shipped as a completely
standard Laravel application: no Stellify dependency, MIT licensed, yours to change.

## What it does

- **Issue** — you take payment however you already do (card machine, cash, your
  own site), then record the voucher here: auto-generated code, value, purchaser,
  recipient, expiry date and an optional personal message. You get a clean print
  view and can email the voucher to the recipient in one click
- **Register** — everything ever issued, searchable by code, purchaser or
  recipient. Status (active, partially redeemed, redeemed, expired, void) is
  always derived from the ledger, the void marker and the expiry date — never
  stored, so it can never be wrong
- **Redeem by hand** — look up a code (case doesn't matter), see the balance,
  enter the amount spent and who took it. Partial redemption leaves the remainder
  on the voucher. Over-redemption, and redemption against expired or void
  vouchers, are rejected server-side; a locked database transaction recomputes
  the balance from the ledger, so two tills can't double-spend the same voucher
- **Void** — irreversible, needs a reason and a staff name, and is recorded as a
  ledger row that zeroes the remaining balance
- **Liability report** — the running total of unredeemed value on live vouchers:
  money the business owes. Expired-but-unredeemed value is shown separately —
  what to do with it is your accountant's call, not this system's. This page
  exists because your accountant will ask for it
- All money is integer pence — no floats, ever. Codes look like
  `GV-XXXX-XXXX-XXXX`, generated with `random_int` over an alphabet with no
  0/O or 1/I, so nothing gets misread off a crumpled printout

## What it deliberately does not do

No selling online, no automatic redemption at checkout, no branded voucher
design, no bulk or corporate orders, no expiry reminders, no accounting exports.
The paid [Stellify Constellation module](https://stellisoft.com) adds those.

## Requirements

- PHP 8.3+ with the `sqlite3` extension (any recent PHP install has it)
- [Composer](https://getcomposer.org)
- No database server needed — SQLite is the default. PostgreSQL is recommended
  for production: set the `DB_*` variables in `.env` and migrate

## Setup

```bash
git clone https://github.com/Stellify-Software-Ltd/gift-vouchers.git
cd gift-vouchers
composer install
cp .env.example .env
php artisan key:generate
touch database/database.sqlite
php artisan migrate --seed
php artisan serve
```

Then set your **operator key** in `.env` (any hard-to-guess string):

```
OPERATOR_KEY=pick-something-long-and-random
```

Open `http://localhost:8000` and sign in with it. The seeder loads a handful of
demo vouchers in every state (dated relative to today, so they never go stale) —
run `php artisan migrate:fresh` before going live to start clean.

## The operator key

One shared key opens the desk — small businesses share a till, so instead of
per-user accounts every transaction records a required "who did it" staff name,
which ends up in the ledger next to the amount. Sign-in attempts are rate-limited
(5 per minute per IP) and failures are logged. Sign-in is refused entirely while
`OPERATOR_KEY` is empty. To rotate the key, change it in `.env`.

## Email

Out of the box `MAIL_MAILER=log`: the "Email it" button writes the voucher email
to `storage/logs/laravel.log` instead of sending it, so you can try the whole
flow before setting anything up. For real delivery, set the usual Laravel mail
variables in `.env`, e.g. for SMTP:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=you@yourdomain.com
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=you@yourdomain.com
MAIL_FROM_NAME="Your Business"
```

Any provider Laravel supports (Postmark, Resend, SES, Mailgun…) works the same way.

## The liability page

Every unredeemed pound on a live voucher is money you owe — customers will walk
in months from now and expect to spend it. `/liability` totals that up from the
ledger, lists every voucher carrying a balance, and shows expired-but-unredeemed
value as a separate figure so your accountant can decide how to treat it.

## A note on VAT

This system gives no tax advice. In the UK, vouchers are treated differently for
VAT depending on whether they are single-purpose or multi-purpose, and the rules
changed in 2019. The ledger records everything an accountant needs to apply
whichever treatment is right for you: the sale date and value of each voucher,
every redemption date and amount, and what remains. Ask your accountant.

## Tests

```bash
php artisan test
```

The suite (34 tests) covers issuing (ledger row, code format and uniqueness),
partial and full redemption with derived statuses, over-redemption and
expired/void redemption rejected server-side, the double-spend race (balance
recomputed under lock from the ledger), voiding (zeroing ledger row, blocks
further redemption, can't void twice), the liability report totals, register
search, and the operator gate on every page including rate-limited sign-in.

## Licence

MIT — see [LICENSE](LICENSE).
