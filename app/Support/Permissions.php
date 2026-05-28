<?php

namespace App\Support;

/**
 * Registry of granular CRUD permissions per resource.
 *
 * Permission keys follow the format "{resource}.{action}" — e.g.
 * "beneficiaries.view", "donations.delete". Sections that don't fit
 * the standard CRUD set (reports, system_logs) declare their own
 * actions in the registry.
 */
class Permissions
{
    /**
     * Full registry of resources and the actions each supports.
     *
     * @return array<string, array{label:string, actions:array<string, string>}>
     */
    public static function registry(): array
    {
        $crud = [
            'view' => 'View',
            'create' => 'Create',
            'update' => 'Update',
            'delete' => 'Delete',
        ];

        return [
            'beneficiaries' => ['label' => 'Beneficiaries', 'actions' => $crud],
            'beneficiary_applications' => ['label' => 'Beneficiary applications', 'actions' => $crud],
            'volunteers' => ['label' => 'Volunteers', 'actions' => $crud],
            'events' => ['label' => 'Events', 'actions' => $crud],
            'donations' => ['label' => 'Donations', 'actions' => $crud],
            'testimonials' => ['label' => 'Testimonials', 'actions' => $crud],
            'leaders' => ['label' => 'Leadership team', 'actions' => $crud],
            'media' => ['label' => 'Media library', 'actions' => $crud],
            'instagram' => ['label' => 'Instagram', 'actions' => $crud],
            'newsletter' => ['label' => 'Newsletter subscribers', 'actions' => $crud],
            'inbox' => ['label' => 'Email inbox', 'actions' => ['view' => 'View', 'reply' => 'Reply', 'delete' => 'Delete']],
            'reports' => ['label' => 'Reports', 'actions' => ['view' => 'View', 'export' => 'Export']],
            'users' => ['label' => 'User accounts', 'actions' => $crud],
            'system_logs' => ['label' => 'System logs', 'actions' => ['view' => 'View']],
        ];
    }

    /**
     * Flat list of every valid permission key.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        $out = [];
        foreach (self::registry() as $resource => $config) {
            foreach (array_keys($config['actions']) as $action) {
                $out[] = $resource.'.'.$action;
            }
        }

        return $out;
    }

    public static function isValid(string $key): bool
    {
        return in_array($key, self::keys(), true);
    }

    /**
     * Filter an array of keys to only those that exist in the registry.
     *
     * @param  array<int, string>  $keys
     * @return array<int, string>
     */
    public static function sanitize(array $keys): array
    {
        $valid = self::keys();

        return array_values(array_intersect(array_unique($keys), $valid));
    }

    public static function resourceLabel(string $resource): string
    {
        return self::registry()[$resource]['label'] ?? $resource;
    }

    public static function actionLabel(string $resource, string $action): string
    {
        return self::registry()[$resource]['actions'][$action] ?? ucfirst($action);
    }

    public static function buildKey(string $resource, string $action): string
    {
        return $resource.'.'.$action;
    }
}
