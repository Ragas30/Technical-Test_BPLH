<?php

namespace App\Http\Requests\Review;

use App\DTO\Review\ReviewDecisionDTO;
use Illuminate\Foundation\Http\FormRequest;

class CommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Isi komentar untuk review.
             *
             * @example Mohon lampirkan dokumen kajian teknis yang lebih lengkap.
             */
            'notes' => ['required', 'string', 'max:2000'],
        ];
    }

    public function toDTO(): ReviewDecisionDTO
    {
        return new ReviewDecisionDTO(
            notes: $this->string('notes')->toString(),
        );
    }
}
