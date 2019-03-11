# SilverStripe / Facebook Token Converter


## Introduction

Allows creation of a Facebook feeds in SilverStripe by converting access tokens into long lived and permanent access tokens. 

## Requirements
* SilverStripe CMS ^4

## Installation

```
composer require dexven/facebook-token-converter
```

## Quickstart

````
private static $has_one = [
    'FacebookFeed' => FacebookFeed::class,
];


// As a CMS field
DropdownField::create('FacebookFeedID','Select Feed: ', FacebookFeed::get())

````


## TODO
More bug testing

Proper error validation