<?php

namespace App\Http\Controllers;

use App\Http\Requests\Payment\PaymentRequest;
use App\Models\Integration;
use App\Models\Invoice;
use App\Models\InvoiceLine;
use App\Models\Offer;
use App\Models\Payment;
use App\Services\Invoice\GenerateInvoiceStatus;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Ramsey\Uuid\Uuid;
use Illuminate\Support\Facades\DB;

class PaymentsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Payment $payment
     * @return \Illuminate\Http\Response
     * @throws \Exception
     */
    public function destroy(Payment $payment)
    {
        if (!auth()->user()->can('payment-delete')) {
            session()->flash('flash_message', __("You don't have permission to delete a payment"));
            return redirect()->back();
        }
        $api = Integration::initBillingIntegration();
        if ($api) {
            $api->deletePayment($payment);
        }

        $payment->delete();
        session()->flash('flash_message', __('Payment successfully deleted'));
        return redirect()->back();
    }

    public function addPayment(PaymentRequest $request, Invoice $invoice)
    {
        if (!$invoice->isSent()) {
            session()->flash('flash_message_warning', __("Can't add payment on Invoice"));
            return redirect()->route('invoices.show', $invoice->external_id);
        }

        if($request->amount > $request->amount_due) {
            session()->flash('flash_message_warning', __("Le montant que vous payer ne dois pas dépasser le reste à payer"));
            return redirect()->back();
        }

        $payment = Payment::create([
            'external_id' => Uuid::uuid4()->toString(),
            'amount' => $request->amount * 100,
            'payment_date' => Carbon::parse($request->payment_date),
            'payment_source' => $request->source,
            'description' => $request->description,
            'invoice_id' => $invoice->id
        ]);
        $api = Integration::initBillingIntegration();
        if ($api && $invoice->integration_invoice_id) {
            $result = $api->createPayment($payment);
            $payment->integration_payment_id = $result["Guid"];
            $payment->integration_type = get_class($api);
            $payment->save();
        }
        app(GenerateInvoiceStatus::class, ['invoice' => $invoice])->createStatus();

        session()->flash('flash_message', __('Payment successfully added'));
        return redirect()->back();
    }

    public function totalPayments(){
        $total = 0;
        $payments = Payment::all();
        foreach ($payments as $vola) {
            $total += $vola->amount/100;
        }
        return response()->json($total);
    }

    public function paginatedListPayment(Request $request){
        $payments = Payment::paginate(20, ['*'], 'page', $request->page);

        return response()->json([
            'data' => $payments->items(),
            'total_pages' => $payments->lastPage(),
            'current_page' => $payments->currentPage()
        ]);
    }

    public function updatePayment(Request $request) {
        \DB::beginTransaction();
        $payment = Payment::where('external_id', $request->external_id)->firstOrFail();

        try {
            $payment->amount = $request->amount * 100;
            $payment->updated_at = Carbon::now();
            $payment->save();

            $sommeFacture = 0;
            foreach($payment->invoice->invoiceLines as $line){
                $sommeFacture += $line->price;
            }

            if ($payment->invoice->payments->sum('amount') > $sommeFacture) {
                \DB::rollBack();
                return response()->json("err:Le total de paiement depasse le prix de la facture", 200);
            }

            \DB::commit();
            return response()->json("suc:Le paiement a ete mis a jour avec succes");
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json("err:An error occurred while updating the payment ".$e->getMessage() , 500);
        }
    }

    public function deletePayment(Request $request) {
        $payment = Payment::where('external_id', $request->external_id)->first();
        $payment->deleted_at = Carbon::now();
        $payment->save();
        return response()->json("suc:Le paiement a ete efface avec succes");
    }

    public function getPaymentLastDays(){
        $days = 14;
        $dateRange = [];
        $currentDate = Carbon::now();
        $depart = Carbon::now()->subDays($days);

        while ($currentDate >= $depart) {
            $dateRange[] = $depart->format('Y-m-d');
            $depart->addDay();
        }

        $results = DB::table(DB::raw("(SELECT '".implode("' AS date UNION SELECT '", $dateRange)."') AS dates"))
            ->leftJoin('payments', function($join) {
                $join->on(DB::raw('dates.date'), '=', DB::raw('DATE(payments.created_at)'))
                    ->where('payments.created_at', '>=', '2025-03-07');
            })
            ->select(DB::raw('dates.date AS creation'), DB::raw('COUNT(payments.id) AS count'))
            ->groupBy('dates.date')
            ->orderBy('dates.date')
            ->get();

        $paymentCounts = [];

        foreach ($results as $res) {
            $paymentCounts[] = ["date" => $res->creation, "nbr" => $res->count];
        }

        return response()->json($paymentCounts);
    }
}
