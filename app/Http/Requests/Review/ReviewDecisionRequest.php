<?php

namespace App\Http\Requests\Review;

use App\DTO\Review\ReviewDecisionDTO;
use Illuminate\Foundation\Http\FormRequest;

class ReviewDecisionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Catatan keputusan review. Wajib diisi untuk reject dan revisi.
             *
             * @example Dokumen teknis sudah sesuai, namun perlu tambahan detail kapasitas.
             */
            'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }

    public function toDTO(): ReviewDecisionDTO
    {
        return new ReviewDecisionDTO(
            notes: $this->string('notes')->toString() !== '' ? $this->string('notes')->toString() : null,
        );
    }
}
