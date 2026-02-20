<?php

namespace App\Support;

use Illuminate\Support\Str;

class PatientFieldManager
{
    /**
     * @return array{identifier_required:bool,fields:array<int,array{key:string,label:string,type:string,active:bool,built_in:bool,locked:bool,required:bool}>}
     */
    public static function resolved(mixed $stored): array
    {
        $raw = is_array($stored) ? $stored : [];
        $identifierRequired = (bool) ($raw['identifier_required'] ?? false);

        $storedFields = collect($raw['fields'] ?? [])
            ->filter(fn (mixed $field) => is_array($field) && is_string($field['key'] ?? null))
            ->values();

        $storedByKey = $storedFields->keyBy(fn (array $field) => (string) $field['key']);

        $fields = [
            self::fixedField('first_name', __('messages.first_name'), 'text'),
            self::fixedField('last_name', __('messages.last_name'), 'text'),
            self::fixedField('age', __('messages.age'), 'number'),
        ];

        foreach (['sex', 'phone', 'identifier'] as $key) {
            $rawField = $storedByKey->get($key, []);
            $defaultLabel = self::defaultLabel($key);

            $label = trim((string) ($rawField['label'] ?? $defaultLabel));
            $active = (bool) ($rawField['active'] ?? true);

            if ($key === 'identifier' && $identifierRequired) {
                $active = true;
            }

            $fields[] = [
                'key' => $key,
                'label' => $label !== '' ? $label : $defaultLabel,
                'type' => self::defaultType($key),
                'active' => $active,
                'built_in' => true,
                'locked' => false,
                'required' => $key === 'sex' ? true : ($key === 'identifier' ? $identifierRequired : false),
            ];
        }

        foreach ($storedFields as $rawField) {
            $key = (string) ($rawField['key'] ?? '');

            if (in_array($key, ['first_name', 'last_name', 'age', 'sex', 'phone', 'identifier'], true)) {
                continue;
            }

            if (preg_match('/^custom_[a-z0-9_]{1,40}$/', $key) !== 1) {
                continue;
            }

            $label = trim((string) ($rawField['label'] ?? ''));

            if ($label === '') {
                continue;
            }

            $type = (string) ($rawField['type'] ?? 'text');

            if (! in_array($type, ['text', 'number'], true)) {
                $type = 'text';
            }

            $fields[] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'active' => (bool) ($rawField['active'] ?? true),
                'built_in' => false,
                'locked' => false,
                'required' => false,
            ];
        }

        return [
            'identifier_required' => $identifierRequired,
            'fields' => $fields,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $submittedFields
     * @param  array<string, mixed>  $newField
     * @return array{identifier_required:bool,fields:array<int,array{key:string,label:string,type:string,active:bool}>}
     */
    public static function normalizeForStorage(array $submittedFields, bool $identifierRequired, array $newField = []): array
    {
        $editableBuiltIns = [
            'sex' => null,
            'phone' => null,
            'identifier' => null,
        ];

        $customFields = [];

        foreach ($submittedFields as $field) {
            if (! is_array($field)) {
                continue;
            }

            $key = trim((string) ($field['key'] ?? ''));

            if ($key === '' || in_array($key, ['first_name', 'last_name', 'age'], true)) {
                continue;
            }

            $label = trim((string) ($field['label'] ?? ''));
            $type = (string) ($field['type'] ?? 'text');
            $active = filter_var($field['active'] ?? false, FILTER_VALIDATE_BOOL);
            $delete = filter_var($field['delete'] ?? false, FILTER_VALIDATE_BOOL);

            if (array_key_exists($key, $editableBuiltIns)) {
                $defaultLabel = self::defaultLabel($key);

                $editableBuiltIns[$key] = [
                    'key' => $key,
                    'label' => $label !== '' ? $label : $defaultLabel,
                    'type' => self::defaultType($key),
                    'active' => $key === 'identifier' && $identifierRequired ? true : $active,
                ];

                continue;
            }

            if (preg_match('/^custom_[a-z0-9_]{1,40}$/', $key) !== 1) {
                continue;
            }

            if ($delete) {
                continue;
            }

            if ($label === '') {
                continue;
            }

            if (! in_array($type, ['text', 'number'], true)) {
                $type = 'text';
            }

            $customFields[$key] = [
                'key' => $key,
                'label' => $label,
                'type' => $type,
                'active' => $active,
            ];
        }

        foreach (array_keys($editableBuiltIns) as $key) {
            if ($editableBuiltIns[$key] !== null) {
                continue;
            }

            $editableBuiltIns[$key] = [
                'key' => $key,
                'label' => self::defaultLabel($key),
                'type' => self::defaultType($key),
                'active' => $key === 'identifier' && $identifierRequired ? true : true,
            ];
        }

        $newLabel = trim((string) ($newField['label'] ?? ''));

        if ($newLabel !== '') {
            $newType = (string) ($newField['type'] ?? 'text');

            if (! in_array($newType, ['text', 'number'], true)) {
                $newType = 'text';
            }

            $existingKeys = [
                ...array_keys($editableBuiltIns),
                ...array_keys($customFields),
            ];

            $newKey = self::makeCustomKey($newLabel, $existingKeys);

            $customFields[$newKey] = [
                'key' => $newKey,
                'label' => $newLabel,
                'type' => $newType,
                'active' => filter_var($newField['active'] ?? true, FILTER_VALIDATE_BOOL),
            ];
        }

        return [
            'identifier_required' => $identifierRequired,
            'fields' => [
                $editableBuiltIns['sex'],
                $editableBuiltIns['phone'],
                $editableBuiltIns['identifier'],
                ...array_values($customFields),
            ],
        ];
    }

    private static function fixedField(string $key, string $label, string $type): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'type' => $type,
            'active' => true,
            'built_in' => true,
            'locked' => true,
            'required' => true,
        ];
    }

    private static function defaultLabel(string $key): string
    {
        return match ($key) {
            'sex' => __('messages.sex'),
            'phone' => __('messages.phone'),
            'identifier' => __('messages.patient_identifier'),
            default => Str::headline($key),
        };
    }

    private static function defaultType(string $key): string
    {
        return match ($key) {
            'age' => 'number',
            default => 'text',
        };
    }

    /**
     * @param  array<int, string>  $existingKeys
     */
    private static function makeCustomKey(string $label, array $existingKeys): string
    {
        $base = Str::of(Str::slug($label, '_'))->lower()->trim('_')->toString();
        $base = $base !== '' ? $base : 'champ';
        $base = Str::limit($base, 34, '');

        $candidate = 'custom_'.$base;
        $suffix = 1;

        while (in_array($candidate, $existingKeys, true)) {
            $suffixText = '_'.$suffix;
            $trimmedBase = Str::limit($base, max(1, 34 - strlen($suffixText)), '');
            $candidate = 'custom_'.$trimmedBase.$suffixText;
            $suffix += 1;
        }

        return $candidate;
    }
}
