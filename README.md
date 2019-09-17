# SilverStripe Facebook Feed


## Introduction

Allows creation of Facebook feeds in SilverStripe by converting access tokens into long lived and permanent access tokens. 

If you would like to view how this process works, please visit: https://stackoverflow.com/questions/12168452/long-lasting-fb-access-token-for-server-to-pull-fb-page-info

## Requirements
* SilverStripe CMS ^4

## Installation

```
composer require dexven/silverstripe-facebook-feed
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
$GetFacebookFeed($FacebookFeedID)

````


## TODO
More bug testing

Proper error validation
