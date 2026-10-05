<?php

namespace App\Modules\Rental\Mail;

use App\Modules\Rental\Models\Quotation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class QuotationSentMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Quotation $quotation, public readonly string $responseUrl) {}

    public function build(): self
    {
        return $this->subject('Báo giá '.$this->quotation->quotation_no)
            ->view('emails.rental.quotation-sent');
    }
}
