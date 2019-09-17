<?php

namespace Dexven\FacebookFeed\Admin;

use Dexven\FacebookFeed\Model\FacebookFeed;
use SilverStripe\Admin\ModelAdmin;

class FeedManager extends ModelAdmin
{
    private static $menu_title = 'Facebook Feeds';

    private static $url_segment = 'facebook-feeds';

    private static $menu_icon_class = 'font-icon-picture';

    private static $managed_models = [
        FacebookFeed::class
    ];

    public function subsiteCMSShowInMenu()
    {
        return true;
    }
}
