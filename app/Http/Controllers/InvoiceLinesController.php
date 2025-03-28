<?php

namespace App\Http\Controllers;

use App\Models\InvoiceLine;
use Illuminate\Http\Request;

class InvoiceLinesController extends Controller
{
    public function destroy(InvoiceLine $invoiceLine)
    {
        if (!auth()->user()->can('modify-invoice-lines')) {
            session()->flash('flash_message_warning', __('You do not have permission to modify invoice lines'));
            return redirect()->route('invoices.show', $invoiceLine->invoice->external_id);
        }

        $invoiceLine->delete();

        Session()->flash('flash_message', __('Invoice line successfully deleted'));
        return redirect()->route('invoices.show', $invoiceLine->invoice->external_id);
    }

    public function getTotalPrix() {
        $total = \DB::select("select sum(price * quantity) as total from invoice_lines where offer_id is null")[0]->total/100;
        return response()->json($total);
    }

    public function getAllInvoiceLines(Request  $request) {
        $payments = InvoiceLine::where('offer_id', null)->paginate(20, ['*'], 'page', $request->page);

        return response()->json([
            'data' => $payments->items(),
            'total_pages' => $payments->lastPage(),
            'current_page' => $payments->currentPage()
        ]);
    }
}
