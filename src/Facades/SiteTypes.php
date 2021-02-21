<?php

namespace Adaptcms\SiteTypes\Facades;

use Illuminate\Support\Facades\Facade;

class SiteTypes extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'sitetypes';
    }
}
