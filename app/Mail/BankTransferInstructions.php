<?php

namespace App\Mail;

use App\Models\Payment;
use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BankTransferInstructions extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payment $payment,
        public Subscription $subscription
    ) {}

    public function build()
    {
        return $this->subject('Instrucciones para completar tu pago por transferencia')
            ->view('emails.bank_transfer_instructions');
    }
}
