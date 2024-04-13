<?php

namespace App\Http\Requests;

use App\Models\File;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class ParentIdBaseRequest extends FormRequest
{
    
    
    public ?File $parent = null;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
{
    // Retrieve the parent_id using $this->input('parent_id')
    $this->parent = File::query()->where('id', $this->input('parent_id'))->first();

    // Log both parent_id and Auth::id()
    Log::info('Parent ID: ' . ($this->parent ? $this->parent->id : 'null'));
    Log::info('Auth ID: ' . Auth::id());

    if ($this->parent && !$this->parent->isOwnedBy(Auth::id())) {
        return false;
    }
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
            'parent_id' => [
                Rule::exists(File::class, 'id')
                    ->where(function (Builder $query) {
                        return $query
                            ->where('is_folder', '=', '1')
                            ->where('created_by', '=', Auth::id());
                    })
            ]
        ];
    }
}
