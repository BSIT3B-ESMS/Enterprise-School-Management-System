<?php

namespace App\Http\Controllers;

use App\Actions\Recruitment\RecruitmentException;
use App\Actions\Recruitment\RespondToOffer;
use App\Models\Offer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * An employee's contract offers and changes of assignment: read the summary and terms, then accept (typing their full name is the
 * signature) or decline. Declining leaves them as they are.
 */
class EmployeeOfferController extends Controller
{
    public function index(Request $request): View
    {
        abort_if($request->user()->employee_id === null, 403);

        return view('employee.offers', [
            'offers' => Offer::query()->where('employee_id', $request->user()->employee_id)->with('issuer')->latest('id')->get(),
        ]);
    }

    public function show(Request $request, Offer $offer): View
    {
        abort_unless($offer->employee_id !== null && $offer->employee_id === $request->user()->employee_id, 404);

        return view('employee.offer', ['offer' => $offer->load('issuer')]);
    }

    public function accept(Request $request, Offer $offer, RespondToOffer $respond): RedirectResponse
    {
        abort_unless($offer->employee_id !== null && $offer->employee_id === $request->user()->employee_id, 404);

        $data = $request->validate(['signed_name' => ['required', 'string', 'max:150'], 'agree' => ['accepted']], [
            'signed_name.required' => 'Type your full name to sign.',
            'agree.accepted' => 'Confirm that you have read the agreement.',
        ]);

        try {
            $respond->accept($request->user(), $offer, $data['signed_name'], $request->ip());
        } catch (RecruitmentException $exception) {
            return back()->withInput()->with('error', $exception->getMessage());
        }

        return to_route('employee.offers.show', $offer)->with('success', 'You accepted. Thank you.');
    }

    public function decline(Request $request, Offer $offer, RespondToOffer $respond): RedirectResponse
    {
        abort_unless($offer->employee_id !== null && $offer->employee_id === $request->user()->employee_id, 404);

        $data = $request->validate(['reason' => ['nullable', 'string', 'max:500']]);

        try {
            $respond->decline($request->user(), $offer, $data['reason'] ?? null);
        } catch (RecruitmentException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return to_route('employee.offers.show', $offer)->with('success', 'You declined. Your current assignment stays as it is.');
    }
}
