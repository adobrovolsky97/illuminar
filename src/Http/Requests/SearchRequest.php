<?php

namespace Adobrovolsky97\Illuminar\Http\Requests;

use Adobrovolsky97\Illuminar\Watchers\CacheWatcher;
use Adobrovolsky97\Illuminar\Watchers\DumpWatcher;
use Adobrovolsky97\Illuminar\Watchers\EventWatcher;
use Adobrovolsky97\Illuminar\Watchers\ExceptionWatcher;
use Adobrovolsky97\Illuminar\Watchers\HttpRequestWatcher;
use Adobrovolsky97\Illuminar\Watchers\JobWatcher;
use Adobrovolsky97\Illuminar\Watchers\MailWatcher;
use Adobrovolsky97\Illuminar\Watchers\ModelWatcher;
use Adobrovolsky97\Illuminar\Watchers\QueryWatcher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class SearchRequest
 */
class SearchRequest extends FormRequest
{
    /**
     * @return array[]
     */
    public function rules(): array
    {
        return [
            'page'                     => ['nullable', 'integer', 'min:1'],
            'page_size'                => ['nullable', 'integer', 'min:50'],
            'search'                   => ['nullable', 'string'],
            'group_duplicated_queries' => ['nullable', 'boolean'],
            'is_slow'                  => ['nullable', 'boolean'],
            'is_duplicate'             => ['nullable', 'boolean'],
            'types'                    => ['nullable', 'array'],
            'types.*'                  => [
                'nullable',
                'string',
                Rule::in([
                    DumpWatcher::getName(),
                    CacheWatcher::getName(),
                    ModelWatcher::getName(),
                    MailWatcher::getName(),
                    QueryWatcher::getName(),
                    EventWatcher::getName(),
                    ExceptionWatcher::getName(),
                    HttpRequestWatcher::getName(),
                    JobWatcher::getName()
                ])
            ],
        ];
    }
}
