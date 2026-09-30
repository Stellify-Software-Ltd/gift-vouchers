<?php

namespace App\Http\Controllers;

use App\Mail\VoucherIssuedMail;
use App\Models\Voucher;
use App\Services\VoucherService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __construct(private readonly VoucherService $vouchers)
    {
    }

    // ------------------------------------------------------------------
    // Operator sign-in
    // ------------------------------------------------------------------

    public function loginScreen(Request $request): View|RedirectResponse
    {
        if ($request->session()->get('operator_authenticated')) {
            return redirect()->route('vouchers.index');
        }

        return view('login');
    }

    public function login(Request $request): RedirectResponse
    {
        $limiterKey = 'operator-login:'.$request->ip();

        if (RateLimiter::tooManyAttempts($limiterKey, 5)) {
            abort(429, 'Too many sign-in attempts. Please wait a minute and try again.');
        }

        $validated = $request->validate(['key' => 'required|string']);
        $operatorKey = (string) config('vouchers.operator_key');

        if ($operatorKey === '' || ! hash_equals($operatorKey, $validated['key'])) {
            RateLimiter::hit($limiterKey, 60);
            Log::warning('Rejected operator key on sign-in', ['ip' => $request->ip()]);

            return back()->withErrors(['key' => 'That key is not right.']);
        }

        RateLimiter::clear($limiterKey);
        $request->session()->regenerate();
        $request->session()->put('operator_authenticated', true);

        return redirect()->route('vouchers.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('operator_authenticated');
        $request->session()->regenerate();

        return redirect('/login');
    }

    // ------------------------------------------------------------------
    // Register
    // ------------------------------------------------------------------

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $vouchers = Voucher::query()
            ->when($search !== '', fn ($query) => $query->search($search))
            ->with('transactions')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('vouchers.index', ['vouchers' => $vouchers, 'search' => $search]);
    }

    // ------------------------------------------------------------------
    // Issue
    // ------------------------------------------------------------------

    public function create(): View
    {
        return view('vouchers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'value' => 'required|numeric|min:0.01|max:100000',
            'purchaser_name' => 'required|string|max:120',
            'purchaser_email' => 'nullable|email|max:190',
            'recipient_name' => 'nullable|string|max:120',
            'recipient_email' => 'nullable|email|max:190',
            'message' => 'nullable|string|max:500',
            'expires_on' => 'required|date|after:today',
            'staff_name' => 'required|string|max:60',
        ]);

        $voucher = $this->vouchers->issue([
            'value_pence' => (int) round($validated['value'] * 100),
            'purchaser_name' => $validated['purchaser_name'],
            'purchaser_email' => $validated['purchaser_email'] ?? null,
            'recipient_name' => $validated['recipient_name'] ?? null,
            'recipient_email' => $validated['recipient_email'] ?? null,
            'message' => $validated['message'] ?? null,
            'expires_on' => $validated['expires_on'],
            'staff_name' => $validated['staff_name'],
        ]);

        return redirect()
            ->route('vouchers.show', $voucher)
            ->with('status', "Voucher {$voucher->code} issued.");
    }

    // ------------------------------------------------------------------
    // Voucher detail, print & email
    // ------------------------------------------------------------------

    public function show(Voucher $voucher): View
    {
        $voucher->load('transactions');

        return view('vouchers.show', ['voucher' => $voucher]);
    }

    public function print(Voucher $voucher): View
    {
        return view('vouchers.print', ['voucher' => $voucher]);
    }

    public function email(Voucher $voucher): RedirectResponse
    {
        $to = $voucher->recipient_email ?: $voucher->purchaser_email;

        if (! $to) {
            return back()->withErrors(['email' => 'No purchaser or recipient email is recorded on this voucher.']);
        }

        Mail::to($to)->send(new VoucherIssuedMail($voucher));

        return back()->with('status', "Voucher emailed to {$to}.");
    }

    // ------------------------------------------------------------------
    // Redeem
    // ------------------------------------------------------------------

    public function redeemScreen(Request $request): View|RedirectResponse
    {
        $code = trim((string) $request->query('code', ''));

        if ($code !== '') {
            $voucher = Voucher::withCode($code)->first();

            if ($voucher) {
                return redirect()->route('vouchers.show', $voucher);
            }

            return view('redeem', ['code' => $code, 'notFound' => true]);
        }

        return view('redeem', ['code' => '', 'notFound' => false]);
    }

    public function redeem(Voucher $voucher, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:100000',
            'staff_name' => 'required|string|max:60',
            'note' => 'nullable|string|max:255',
        ]);

        $this->vouchers->redeem(
            $voucher,
            (int) round($validated['amount'] * 100),
            $validated['staff_name'],
            $validated['note'] ?? null,
        );

        return redirect()
            ->route('vouchers.show', $voucher)
            ->with('status', sprintf('£%s redeemed. £%s left on the voucher.', number_format($validated['amount'], 2), number_format($voucher->balancePence() / 100, 2)));
    }

    // ------------------------------------------------------------------
    // Void
    // ------------------------------------------------------------------

    public function void(Voucher $voucher, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => 'required|string|max:255',
            'staff_name' => 'required|string|max:60',
        ]);

        $this->vouchers->void($voucher, $validated['reason'], $validated['staff_name']);

        return redirect()
            ->route('vouchers.show', $voucher)
            ->with('status', "Voucher {$voucher->code} voided.");
    }

    // ------------------------------------------------------------------
    // Liability report
    // ------------------------------------------------------------------

    public function liability(): View
    {
        return view('liability', $this->vouchers->liability());
    }
}
