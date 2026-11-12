<?php

namespace Adobrovolsky97\Illuminar\Http\Requests;

use Adobrovolsky97\Illuminar\Helpers\SearchHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class SearchRequest
 */
class SearchRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'page'                     => ['nullable', 'integer', 'min:1'],
            'page_size'                => ['nullable', 'integer', 'min:10', 'max:500'],
            'search'                   => ['nullable', 'string', 'max:500'],
            'context_id'               => ['nullable', 'string', 'max:64'],
            'group_duplicated_queries' => ['nullable', 'boolean'],
            'is_slow'                  => ['nullable', 'boolean'],
            'is_duplicate'             => ['nullable', 'boolean'],
            'types'                    => ['nullable', 'array'],
            'types.*'                  => ['nullable', 'string', Rule::in(SearchHelper::TYPES)],
        ];
    }
}
