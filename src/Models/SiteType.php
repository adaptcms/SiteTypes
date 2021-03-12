<?php

namespace Adaptcms\SiteTypes\Models;

use Glorand\Model\Settings\Traits\HasSettingsTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

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

class SiteType extends Model implements HasMedia
{
  use
    HasComposer,
    HasPackager,
    HasSettingsTable,
    HasUuid,
    InteractsWithMedia,
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
  *
  * @throws \Exception
  * @return Plugin
  */
  public function manualStore($publishPackage = false)
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

    // copy ui components
    $this->copyUiComponents();

    return $siteType;
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
        'package' => $package,
        // remove `SiteType` prefix
        'name' => substr($package, 8)
      ]
    );

    // see if site type has been deactivated
    $deactivateSiteType = (!$this->is_active && $this->getOriginal('is_active'));

    $this->save();

    // get fresh instance
    $this->fresh();

    if ($deactivateSiteType) {
      $this->deactivateSiteType();
    } else {
      // publish package if chosen
      if ($publishPackage && !empty($this->github_url)) {
        $this->publishPackage(
          $this->vendor,
          $this->package,
          $this->github_url
        );
      }

      // copy ui components
      $this->copyUiComponents();
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
  * @param bool $existingSiteType
  *
  * @return void
  */
  public function copyUiComponents($existingSiteType = false)
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

    if ($existingSiteType) {
      $overwriteLayout = $this->siteTypeClass()->overwriteLayout;

      if ($overwriteLayout) {
        // move default layout to a new filename
        $layoutPath = 'ui/layouts/layout.vue';
        $newLayoutPath = 'ui/layouts/defaultLayout.vue';

        if (!Storage::disk('site')->exists($newLayoutPath)) {
          Storage::disk('site')->move($layoutPath, $newLayoutPath);
        }

        // copy over site type layout to be new default layout
        $layoutPath = $this->vendor . '/' . $this->package . '/ui/layouts/layout.vue';
        $newLayoutPath = 'ui/layouts/layout.vue';
        if (Storage::disk('packages')->exists($layoutPath)) {
          $contents = Storage::disk('packages')->get($layoutPath);

          Storage::disk('site')->put($newLayoutPath, $contents);
        }
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
  public function getConfig()
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
      // quick validation check
      if (!isset($row['name']) || empty($row['fields'])) {
        unset($customModules[$key]);

        continue;
      }

      $customModules[$key]['slug'] = Str::slug($row['name']);
      $customModules[$key]['value'] = true;

      foreach ($row['fields'] as $fieldKey => $field) {
        // quick validation check
        if (!isset($field['name']) || !isset($field['field'])) {
          unset($customModules[$key]['fields'][$fieldKey]);

          continue;
        }

        $customModules[$key]['fields'][$fieldKey]['slug'] = Str::slug($field['name']);
        $customModules[$key]['fields'][$fieldKey]['value'] = true;
      }
    }

    $data['customModules'] = $customModules;

    $customPages = $this->siteTypeClass()->pages;

    foreach ($customPages as $key => $row) {
      // quick validation check
      if (!isset($row['name']) || !isset($row['url']) || empty($row['fields'])) {
        unset($customPages[$key]);

        continue;
      }

      $customPages[$key]['slug'] = Str::slug($row['name']);
      $customPages[$key]['value'] = true;

      foreach ($row['fields'] as $fieldKey => $field) {
        // quick validation check
        if (!isset($field['name']) || !isset($field['field'])) {
          unset($customPages[$key]['fields'][$fieldKey]);

          continue;
        }

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
  * @param array   $config
  * @param Request $request
  *
  * @return SiteType
  */
  public function activateSiteType(array $config, Request $request)
  {
    $data = $request->all();

    // set basic config data to site type settings
    foreach ($config['basicConfig'] as $row) {
      $field = $row['column_name'];
      $value = isset($data[$field]) ? $data[$field] : null;

      // get field type model
      $customField = Field::where('package', $row['field'])->firstOrFail();

      $hasSaved = false;
      if (!empty($customField)) {
        $fieldType = $customField->fieldType();

        if (method_exists($fieldType, 'saveToSettings')) {
          $fieldType->saveToSettings($this, $request, $row, 'config.' . $field);

          $hasSaved = true;
        }
      }

      if (!$hasSaved) {
        $this->settings()->set('config.' . $field, $value);
      }
    }

    // set up modules and package fields

    // first, create and collect modules
    $modules = collect([]);
    foreach ($config['customModules'] as $module) {
      $isEnabled = $data['modules'][$module['slug']]['value'] === 'true';

      if ($isEnabled) {
        $moduleLookup = Module::where('name', $module['name'])->first();

        if (!empty($moduleLookup)) {
          $modules->push($moduleLookup);
        } else {
          $newModule = Module::manualStore($module['name'], false);
          $newModule->fresh();

          $modules->push($newModule);
        }
      }
    }

    foreach ($modules as $module) {
      // get fields
      $fields = [];
      foreach ($config['customModules'] as $row) {
        if ($row['slug'] === $module->slug) {
          $fields = $row['fields'];

          break;
        }
      }

      foreach ($fields as $index => $field) {
        // skip if field is not enabled
        $isFieldEnabled = $data['modules'][$module['slug']]['fields'][$field['slug']] === 'true';

        if (!$isFieldEnabled) continue;

        $fieldParams = [
          'name' => $field['name']
        ];

        // get field type model
        $customField = Field::where('package', $field['field'])->firstOrFail();

        // first field will be primary
        if ($index === 0) {
          $fieldParams['is_primary'] = true;
        }

        // set meta data
        if (isset($field['meta'])) {
          $fieldParams['meta'] = $field['meta'];

          $meta = $fieldParams['meta'];

          // if column_name set in meta, set it on field params instead
          if (isset($meta['column_name'])) {
            $fieldParams['column_name'] = $meta['column_name'];

            unset($fieldParams['meta']['column_name']);
          }

          // for relational fields, we need to correctly set the data
          if (isset($meta['related_module_name'])) {
            $relatedModule = $modules->firstWhere('name', $meta['related_module_name']);

            if (!empty($relatedModule)) {
              $fieldParams['meta']['related_package_type'] = get_class($relatedModule);
              $fieldParams['meta']['related_package_id'] = $relatedModule->id;

              unset($fieldParams['meta']['related_module_name']);
            }
          }
        }

        // save the package field
        $packageField = (new PackageField)->manualStore($module, $customField, $fieldParams);

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

    // set up pages and package fields

    // first, create and collect pages
    $pages = collect([]);
    foreach ($config['customPages'] as $page) {
      $isEnabled = $data['pages'][$page['slug']]['value'] === 'true';

      if ($isEnabled) {
        $pageLookup = Page::where('url', $page['url'])->first();

        if (!empty($pageLookup)) {
          $pages->push($pageLookup);
        } else {
          // create new page
          $newPage = new Page;

          $newPage->name = $page['name'];
          $newPage->url = isset($page['url']) ? $page['url'] : '/' . $page['slug'];

          $newPage = $newPage->manualStore(false);

          $pages->push($newPage);
        }
      }
    }

    foreach ($pages as $page) {
      // get fields
      $fields = [];
      foreach ($config['customPages'] as $row) {
        if ($row['slug'] === $page->slug) {
          $fields = $row['fields'];

          break;
        }
      }

      foreach ($fields as $index => $field) {
        // skip if field is not enabled
        $isFieldEnabled = $data['pages'][$page['slug']]['fields'][$field['slug']] === 'true';

        if (!$isFieldEnabled) continue;

        $fieldParams = [
          'name' => $field['name']
        ];

        // get field type model
        $customField = Field::where('package', $field['field'])->firstOrFail();

        // first field will be primary
        if ($index === 0) {
          $fieldParams['is_primary'] = true;
        }

        // set meta data
        if (isset($field['meta'])) {
          $fieldParams['meta'] = $field['meta'];

          $meta = $fieldParams['meta'];

          // if column_name set in meta, set it on field params instead
          if (isset($meta['column_name'])) {
            $fieldParams['column_name'] = $meta['column_name'];

            unset($fieldParams['meta']['column_name']);
          }
        }

        // save the package field
        $packageField = (new PackageField)->manualStore($page, $customField, $fieldParams);

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

    // set as active
    $this->is_active = true;

    $this->save();

    // look for any other site types that are active
    // and disable them
    $siteTypes = SiteType::where('id', '!=', $this->id)
      ->where('is_active', true)
      ->get();

    if ($siteTypes->count()) {
      foreach ($siteTypes as $model) {
        $model->is_active = false;

        $model->manualUpdate();
      }
    }

    // copy UI component files for use
    $this->copyUiComponents(true);
    $this->copyUiPages();

    return $this;
  }

  /**
  * Save Settings
  *
  * @param Request $request
  *
  * @return SiteType
  */
  public function saveSettings(Request $request)
  {
    $config = $this->getConfig();
    $data = $request->all();

    // set basic config data to site type settings
    foreach ($config['basicConfig'] as $row) {
      $field = $row['column_name'];
      $value = isset($data[$field]) ? $data[$field] : null;

      // get field type model
      $customField = Field::where('package', $row['field'])->firstOrFail();

      $hasSaved = false;
      if (!empty($customField)) {
        $fieldType = $customField->fieldType();

        if (method_exists($fieldType, 'saveToSettings')) {
          $fieldType->saveToSettings($this, $request, $row, 'config.' . $field);

          $hasSaved = true;
        }
      }

      if (!$hasSaved) {
        $this->settings()->set('config.' . $field, $value);
      }
    }

    return $this;
  }

  /**
  * Get Form Meta
  *
  * @param Request $request
  *
  * @return array
  */
  public function getFormMeta(Request $request)
  {
    $config = $this->getConfig();

    $formMeta = [];
    foreach ($config['basicConfig'] as $field) {
      $columnName = $field['column_name'];
      $customField = Field::where('package', $field['field'])->firstOrFail();
      $fieldType = $customField->fieldType();

      if (method_exists($fieldType, 'withSettingsFormMeta')) {
        $formMeta[$columnName] = $fieldType->withSettingsFormMeta($request, $this, $columnName);
      }
    }

    return $formMeta;
  }

  /**
  * Deactivate SiteType
  *
  * @return SiteType
  */
  public function deactivateSiteType()
  {
    // if overwriteLayout = true, set original layout back
    $overwriteLayout = $this->siteTypeClass()->overwriteLayout;

    if ($overwriteLayout) {
      $layoutPath = 'ui/layouts/layout.vue';
      $defaultLayoutPath = 'ui/layouts/defaultLayout.vue';

      if (Storage::disk('site')->exists($defaultLayoutPath)) {
        $defaultLayoutContents = Storage::disk('site')->get($defaultLayoutPath);

        Storage::disk('site')->put($layoutPath, $defaultLayoutContents);
      }
    }

    // delete modules set in config of site type class file
    $config = $this->getConfig();
    foreach ($config['customModules'] as $row) {
      $module = Module::where('name', $row['name'])->first();

      if (!empty($module)) {
        \Log::info('deleted module `' . $row['name'] . '`');
        $module->manualDestroy();
      } else {
        \Log::info('could NOT delete module `' . $row['name'] . '`');
      }
    }

    // delete pages set in config of site type class file
    foreach ($config['customPages'] as $row) {
      $page = Page::where('url', $row['url'])->first();

      if (!empty($page)) {
        \Log::info('deleted page `' . $row['url'] . '`');
        $page->manualDestroy();
      } else {
        \Log::info('could NOT delete page `' . $row['url'] . '`');
      }
    }

    $this->deleteUiPages();

    return $this;
  }

  /**
  * Copy Ui Pages
  *
  * @return void
  */
  public function copyUiPages()
  {
    $uiPath = $this->vendor . '/' . $this->package . '/ui';

    // only proceed to copy pages if a pages folder exists for the site type
    if (Storage::disk('packages')->exists($uiPath) && Storage::disk('packages')->exists($uiPath . '/pages')) {
      $pageFolders = Storage::disk('packages')->directories($uiPath . '/pages');

      // loop through folders within 'pages'
      foreach ($pageFolders as $folderPath) {
        // check if folder exists
        // if it does not, create it
        $folder = str_replace($uiPath . '/pages', '', $folderPath);
        if (!Storage::disk('site')->exists('ui/pages' . $folder)) {
          Storage::disk('site')->makeDirectory('ui/pages' . $folder);
        }

        // copy page files to `Site`
        $files = Storage::disk('packages')->files($folderPath);
        foreach ($files as $filePath) {
          $file = str_replace($uiPath . '/pages' . $folder, '', $filePath);
          $contents = Storage::disk('packages')->get($filePath);

          Storage::disk('site')->put('ui/pages' . $folder . $file, $contents);
        }

        // look for any folders within a folder in `/ui/pages`
        // an example being custom modules pages at say `/ui/pages/modules/Locations/index.vue`
        $folders = Storage::disk('packages')->directories($folderPath);
        foreach ($folders as $secondFolderPath) {
          $secondFolder = str_replace($uiPath . '/pages' . $folder, '', $secondFolderPath);
          if (!Storage::disk('site')->exists('ui/pages' . $folder .$secondFolder)) {
            Storage::disk('site')->makeDirectory('ui/pages' . $folder . $secondFolder);
          }

          $files = Storage::disk('packages')->files($secondFolderPath);
          foreach ($files as $filePath) {
            $file = str_replace($uiPath . '/pages' . $folder .$secondFolder, '', $filePath);
            $contents = Storage::disk('packages')->get($filePath);

            Storage::disk('site')->put('ui/pages' . $folder . $secondFolder . $file, $contents);
          }
        }
      }
    }
  }

  /**
  * Delete Ui Pages
  *
  * @return void
  */
  public function deleteUiPages()
  {
    $uiPath = $this->vendor . '/' . $this->package . '/ui';

    // only proceed to delete pages if a pages folder exists for the site type
    if (Storage::disk('packages')->exists($uiPath) && Storage::disk('packages')->exists($uiPath . '/pages')) {
      $pageFolders = Storage::disk('packages')->directories($uiPath . '/pages');

      foreach ($pageFolders as $folderPath) {
        $folder = str_replace($uiPath . '/pages', '', $folderPath);

        // if there is a custom folder in ui
        // delete from `Site`
        if (!in_array($folder, [ '/pages', '/modules' ])) {
          if (Storage::disk('site')->exists('ui/pages' . $folder)) {
            Storage::disk('site')->deleteDirectory('ui/pages' . $folder);
          }
        }

        // for pages folder we need to reset any overwritten
        // existing pages from the site type, such as the home page
        if ($folder === 'pages') {
          $files = Storage::disk('packages')->files($folderPath);

          foreach ($files as $filePath) {
            $file = str_replace($uiPath . '/pages' . $folder, '', $filePath);

            if (Storage::disk('site')->exists('ui/pages/pages' . $file)) {
              // empty/default page contents to reset the page
              $contents = Storage::disk('packages')->get('Adaptcms/Pages/src/Skeletons/EmptyPage.vue');

              Storage::disk('site')->put('ui/pages/pages' . $file, $contents);
            }
          }
        }
      }
    }
  }
}
