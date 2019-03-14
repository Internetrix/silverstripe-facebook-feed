<?php

namespace Dexven\TokenConverter\Extensions;

use Dexven\TokenConverter\Model\FacebookFeed;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataExtension;
use GuzzleHttp\Client;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\ORM\FieldType\DBField;

class TokenControllerExtension extends DataExtension
{
    public function GetFacebookFeed($feedID)
    {
        $facebookFeed = FacebookFeed::get_by_id($feedID);

        if (count($facebookFeed) != 0) {
            $url = 'https://graph.facebook.com/v3.2/' . $facebookFeed->UserID . '/feed?fields=from,permalink_url,full_picture,message,created_time&limit=50&access_token=' . $facebookFeed->PermanentAccessToken;

            $client = new Client();
            $options = [CURLOPT_SSL_VERIFYPEER => false];

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
                        'User' => $data['from']['name'],
                        'Link' => "https://www.facebook.com/{$data['from']['id']}/posts/{$objectid}",
                        'ProfileLink' => 'https://www.facebook.com/' . $facebookFeed->UserID,
                        'Image' => isset($data['full_picture']) ? $data['full_picture'] : '',
                        'Message' => DBField::create_field('Text', $data['message']),
                        'Posted' => DBField::create_field(
                            'Datetime',
                            $posted['year'] . '-' . $posted['month'] . '-' . $posted['day'] . ' ' . $posted['hour'] . ':' . $posted['minute'] . ':' . $posted['second']
                        ),
                    ]));
                }

                return $posts;
            }
        }
    }
}
