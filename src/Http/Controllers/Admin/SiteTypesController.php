<?php

namespace Adaptcms\SiteTypes\Http\Controllers\Admin;

use Illuminate\Http\Request;

use Adaptcms\Base\Base;
use Adaptcms\SiteTypes\Models\SiteType;
use Adaptcms\SiteTypes\Http\Requests\StoreSiteTypeRequest;
use Adaptcms\SiteTypes\Http\Requests\UpdateSiteTypeRequest;
use App\Http\Controllers\Controller;

use Redirect;

class SiteTypesController extends Controller
{
  /**
  * Index
  *
  * @param Request $request
  *
  * @return Response
  */
  public function index(Request $request)
  {
    // get paginated site types
    $sortBy = $request->get('sortBy', 'created_at');
    $sortDir = $request->get('sortDir', 'desc');

    $items = SiteType::orderBy($sortBy, $sortDir)
      ->paginate(15)
      ->appends(request()->query());

    // get marketplace URI for search
    $marketplace_uri = config('services.app.marketplace_uri');

    return $this->renderUiView('admin/index', compact(
      'items',
      'sortBy',
      'sortDir',
      'marketplace_uri'
    ));
  }

  /**
  * Create
  *
  * @param Request $request
  *
  * @return Response
  */
  public function create(Request $request)
  {
    return $this->renderUiView('admin/create');
  }

  /**
  * Store
  *
  * @param StoreSiteTypeRequest $request
  *
  * @return Redirect
  */
  public function store(StoreSiteTypeRequest $request)
  {
    // init SiteType
    $siteType = new SiteType;

    // create site type
    $siteType->fill($request->only('vendor', 'package', 'github_url'));

    $siteType->manualStore($request->publish);

    // flash message and redirect
    $request->session()->flash('message', 'Site Type has been created!');

    return Redirect::route('site_types.admin.index');
  }

  /**
  * Edit
  *
  * @param Request  $request
  * @param SiteType $siteType
  *
  * @return Response
  */
  public function edit(Request $request, SiteType $siteType)
  {
    $model = $siteType;

    return $this->renderUiView('admin/edit', compact(
      'model'
    ));
  }

  /**
  * Update
  *
  * @param UpdateSiteTypeRequest $request
  * @param SiteType              $siteType
  *
  * @return Redirect
  */
  public function update(UpdateSiteTypeRequest $request, SiteType $siteType)
  {
    // save SiteType
    $siteType->fill($request->only('vendor', 'package', 'github_url', 'is_active'));

    $siteType->manualUpdate($request->publish);

    // flash message and redirect
    $request->session()->flash('message', 'Site Type has been saved!');

    return Redirect::route('site_types.admin.index');
  }

  /**
  * Destroy
  *
  * @param Request  $request
  * @param SiteType $siteType
  *
  * @return Redirect
  */
  public function destroy(Request $request, SiteType $siteType)
  {
    // delete SiteType
    $siteType->manualDestroy();

    // flash message and redirect
    $request->session()->flash('message', 'Site Type has been deleted!');

    return Redirect::route('site_types.admin.index');
  }

  /**
  * Show
  *
  * @param Request  $request
  * @param SiteType $siteType
  *
  * @return Response
  */
  public function show(Request $request, SiteType $siteType)
  {
    return $this->renderUiView('admin/show', compact(
      'siteType'
    ));
  }

  /**
  * Search
  *
  * @param Request $request
  *
  * @return string
  */
  public function search(Request $request)
  {
    $siteTypes = SiteType::search($request->get('query'))->get();

    $results = [];
    foreach ($siteTypes as $siteType) {
      $results[] = $siteType->toSearchableArray();
    }

    return response()->json(compact('results'));
  }

  /**
  * Install
  *
  * @param Request $request
  * @param string  $slug
  *
  * @return Redirect
  */
  public function install(Request $request, string $slug)
  {
    // get package from marketplace API
    $result = file_get_contents(config('services.app.marketplace_uri') . '/packages/show/' . $slug);
    $result = json_decode($result);

    $package = $result->package;

    // install package
    (new SiteType)->installPackage($package->github_url);

    // flash message and redirect
    $request->session()->flash('message', 'Site Type has been installed!');

    return Redirect::route('site_types.admin.index');
  }

  /**
  * Show Activate
  *
  * @param Request  $request
  * @param SiteType $siteType
  *
  * @return Redirect
  */
  public function showActivate(Request $request, SiteType $siteType)
  {
    // get config data
    $config = $siteType->getConfig();

    $basicConfig   = $config['basicConfig'];
    $customModules = $config['customModules'];
    $customPages   = $config['customPages'];

    // get saved settings data
    $settings = $siteType->settings()->all();
    $settings = $settings['config'];

    // retrieve any form meta
    $formMeta = $siteType->getFormMeta($request);

    return $this->renderUiView('admin/activate', compact(
      'siteType',
      'basicConfig',
      'customModules',
      'customPages',
      'settings',
      'formMeta'
    ));
  }

  /**
  * Post Activate
  *
  * @param Request  $request
  * @param SiteType $siteType
  *
  * @return Redirect
  */
  public function postActivate(Request $request, SiteType $siteType)
  {
    // get config data
    $config = $siteType->getConfig();

    // basic config validation
    $rules = [];
    foreach ($config['basicConfig'] as $row) {
      if (isset($row['is_required_create'])) {
        $rules[$row['column_name']] = 'required';
      }
    }

    if (!empty($rules)) {
      $request->validate($rules);
    }

    // activate site type config, modules, and pages
    $siteType->activateSiteType($config, $request);

    // flash message and redirect
    $request->session()->flash('message', 'Site Type has been activated!');

    return Redirect::route('site_types.admin.index');
  }

  /**
  * Show Settings
  *
  * @param Request  $request
  * @param SiteType $siteType
  *
  * @return Redirect
  */
  public function showSettings(Request $request, SiteType $siteType)
  {
    // get config data
    $config = $siteType->getConfig();
    $config = $config['basicConfig'];

    // get saved settings data
    $settings = $siteType->settings()->all();
    $settings = $settings['config'];

    // retrieve any form meta
    $formMeta = $siteType->getFormMeta($request);

    return $this->renderUiView('admin/settings', compact(
      'siteType',
      'config',
      'settings',
      'formMeta'
    ));
  }

  /**
  * Update Settings
  *
  * @param Request  $request
  * @param SiteType $siteType
  *
  * @return Redirect
  */
  public function updateSettings(Request $request, SiteType $siteType)
  {
    // save settings
    $siteType->saveSettings($request);

    // flash message and redirect
    $request->session()->flash('message', 'Site Type settings have been saved!');

    return Redirect::route('site_types.admin.index');
  }
}
