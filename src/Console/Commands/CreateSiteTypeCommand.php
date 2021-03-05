<?php

namespace Adaptcms\SiteTypes\Console\Commands;

use Illuminate\Console\Command;

use Adaptcms\SiteTypes\Models\SiteType;

class CreateSiteTypeCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'cms:siteType:create {vendor} {package}';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Command to create a new site type for the cms.';

  /**
   * Execute the console command.
   *
   * @return void
   */
  public function handle()
  {
    $vendor = $this->argument('vendor');
    $package = $this->argument('package');
    $overwriteLayout = $this->confirm('Should this site type overwrite the default public layout?');

    try {
      $siteType = new SiteType(compact(
        'vendor',
        'package'
      ));

      $siteType->manualStore(false, $overwriteLayout);

      $this->info('Site Type `' . $siteType->vendor . '/' . $siteType->package . '` has been created.');
    } catch (\Exception $e) {
      $this->error($e->getMessage());
      $this->error('Site Type `' . $siteType->vendor . '/' . $siteType->package . '` could not be created. Existing site type clash, or reserved name provided.');
    }
  }
}
