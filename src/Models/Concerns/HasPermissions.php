<?php

namespace VATSIMUK\Support\Auth\Models\Concerns;

use Illuminate\Support\Collection;
use VATSIMUK\Support\Auth\Facades\PermissionValidity;

trait HasPermissions
{
    /**
     * Determine if the model may perform the given permission.
     *
     * @param string $permission
     *
     * @return bool
     */
    public function hasPermissionTo($permission): bool
    {
        return PermissionValidity::permissionSatisfiedByPermissions($permission, $this->getAllPermissions());
    }

    /**
     * Determine if the model has any of the given permissions.
     *
     * @param array ...$permissions
     *
     * @return bool
     */
    public function hasAnyPermission(...$permissions): bool
    {
        if (is_array($permissions[0])) {
            $permissions = $permissions[0];
        }
        foreach ($permissions as $permission) {
            if ($this->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the model has all of the given permissions.
     *
     * @param array ...$permissions
     *
     * @return bool
     */
    public function hasAllPermissions(...$permissions): bool
    {
        if (is_array($permissions[0])) {
            $permissions = $permissions[0];
        }
        foreach ($permissions as $permission) {
            if (! $this->hasPermissionTo($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Return all the permissions the model has, both directly and via roles.
     */
    public function getAllPermissions(): Collection
    {
        return is_array($this->attribute('all_permissions')) ? collect($this->attribute('all_permissions')) : $this->attribute('all_permissions');
    }
}
