<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
        //dd('ok');
        return [
            'name' => 'required|max:100',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->where(function ($query) {
                    // Unique si le type est mobile
                    logger()->info('mobile : ' . $this->type);
                    if ($this->type !== 'mobile') {
                        $query->where('sign_in_by', $this->type);
                    }
                }),
            ],
            'type' => ['required', 'in:google,git,facebook,mobile'],
            'password' => ['required', Password::min(6)->letters()->symbols()->numbers()],
        ];
    }

    // Envoie de la réponse en JSON
    public function wantsJson()
    {
        return true;
    }

    // Désactivation de la redirection en cas d'erreur
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'message' => 'Les données fournies sont invalides.',
                'data' => $validator->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY)
        );
    }
}
