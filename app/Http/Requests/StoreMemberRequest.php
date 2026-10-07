<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMemberRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name'                 => ['required', 'string', 'max:255'],
            'email'                     => ['nullable', 'email', 'max:255', 'unique:members,email', 'unique:users,email'],
            'password'                  => ['nullable', 'string', 'min:6'],
            'member_type'               => ['nullable', 'string', 'in:member,pastor,department_leader,accountant,treasurer,admin,secretary,deacon,shemasi'],
            'phone'                     => ['nullable', 'string', 'max:20'],
            'gender'                    => ['nullable', 'in:male,female,other'],
            'date_of_birth'             => ['nullable', 'date'],
            'marital_status'            => ['nullable', 'in:single,married,widowed,divorced'],
            'address'                   => ['nullable', 'string'],
            'salvation_date'            => ['nullable', 'date'],
            'baptism_date'              => ['nullable', 'date'],
            'profile_photo'             => ['nullable', 'image', 'max:2048'],
            'emergency_contact_name'    => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone'   => ['nullable', 'string', 'max:20'],
            'registration_type'         => ['nullable', 'string', 'max:255'],
            'status'                    => ['nullable', 'in:active,inactive,pending'],
            'departments'               => ['nullable', 'array'],
            'departments.*'             => ['exists:departments,id'],
        ];
    }

    /**
     * Custom validation messages
     */
    public function messages(): array
    {
        return [
            'full_name.required' => 'Jina kamili la mshiriki linahitajika.',
            'email.unique'       => 'Barua pepe hii tayari inatumiwa na mshiriki mwingine au mtumiaji wa mfumo.',
            'email.email'        => 'Tafadhali ingiza barua pepe iliyo sahihi.',
            'password.min'       => 'Nenosiri linapaswa kuwa na herufi zisizopungua 6.',
        ];
    }
}
