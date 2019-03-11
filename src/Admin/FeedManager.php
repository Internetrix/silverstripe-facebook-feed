<?php

namespace Dexven\KeyConverter\Admin;

use Dexven\KeyConverter\Model\FacebookFeed;
use SilverStripe\Admin\ModelAdmin;

class FeedManager extends ModelAdmin
{
    private static $menu_title = 'Facebook Feeds';

    private static $url_segment = 'facebook-feeds';

    private static $managed_models = [
        FacebookFeed::class
    ];

    public function subsiteCMSShowInMenu()
    {
        return true;
    }
}
