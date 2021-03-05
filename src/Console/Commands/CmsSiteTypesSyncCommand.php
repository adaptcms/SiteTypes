<?php

namespace Adaptcms\SiteTypes\Console\Commands;

use Illuminate\Console\Command;

use Adaptcms\SiteTypes\Models\SiteType;

class CmsSiteTypesSyncCommand extends Command
{
  /**
   * The name and signature of the console command.
   *
   * @var string
   */
  protected $signature = 'cms:siteTypes:sync';

  /**
   * The console command description.
   *
   * @var string
   */
  protected $description = 'Sync local site types to DB.';

  /**
   * Execute the console command.
   *
   * @return int
   */
  public function handle()
  {
    SiteType::sync();
  }
}
