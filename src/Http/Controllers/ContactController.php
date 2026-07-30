<?php

namespace App\Http\Controllers;

use App\Enums\ContactReason;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ContactController extends Controller
{
    public function index()
    {
        return view('public.contact');
    }

    public function send(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => ['required', Rule::enum(ContactReason::class)],
            'message' => 'required|string|min:10',
        ]);

        // TODO: Nastavit Mail driver v .env a přidat Mailable
        // Mail::to(config('mail.contact_address'))->send(new ContactMail($validated));

        return redirect()->route('contact.index')
            ->with('success', 'Děkujeme! Vaše zpráva byla odeslána. Ozveme se co nejdříve.');
    }
}
