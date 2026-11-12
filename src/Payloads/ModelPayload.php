<?php

namespace Adobrovolsky97\Illuminar\Payloads;

use Adobrovolsky97\Illuminar\Watchers\ModelWatcher;
use Illuminate\Database\Eloquent\Model;

/**
 * Class ModelPayload
 */
class ModelPayload extends Payload
{
    private const HIDDEN_VALUE = '********';

    /**
     * Model event name, e.g. "eloquent.updated: App\Models\User"
     */
    private string $event;

    private array $payload;

    public function __construct(string $event, array $payload)
    {
        $this->event = $event;
        $this->payload = $payload;

        parent::__construct();
    }

    public static function actionFromEventName(string $event): string
    {
        $name = trim(explode(':', $event, 2)[0]);

        return str_starts_with($name, 'eloquent.') ? substr($name, strlen('eloquent.')) : '';
    }

    public function toArray(): array
    {
        $model = $this->payload['model'] ?? $this->payload[0] ?? null;

        if (!$model instanceof Model) {
            return [];
        }

        $primaryKey = $model->getKey();
        $action = self::actionFromEventName($this->event);

        switch ($action) {
            case 'created':
                $newAttributes = $this->hideAttributes($model, $model->getAttributes());
                $originalAttributes = [];
                break;
            case 'deleted':
            case 'restored':
                $newAttributes = [];
                $originalAttributes = [];
                break;
            default:
                $changes = $model->getChanges() ?: $model->getDirty();
                $newAttributes = $this->hideAttributes($model, $changes);
                $originalAttributes = $this->hideAttributes($model, $this->getOriginalValues($model, array_keys($changes)));
        }

        return array_merge($this->baseFields(ModelWatcher::getName()), [
            'action'              => $action,
            'model_class'         => get_class($model),
            'primary_key'         => is_array($primaryKey) ? '[' . implode(', ', $primaryKey) . ']' : $primaryKey,
            'new_attributes'      => $newAttributes,
            'original_attributes' => $originalAttributes,
        ]);
    }

    private function getOriginalValues(Model $model, array $keys): array
    {
        $original = [];

        foreach ($keys as $key) {
            $original[$key] = $model->getRawOriginal($key);
        }

        return $original;
    }

    /**
     * Mask $hidden attributes (passwords, tokens etc.)
     */
    private function hideAttributes(Model $model, array $attributes): array
    {
        foreach ($model->getHidden() as $hidden) {
            if (array_key_exists($hidden, $attributes)) {
                $attributes[$hidden] = self::HIDDEN_VALUE;
            }
        }

        return $attributes;
    }
}
