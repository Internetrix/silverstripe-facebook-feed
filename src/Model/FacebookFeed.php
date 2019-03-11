<?php

namespace Dexven\KeyConverter\Model;

use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Dev\Debug;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use GuzzleHttp\Client;

class FacebookFeed extends DataObject
{
    private static $db = [
        'Title' 		        => 'Varchar(255)',
        'UserID'		        => 'Varchar(50)',
        'ShortAccessToken'      => 'Text',
        'LongAccessToken'       => 'Text',
        'PermanentAccessToken'  => 'Text',
        'PublicToken'           => 'Text',
        'SecretToken'           => 'Text'
    ];

    private static $summary_fields = [
        'Title' 	            => 'Feed',
        'UserID'                => 'User ID'
    ];

    private static $has_one = [
        'Parent'                => DataObject::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Parent');

        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Title'),
            TextField::create('UserID')->setDescription('The ID of the page you are accessing. Not the name.'),

        ]);

        $fields->addFieldsToTab('Root.Main', [
            TextField::create('PublicToken'),
            TextField::create('SecretToken')
        ], 'ShortAccessToken');

        $fields->addFieldToTab('Root.Main', LiteralField::create('TokenHeader', '</br><h1>Access Tokens</h1>'), 'ShortAccessToken');

        $fields->addFieldsToTab('Root.Main', [
            TextareaField::create('ShortAccessToken'),
            TextareaField::create('LongAccessToken')->setDescription('This is automatically generated. Leave this field blank.'),
            TextareaField::create('PermanentAccessToken')->setDescription('This is automatically generated. Leave this field blank.'),
        ]);

        return $fields;
    }

    public function getLongAccessToken()
    {
        if ($this->PublicToken && $this->SecretToken && $this->ShortAccessToken) {
            $url = "https://graph.facebook.com/oauth/access_token?client_id=" . $this->PublicToken . "&client_secret=" . $this->SecretToken . "&grant_type=fb_exchange_token&fb_exchange_token=" . $this->ShortAccessToken;

            $client = new Client();
            $options = [CURLOPT_SSL_VERIFYPEER => false];
            $response = $client->request('GET', $url, $options);

            $feed = json_decode($response->getBody(), true);

            if (!isset($feed['access_token'])) {
                if (empty($feed)) {
                    user_error('Response empty. API may have changed.', E_USER_WARNING);
                    return;
                } else {
                    user_error('Facebook message error or API changed', E_USER_WARNING);
                    return;
                }
            } else {
                return $feed['access_token'];
            }
        } else {
            return;
        }
    }

    public function getPermanentAccessToken()
    {
        if ($this->Title && $this->LongAccessToken) {
            $url = "https://graph.facebook.com/me/accounts?access_token=" . $this->LongAccessToken;

            $service = new Client();
            $options = [CURLOPT_SSL_VERIFYPEER => false];
            $response = $service->request('GET', $url, $options);

            $facebook = json_decode($response->getBody(), true);

            if (!isset($facebook)) {
                if (empty($facebook)) {
                    user_error('Response empty. API may have changed.', E_USER_WARNING);
                    return;
                } else {
                    user_error('Facebook message error or API changed', E_USER_WARNING);
                    return;
                }
            } else {
                foreach ($facebook['data'] as $data) {
                    if ($data['id'] == $this->UserID) {
                        return $data['access_token'];
                    }
                }
            }
        }
    }
}
