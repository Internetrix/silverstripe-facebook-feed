<?php

namespace Dexven\TokenConverter\Extensions;

use Dexven\TokenConverter\Model\FacebookFeed;
use SilverStripe\ORM\DataExtension;
use GuzzleHttp\Client;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\FieldType\DBField;

class TokenControllerExtension extends DataExtension
{
    public function getFacebookFeed($feedID)
    {
        $facebookFeed = FacebookFeed::get()->filter(['ID' => $feedID]);

        if ($facebookFeed) {
            $url = 'https://graph.facebook.com/v3.2/' . $facebookFeed->UserID . '/feed?fields=from,permalink_url,full_picture,message,created_time&limit=50&access_token=' . $facebookFeed->PermanentAccessToken;

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
                $posts = ArrayList::create();

                foreach ($feed['data'] as $data) {

                    if (!isset($post['id']) || !isset($post['message'])) {
                        continue;
                    }

                    $objectid = preg_replace("/^.*?_/i", "", $data['id']);

                    $posts->push(ArrayData::create([
                        'User' => $data['from']['name'],
                        'Link' => "https://www.facebook.com/{$data['from']['id']}/posts/{$objectid}",
                        'Image' => isset($data['full_picture']) ? $data['full_picture'] : '',
                        'Message' => DBField::create_field('Text', $data['message']),
                    ]));
                }

                return $posts;
            }
        } else {
            user_error('No feed exists with that ID.', E_USER_WARNING);
            return;
        }
    }
}