# SilverStripe Facebook Feed


## Introduction

Allows creation of Facebook feeds in SilverStripe by converting access tokens into long lived and permanent access tokens. 

If you would like to view how this process works, please visit: https://stackoverflow.com/questions/12168452/long-lasting-fb-access-token-for-server-to-pull-fb-page-info

## Requirements
* SilverStripe CMS ^4

## Installation

```
composer require internetrix/silverstripe-facebook-feed
```

## Quickstart

````
// Adding to a class
private static $has_one = [
    'FacebookFeed' => FacebookFeed::class,
];


// As a CMS field
DropdownField::create('FacebookFeedID','Select a feed:', FacebookFeed::get()->map('ID', 'Title'))


// Calling the Feed in the template
<% with $FacebookFeed %>
    <% loop $Feed %>
        {Post details go here}
    <% end_loop %>
<% end_with %>

````
### A list of fields retrieved by default:
* User
* Link
* ProfileLink
* Image
* Message
* Posted

More optional fields can be found here: https://developers.facebook.com/docs/graph-api/reference/v4.0/page/feed#readfields

### Available extension hooks
* updateFeedFields = Alter the URL used to add or remove fields from the API call
* updateFeedPosts  = Make changes to the final ArrayList of posts before it's returned

## TODO
More bug testing

Proper error validation
