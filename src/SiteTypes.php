<?php

namespace Adaptcms\SiteTypes;

use Adaptcms\Auth\Models\Permission;
use Adaptcms\Base\Models\Package;

class SiteTypes
{
  /**
  * Sync Permissions
  *
  * @return void
  */
  public function syncPermissions()
  {
    $permissions = [
      'site_types.admin.index',
      'site_types.admin.create',
      'site_types.admin.edit',
      'site_types.admin.delete',
      'site_types.admin.show',
      'site_types.admin.search',
      'site_types.admin.settings',
      'site_types.admin.install'
    ];

    Permission::syncPackagePermissions($permissions);
  }

  /**
  * On Install
  *
  * @return void
  */
  public function onInstall()
  {
    Package::syncPackageFolder(get_class());
  }
}
