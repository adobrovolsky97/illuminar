<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\ModelWatcher;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ModelPayload
 */
class ModelPayload extends Payload
{
    /**
     * Model event name
     *
     * @var string
     */
    private string $event;

    /**
     * Model event payload
     *
     * @var array
     */
    private array $payload;

    /**
     * @param string $event
     * @param array $payload
     */
    public function __construct(string $event, array $payload)
    {
        $this->event = $event;
        $this->payload = $payload;

        parent::__construct();
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $model = $this->payload['model'] ?? $this->payload[0];

        if (!$model instanceof Model) {
            return [];
        }

        $primaryKey = $model->getKey();
        $action = $this->getAction();


        switch ($action) {
            case 'created':
                $newAttributes = $model->toArray();
                $originalAttributes = [];
                break;
            case 'deleted':
            case 'restored':
                $newAttributes = [];
                $originalAttributes = [];
                break;
            default:
                $newAttributes = $model->getChanges();
                $originalAttributes = $this->getOriginalValuesForChangedAttributes($model);
        }

        return [
            'uuid'                => $this->getUuid(),
            'type'                => ModelWatcher::getName(),
            'action'              => $action,
            'model_class'         => get_class($model),
            'primary_key'         => is_array($primaryKey) ? '[' . implode(', ', $primaryKey) . ']' : $primaryKey,
            'new_attributes'      => $newAttributes,
            'original_attributes' => $originalAttributes,
            'caller'              => $this->getCaller(),
            'time'                => now()->format('H:i:s'),
        ];
    }

    /**
     * Get original values for changed attributes
     *
     * @param Model $model
     * @return array
     */
    private function getOriginalValuesForChangedAttributes(Model $model): array
    {
        $original = [];

        foreach ($model->getChanges() as $key => $value) {
            $original[$key] = $model->getOriginal($key);
        }

        return $original;
    }

    /**
     * Get action name
     *
     * @return string
     */
    private function getAction(): string
    {
        preg_match('/\.(.*):/', $this->event, $matches);

        return $matches[1] ?? '';
    }
}
