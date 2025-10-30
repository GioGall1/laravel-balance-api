<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'from_user_id' => ['required','integer','exists:users,id'],
            'to_user_id'   => ['required','integer','exists:users,id','different:from_user_id'],
            'amount'       => ['required','numeric','min:0.01'],
            'comment'      => ['nullable','string','max:255'],
        ];
    }

    public function authorize(): bool
    {
        return true;
    }
}
