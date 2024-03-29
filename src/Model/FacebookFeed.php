<?php

namespace Internetrix\FacebookFeed\Model;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use GuzzleHttp\Client;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\View\ArrayData;

/**
 * Class FacebookFeed
 * @package Internetrix\FacebookFeed\Model
 */
class FacebookFeed extends DataObject
{
    private static $db = [
        'Title' 		        => 'Varchar(255)',
        'UserID'		        => 'Varchar(50)',
        'ShortAccessToken'      => 'Text',
        'LongAccessToken'       => 'Text',
        'PermanentAccessToken'  => 'Text',
        'PublicToken'           => 'Text',
        'SecretToken'           => 'Text',
        'RegenerateToken'       => 'Boolean'
    ];

    private static $table_name = 'IRX_FacebookFeed';

    private static $summary_fields = [
        'Title' 	            => 'Feed',
        'UserID'                => 'User ID'
    ];

    private static $defaults = [
        'RegenerateToken' 	    => true
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Parent');

        $fields->addFieldToTab('Root.Main', LiteralField::create('DetailsHeader', '<h1>App Details</h1>'), 'Title');
        $fields->addFieldToTab('Root.Main', LiteralField::create('Description', '<p class="message">These details can be acquired
        by creating an app on Facebook. Do so here: <a href="https://developers.facebook.com/apps/">developers.facebook.com/apps/</a><br>'), 'Title');

        $fields->addFieldsToTab('Root.Main', [
            TextField::create('Title'),
            TextField::create('UserID')->setDescription('The ID of the page you are accessing. Not the name.'),

        ]);

        $fields->addFieldsToTab('Root.Main', [
            TextField::create('PublicToken'),
            TextField::create('SecretToken')
        ], 'ShortAccessToken');

        $fields->addFieldToTab('Root.Main', LiteralField::create('TokenHeader', '</br><h1>Access Tokens</h1>'), 'ShortAccessToken');
        $fields->addFieldToTab('Root.Main', LiteralField::create('Warning', '<p class="message warning">The Short Access Token has a limit of 2 hours, 
        while the Long Access Token has a limit of 2 months. Make sure to replace them when you need to regenerate the tokens.<br>'), 'ShortAccessToken');

        $fields->addFieldsToTab('Root.Main', [
            TextareaField::create('ShortAccessToken'),
            TextareaField::create('LongAccessToken')->setDescription('This is automatically generated.'),
            TextareaField::create('PermanentAccessToken')->setDescription('This is automatically generated.'),
            CheckboxField::create('RegenerateToken', 'Regenerate Token')
        ]);

        $fields->addFieldToTab('Root.Main', LiteralField::create('CheckWarning', '<p class="message warning">Check this box each time you wish to regenerate the Long and Permanent tokens.'), 'RegenerateToken');

        return $fields;
    }

    /**
     * Ensures the tokens are generated with the currently saved details
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if ($this->RegenerateToken == true) {
            $this->LongAccessToken = $this->CreateLongAccessToken();
            $this->PermanentAccessToken = $this->CreatePermanentAccessToken();
            $this->RegenerateToken = false;
        }
    }

    /**
     * Creates a long lived token (lifespan of 60 days)
     */
    public function CreateLongAccessToken()
    {
        if ($this->ID && $this->PublicToken && $this->SecretToken && $this->ShortAccessToken) {
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
        }

        return null;
    }

    /**
     * Creates a permanent access token (no expiry)
     */
    public function CreatePermanentAccessToken()
    {
        if ($this->ID && $this->LongAccessToken) {
            $url = "https://graph.facebook.com/" . $this->UserID . "?fields=access_token&access_token=" . $this->LongAccessToken;

            $service = new Client();
            $options = [CURLOPT_SSL_VERIFYPEER => false];
            $response = $service->request('GET', $url, $options);

            $feed = json_decode($response->getBody(), true);

            if (!isset($feed)) {
                if (empty($feed)) {
                    user_error('Response empty. API may have changed.', E_USER_WARNING);
                    return;
                } else {
                    user_error('Facebook message error or API changed', E_USER_WARNING);
                    return;
                }
            } else {
                if ($feed['access_token']) {
                    return $feed['access_token'];
                }
            }
        }

        return null;
    }

    public function getFeed($limit = null)
    {
        $url = 'https://graph.facebook.com/v4.0/' . $this->UserID . '/feed?fields=from,permalink_url,full_picture,message,created_time,place,to&limit=50&access_token=' . $this->PermanentAccessToken;

        $this->extend('updateFeedFields', $url, $this);

        $client = new Client();
        $options = [
            CURLOPT_SSL_VERIFYPEER => false
        ];

        try {
            $response = $client->request('GET', $url, $options);
        }
        catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = $e->getResponse();
            $responseString = $response->getBody()->getContents();
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
            $response = $e->getResponse();
            $responseString = $response->getBody()->getContents();
        }

        $feed = json_decode($response->getBody(), true);

        if (!isset($feed['data'])) {
            if (empty($feed)) {
                //user_error('Response empty. API may have changed.', E_USER_WARNING);
                echo "<script>console.log( 'Response empty. API may have changed.' );</script>";
                return;
            } else {
                //user_error('Facebook message error or API changed', E_USER_WARNING);
                echo "<script>console.log( 'Facebook message error or API changed.' );console.log(" . $responseString . ");</script>";
                return;
            }
        } else {
            $posts = ArrayList::create();

            foreach ($feed['data'] as $data) {
                if (!isset($data['id']) || !isset($data['message'])) {
                    continue;
                }

                $posted = date_parse($data['created_time']);
                $objectid = preg_replace("/^.*?_/i", "", $data['id']);

                $posts->push(ArrayData::create([
                    'User'          => $data['from']['name'],
                    'Link'          => "https://www.facebook.com/{$data['from']['id']}/posts/{$objectid}",
                    'ProfileLink'   => 'https://www.facebook.com/' . $this->UserID,
                    'Image'         => isset($data['full_picture']) ? $data['full_picture'] : '',
                    'Message'       => DBField::create_field('Text', $data['message']),
                    'Posted'        => DBField::create_field(
                        'Datetime',
                        $posted['year'] . '-' . $posted['month'] . '-' . $posted['day'] . ' ' . $posted['hour'] . ':' . $posted['minute'] . ':' . $posted['second']
                    ),
                ]));

                $this->extend('updateFeedPosts', $posts, $feed);
            }

            if ($limit) {
                return $posts->limit($limit);
            } else {
                return $posts;
            }
        }
    }
}
