<?php

namespace Adaptcms\SiteTypes\Models;

use Glorand\Model\Settings\Traits\HasSettingsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

use Adaptcms\Base\Traits\HasComposer;
use Adaptcms\Base\Traits\HasPackager;
use Adaptcms\Base\Traits\HasUuid;

use Storage;
use URL;

class SiteType extends Model
{
  use
    HasComposer,
    HasPackager,
    HasSettingsTable,
    HasUuid,
    Searchable;

  /**
  * @var array
  */
  public $defaultSettings = [
    //
  ];

  /**
  * @var array
  */
  protected $fillable = [
    'vendor',
    'package',
    'github_url',
    'composer',
    'is_active'
  ];

  /**
  * @var array
  */
  protected $casts = [
    'composer'  => 'array',
    'is_active' => 'boolean'
  ];

  /**
  * @var array
  */
  protected $appends = [
    'admin_link_show'
  ];

  /**
  * Manual Store
  *
  * @param bool $publishPackage
  *
  * @throws \Exception
  * @return Plugin
  */
  public function manualStore($publishPackage = false)
  {
    $vendor = Str::studly($this->vendor);
    $package = Str::studly($this->package);

    // create package for plugin
    $this->createPackage($vendor, $package);

    // save plugin to db
    $plugin = self::firstOrCreate(
      [
        'vendor'  => $vendor,
        'package' => $package
      ]
    );

    // get fresh instance
    $plugin->fresh();

    // update record with composer contents
    $composer = self::getComposerFile($vendor, $package);

    $plugin->composer = $composer;

    $plugin->save();

    // get fresh instance again
    $plugin->fresh();

    // publish package if chosen
    if ($publishPackage && !empty($plugin->github_url)) {
      $this->publishPackage(
        $plugin->vendor,
        $plugin->package,
        $plugin->github_url
      );
    }

    return $plugin;
  }

  /**
  * Manual Update
  *
  * @param bool $publishPackage
  *
  * @throws \Exception
  * @return Plugin
  */
  public function manualUpdate($publishPackage = false)
  {
    $vendor = Str::studly($this->vendor);
    $package = Str::studly($this->package);

    // save plugin to db
    $this->fill(
      [
        'vendor'  => $vendor,
        'package' => $package
      ]
    );

    $this->save();

    // get fresh instance
    $this->fresh();

    // publish package if chosen
    if ($publishPackage && !empty($this->github_url)) {
      $this->publishPackage(
        $this->vendor,
        $this->package,
        $this->github_url
      );
    }

    return $this;
  }

  /**
  * Manual Destroy
  *
  * @throws \Exception
  * @return Plugin
  */
  public function manualDestroy()
  {
    // delete plugin
    $this->delete();

    // remove from composer
    $this->removePackage($this->vendor, $this->package);

    return $this;
  }

  /**
  * Sync
  * Sync local plugins with database
  *
  * @return void
  */
  public static function sync()
  {
    $vendors = Storage::disk('site-types')->directories('/');

    foreach ($vendors as $vendor) {
      $packages = Storage::disk('site-types')->directories('/' . $vendor);

      foreach ($packages as $package) {
        $packageName = str_replace($vendor . '/', '', $package);

        // get composer contents
        $composer = self::getComposerFile($vendor, $packageName);

        $github_url = 'https://github.com/' . $composer['name'];

        self::firstOrCreate(
          [
            'vendor'  => $vendor,
            'package' => $packageName
          ],
          [
            'github_url' => $github_url,
            'composer'   => $composer
          ]
        );
      }
    }
  }

  /**
  * Model Class
  * Returns core model class file for plugin
  *
  * @return string
  */
  public function modelClass()
  {
    $class = '\\' . $this->vendor . '\\' . $this->package . '\\' . $this->package;

    return (new $class);
  }

  /**
   * Get the indexable data array for the model.
   *
   * @return array
   */
  public function toSearchableArray()
  {
    $item = $this->toArray();

    $item['name'] = $this->vendor . '/' . $this->package;

    unset($item['composer']);

    return $item;
  }

  /**
  * Get Admin Link Show Attribute
  *
  * @return string
  */
  public function getAdminLinkShowAttribute()
  {
    return URL::route('site_types.admin.show', [
      'siteType' => $this->id
    ]);
  }
}
