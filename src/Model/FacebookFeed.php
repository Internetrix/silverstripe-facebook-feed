<?php

namespace Dexven\KeyConverter\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;

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
        'ID'                    => 'ID',
        'Title' 	            => 'Feed',
        'UserID'                => 'User ID'
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Title'),
            TextField::create('UserID'),
            TextField::create('PublicToken'),
            TextField::create('SecretToken'),
            TextareaField::create('ShortAccessToken'),
            TextareaField::create('LongAccessToken'),
            TextareaField::create('PermanentAccessToken'),
        ]);

        return $fields;
    }

    public function getLongAccessToken()
    {
        if ($this->UserID && $this->SecretToken && $this->ShortAccessToken) {
            $url = "https://graph.facebook.com/oauth/access_token?client_id=" . $this->UserID . "&client_secret=" . $this->SecretToken . "&grant_type=fb_exchange_token&fb_exchange_token=" . $this->ShortAccessToken;

            $client = new GuzzleHttp\Client();
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

            $service = new GuzzleHttp\Client();
            $options = [CURLOPT_SSL_VERIFYPEER => false];
            $response = $service->request('GET', $url, $options);

            $facebook = json_decode($response->getBody(), true);

            if (!isset($facebook['data'])) {
                if (empty($facebook)) {
                    user_error('Response empty. API may have changed.', E_USER_WARNING);
                    return;
                } else {
                    user_error('Facebook message error or API changed', E_USER_WARNING);
                    return;
                }
            }
        }
    }
}