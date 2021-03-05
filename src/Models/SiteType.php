<?php

namespace Adaptcms\SiteTypes\Models;

use Glorand\Model\Settings\Traits\HasSettingsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;

use Adaptcms\Base\Models\GlobalWarning;
use Adaptcms\Base\Models\PackageField;
use Adaptcms\Base\Traits\HasComposer;
use Adaptcms\Base\Traits\HasPackager;
use Adaptcms\Base\Traits\HasUuid;
use Adaptcms\Fields\Models\Field;
use Adaptcms\Modules\Models\Module;
use Adaptcms\Pages\Models\Page;

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
    'name',
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
  * @param bool $overwriteLayout
  *
  * @throws \Exception
  * @return Plugin
  */
  public function manualStore($publishPackage = false, $overwriteLayout = false)
  {
    $vendor = Str::studly($this->vendor);
    $package = Str::studly($this->package);

    if (!strstr($package, 'SiteType')) {
      $package = 'SiteType' . $package;
    }

    // create package for site type
    $this->createPackage($vendor, $package, [
      '--skeleton' => config('packager.site_type_skeleton')
    ]);

    // replace placeholders with module name
    $find = [
      ':uc:vendor',
      ':uc:package'
    ];

    $replace = [
      $vendor,
      $package
    ];

    $path = $vendor . '/' . $package . '/src/SiteType/' . $package . '.php';

    $contents = Storage::disk('packages')->get($path);

    $contents = str_replace($find, $replace, $contents);

    // update php file
    Storage::disk('packages')->put($path, $contents);

    // save site type to db
    $siteType = self::firstOrCreate(
      [
        'vendor'  => $vendor,
        'package' => $package
      ],
      [
        'name' => substr($package, 8)
      ]
    );

    // get fresh instance
    $siteType->fresh();

    // update record with composer contents
    $composer = self::getComposerFile($vendor, $package);

    $siteType->composer = $composer;

    $siteType->save();

    // get fresh instance again
    $siteType->fresh();

    // publish package if chosen
    if ($publishPackage && !empty($siteType->github_url)) {
      $this->publishPackage(
        $siteType->vendor,
        $siteType->package,
        $siteType->github_url
      );
    }

    // copy UI component files for use
    $siteType->copyUiComponents($overwriteLayout, true);

    return $siteType;
  }

  /**
  * Manual Update
  *
  * @param bool $publishPackage
  * @param bool $overwriteLayout
  *
  * @throws \Exception
  * @return Plugin
  */
  public function manualUpdate($publishPackage = false, $overwriteLayout = false)
  {
    $vendor = Str::studly($this->vendor);
    $package = Str::studly($this->package);

    // save plugin to db
    $this->fill(
      [
        'vendor'  => $vendor,
        'package' => $package,
        // remove `SiteType` prefix
        'name' => substr($package, 8)
      ]
    );

    $copyUiComponents = (!$overwriteLayout && $this->is_active && !$this->getOriginal('is_active'));
    $disableOtherSiteTypes = ($this->is_active && !$this->getOriginal('is_active'));

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

    // copy ui if overwriteLayout = true
    if ($overwriteLayout) {
      $this->copyUiComponents(true);
    }

    // copy UI components if site type now active
    if ($copyUiComponents) {
      $this->copyUiComponents();
    }

    // disable other site types if this site type is now active
    if ($disableOtherSiteTypes) {
      $siteTypes = SiteType::where('id', '!=', $this->id)
        ->where('is_active', true)
        ->get();

      if ($siteTypes->count()) {
        foreach ($siteTypes as $model) {
          $model->is_active = false;

          $model->manualUpdate();
        }
      }
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

    // delete UI component files
    $this->deleteUiComponents();

    return $this;
  }

  /**
  * Find Site Type
  *
  * @param string $vendor
  * @param string $package
  *
  * @return null|Field
  */
  public static function findSiteType(string $vendor, string $package)
  {
    return self::where('vendor', $vendor)->where('package', $package)->firstOrFail();
  }

  /**
  * Sync
  * Sync local site types with database
  *
  * @return void
  */
  public static function sync()
  {
    $vendors = Storage::disk('packages')->directories('/');

    foreach ($vendors as $vendor) {
      $packages = Storage::disk('packages')->directories('/' . $vendor);

      foreach ($packages as $package) {
        $packageName = str_replace($vendor . '/', '', $package);

        // if not prefixed by `SiteType`, skip it
        if (substr($packageName, 0, 8) !== 'SiteType' || $packageName === 'SiteTypes') continue;

        // get composer contents
        $composer = self::getComposerFile($vendor, $packageName);

        $github_url = 'https://github.com/' . $composer['name'];

        $siteType = self::firstOrCreate(
          [
            'vendor'  => $vendor,
            'package' => $packageName
          ],
          [
            'github_url' => $github_url,
            'composer'   => $composer
          ]
        );

        // if existing site type, save github/composer info if empty
        if (empty($siteType->github_url) || empty($siteType->composer)) {
          $siteType->github_url = $github_url;
          $siteType->composer   = $composer;

          $siteType->save();
        }

        // copy UI component files for use
        $siteType->copyUiComponents();
      }
    }
  }

  /**
  * Model Class
  * Returns core model class file for site type
  *
  * @return string
  */
  public function modelClass()
  {
    $class = '\\' . $this->vendor . '\\' . $this->package . '\\' . $this->package;

    return (new $class);
  }

  /**
  * Site Type Class
  * Returns site type class file for site type
  *
  * @return string
  */
  public function siteTypeClass()
  {
    $class = '\\' . $this->vendor . '\\' . $this->package . '\\SiteType\\' . $this->package;

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

  /**
  * Copy Ui components
  *
  * @param bool $overwriteLayout
  * @param bool $isNew
  *
  * @return void
  */
  public function copyUiComponents($overwriteLayout = false, $isNew = false)
  {
    // start with activate site type component
    $activateUiComponent = 'ActivateSiteType.vue';
    $renamedActivateComponent = 'ActivateSiteType' . $this->name . '.vue';

    // copy over component ui to site folder path
    $activateUiPath = $this->vendor . '/' . $this->package . '/ui/' . $activateUiComponent;
    if (Storage::disk('packages')->exists($activateUiPath)) {
      $contents = Storage::disk('packages')->get($activateUiPath);

      Storage::disk('site-types')->put($renamedActivateComponent, $contents);
    }

    // get contents of folder `index.js` file
    $indexPath = 'index.js';

    // make sure file exists, if not, touch it
    if (!Storage::disk('site-types')->exists($indexPath)) {
      Storage::disk('site-types')->put($indexPath, '');
    }

    // then get contents of the file
    $indexContents = Storage::disk('site-types')->get($indexPath);

    // if reference does not exist for this component
    // add a reference
    if (!strstr($indexContents, $renamedActivateComponent)) {
      $indexContents .= "export { default as " . $this->package . " } from './" . $renamedActivateComponent . "'" . PHP_EOL;

      Storage::disk('site-types')->put($indexPath, $indexContents);
    }

    // check if overwriteLayout setting exists and if so, is set to true
    $contents = Storage::disk('packages')->get($this->vendor . '/' . $this->package . '/src/SiteType/' . $this->package . '.php');

    if ($overwriteLayout || ($isNew && strstr($contents, 'overwriteLayout = true'))) {
      // move default layout to a new filename
      $layoutPath = 'ui/layouts/layout.vue';
      $newLayoutPath = 'ui/layouts/defaultLayout.vue';

      if (!Storage::disk('site')->exists($newLayoutPath)) {
        Storage::disk('site')->move($layoutPath, $newLayoutPath);
      }

      // copy over site type layout to be new default layout
      $layoutPath = $this->vendor . '/' . $this->package . '/ui/layouts/layout.vue';
      $newLayoutPath = 'Adaptcms/Site/ui/layouts/layout.vue';
      if (Storage::disk('packages')->exists($layoutPath)) {
        Storage::disk('packages')->copy($layoutPath, $newLayoutPath);
      }
    }

    // throw global warning so user runs npm to rebuild files
    GlobalWarning::storeWarning('Adaptcms', 'SiteTypes', 'run-npm', [
      'message' => 'Please run <strong>npm run dev</strong>, or the proper alternative to rebuild files and then reload the page.'
    ]);
  }

  /**
  * Delete Ui components
  *
  * @return void
  */
  public function deleteUiComponents()
  {
    // delete activate ui component
    $renamedActivateComponent = 'ActivateSiteType' . $this->name . '.vue';

    if (Storage::disk('site-types')->exists($renamedActivateComponent)) {
      Storage::disk('site-types')->delete($renamedActivateComponent);
    }

    // then reset layout to default if site type
    // has overwrite layout flag on
    $overwriteLayout = $this->siteTypeClass()->overwriteLayout;
    if ($overwriteLayout) {
      $layoutPath = 'Adaptcms/Site/ui/layouts/layout.vue';
      $defaultLayoutPath = 'Adaptcms/Site/ui/layouts/defaultLayout.vue';

      if (Storage::disk('packages')->exists($defaultLayoutPath)) {
        Storage::disk('packages')->copy($defaultLayoutPath, $layoutPath);
      }
    }

    // get contents of folder `index.js` file
    $indexPath = 'index.js';

    // make sure file exists, if not, touch it
    if (!Storage::disk('site-types')->exists($indexPath)) {
      Storage::disk('site-types')->put($indexPath, '');
    }

    // then get contents of the file
    $indexContents = Storage::disk('site-types')->get($indexPath);

    // if reference does exist for this component
    // delete the reference
    if (strstr($indexContents, $renamedActivateComponent)) {
      $find = "export { default as " . $this->package . " } from './" . $renamedActivateComponent . "'" . PHP_EOL;

      $indexContents = str_replace($find, '', $indexContents);

      Storage::disk('site-types')->put($indexPath, $indexContents);
    }

    // throw global warning so user runs npm to rebuild files
    GlobalWarning::storeWarning('Adaptcms', 'SiteTypes', 'run-npm', [
      'message' => 'Please run <strong>npm run dev</strong>, or the proper alternative to rebuild files and then reload the page.'
    ]);
  }

  /**
  * Get Config For Activation
  *
  * @return array
  */
  public function getConfigForActivation()
  {
    $data = [
      'basicConfig'   => [],
      'customModules' => [],
      'customPages'   => []
    ];

    // get config and add in field keys
    $basicConfig = $this->siteTypeClass()->config;

    foreach ($basicConfig as $key => $row) {
      $basicConfig[$key]['column_name'] = Str::slug($row['name']);
      $basicConfig[$key]['value'] = null;
      $basicConfig[$key]['meta'] = isset($row['meta']) ? $row['meta'] : [];
    }

    $data['basicConfig'] = $basicConfig;

    // get modules and pages
    $customModules = $this->siteTypeClass()->modules;

    foreach ($customModules as $key => $row) {
      $customModules[$key]['slug'] = Str::slug($row['name']);
      $customModules[$key]['value'] = true;

      foreach ($row['fields'] as $fieldKey => $field) {
        $customModules[$key]['fields'][$fieldKey]['slug'] = Str::slug($field['name']);
        $customModules[$key]['fields'][$fieldKey]['value'] = true;
      }
    }

    $data['customModules'] = $customModules;

    $customPages = $this->siteTypeClass()->pages;

    foreach ($customPages as $key => $row) {
      $customPages[$key]['slug'] = Str::slug($row['name']);
      $customPages[$key]['value'] = true;

      foreach ($row['fields'] as $fieldKey => $field) {
        $customPages[$key]['fields'][$fieldKey]['slug'] = Str::slug($field['name']);
        $customPages[$key]['fields'][$fieldKey]['value'] = true;
      }
    }

    $data['customPages'] = $customPages;

    return $data;
  }

  /**
  * Activate Site Type
  *
  * @param array $config
  * @param array $data
  *
  * @return SiteType
  */
  public function activateSiteType(array $config, array $data)
  {
    // dd($data);

    // set basic config data to site type settings
    if (!empty($config['basicConfig'])) {
      $basicConfig = [];
      foreach ($config['basicConfig'] as $row) {
        $field = $row['column_name'];
        $value = isset($data[$field]) ? $data[$field] : null;

        $basicConfig['config.' . $field] = $value;
      }

      // $this->settings()->setMultiple($basicConfig);
    }

    // set up modules and package fields
    if (!empty($config['customModules'])) {
      foreach ($config['customModules'] as $module) {
        $isEnabled = $data['modules'][$module['slug']]['value'] === 'true';

        if ($isEnabled) {
          // $moduleModel = Module::manualStore($module['name'], false);

          foreach ($module['fields'] as $index => $field) {
            $isFieldEnabled = $data['modules'][$module['slug']]['fields'][$field['slug']] === 'true';

            if (!$isFieldEnabled) continue;

            $fieldParams = [];

            // get field type model
            $customField = Field::where('package', $field['field'])->firstOrFail();

            // first field will be primary
            if ($index === 0) {
              $fieldParams['is_primary'] = true;
            }

            // set meta data
            if (isset($field['meta'])) {
              $fieldParams['meta'] = $field['meta'];
            }

            // $packageField = PackageField::manualStore($moduleModel, $customField, $fieldParams);

            if (!empty($packageField)) {
              // set required options if they are set
              if (!empty($field['is_required_create'])) {
                $packageField->settings()->set('options.is_required_create', true);
              }

              if (!empty($field['is_required_edit'])) {
                $packageField->settings()->set('options.is_required_edit', true);
              }
            }
          }
        }
      }
    }

    // set up pages and package fields
    if (!empty($config['customPages'])) {
      foreach ($config['customPages'] as $page) {
        $isEnabled = $data['pages'][$page['slug']]['value'] === 'true';

        if ($isEnabled) {
          // $pageModel = Page::manualStore($page['name'], false);

          foreach ($page['fields'] as $index => $field) {
            $isFieldEnabled = $data['pages'][$page['slug']]['fields'][$field['slug']] === 'true';

            if (!$isFieldEnabled) continue;

            $fieldParams = [];

            // get field type model
            $customField = Field::where('package', $field['field'])->firstOrFail();

            // first field will be primary
            if ($index === 0) {
              $fieldParams['is_primary'] = true;
            }

            // set meta data
            if (isset($field['meta'])) {
              $fieldParams['meta'] = $field['meta'];
            }

            // $packageField = PackageField::manualStore($pageModel, $customField, $fieldParams);

            if (!empty($packageField)) {
              // set required options if they are set
              if (!empty($field['is_required_create'])) {
                $packageField->settings()->set('options.is_required_create', true);
              }

              if (!empty($field['is_required_edit'])) {
                $packageField->settings()->set('options.is_required_edit', true);
              }
            }
          }
        }
      }
    }

    dd($data);

    return $this;
  }
}
