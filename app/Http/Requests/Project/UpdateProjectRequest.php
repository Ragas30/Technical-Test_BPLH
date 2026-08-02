<?php

namespace App\Http\Requests\Project;

use App\DTO\Project\UpdateProjectDTO;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            /**
             * Judul project.
             *
             * @example Pembangunan Instalasi Pengolahan Air Limbah Kawasan Industri
             */
            'title' => ['required', 'string', 'max:255'],

            /**
             * Deskripsi singkat project.
             *
             * @example Pembangunan IPAL komunal untuk kawasan industri dengan kapasitas 100 m3/hari.
             */
            'description' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function toDTO(): UpdateProjectDTO
    {
        return new UpdateProjectDTO(
            title: $this->string('title')->toString(),
            description: $this->input('description') !== null ? $this->string('description')->toString() : null,
        );
    }
}
