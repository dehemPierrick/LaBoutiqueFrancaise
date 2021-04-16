<?php
namespace App\Classe;

use Mailjet\Client;
use Mailjet\Resources;

class Mail
{
    // clé API de mailjet 
    private $api_key = "";
    private $api_key_secrets = "";

    public function send($to_email, $to_name, $subject, $content)
    {
        $mj = new Client($this->api_key, $this->api_key_secrets,true,['version' => 'v3.1']);
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "pierrickdehem.developpement@gmail.com",
                        'Name' => "Pierrick DEHEM Développement"
                    ],
                    'To' => [
                        [
                            'Email' => $to_email,
                            'Name' => $to_name
                        ]
                    ],
                    'TemplateID' => 2770438,
                    'TemplateLanguage' => true,
                    'Subject' => $subject,
                    'Variables' => [
                        'content' => $content
                    ]
                ]
            ]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);
        $response->success();
    }
}