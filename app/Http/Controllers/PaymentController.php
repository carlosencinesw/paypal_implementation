<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Illuminate\Support\Facades\Request as res;

class PaymentController extends Controller
{
    private $apicontext;

    public function __construct()
    {
        $config = config('paypal');

        $this->apicontext = new ApiContext(new OAuthTokenCredential(
            $config['client_id'],
            $config['secret']
        ));
    }

    public function index()
    {
        return view('payment');
    }

    public function pay(Request $request)
    {
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $item = new Item();
        $item->setName('Entrega Box4Buy')
             ->setCurrency('BRL')
             ->setQuantity(1)
             ->setPrice($request->amount);

        $itemList = new ItemList();
        $itemList->setItems([$item]);

        $amount = new Amount();
        $amount->setTotal($request->amount);
        $amount->setCurrency('BRL');

        $transaction = new Transaction();
        $transaction->setAmount($amount)
                    ->setItemList($itemList)
                    ->setDescription("PAGAMENTO DE TESTE DA API");

        $redirect_urls = new RedirectUrls();
        $redirect_urls->setReturnUrl(route('status'))
                      ->setCancelUrl(route('status'));

        $payment = new Payment();
        $payment->setIntent('sale')
                ->setPayer($payer)
                ->setTransactions([$transaction])
                ->setRedirectUrls($redirect_urls);

        try {
            $payment->create($this->apicontext);

            session()->put('payment_id', $payment->getId());

            //return "<a href='{$payment->getApprovalLink()}' target='_blank'>PAGAR</a>";
            return redirect($payment->getApprovalLink());
        } catch (\PayPal\Exception\PayPalConnectionException $e) {
            return dump($e->getData());
        }
    }

    public function getStatus()
    {
        $payment_id = session('payment_id');

        session()->forget('payment_id');

        if (empty(res::input('PayerID')) || empty(res::input('token'))) {
            session()->put('error', 'Falha no pagamento');
            return redirect('/');
        }

        $payment = Payment::get($payment_id, $this->apicontext);
        $execute = new PaymentExecution();
        $execute->setPayerId(res::input('PayerID'));

        $result = $payment->execute($execute, $this->apicontext);

        if ($result->getState() == 'approved') {
            session()->put('success', 'Pagamento Efetuado');
            return redirect('/');
        }

        session()->put('error', 'Erro no pagamento');
        return redirect('/');
    }
}
