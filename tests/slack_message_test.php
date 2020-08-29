<?php

use Podlove\Modules\SlackShownotes\Message;

/**
 * @internal
 * @coversNothing
 */
class SlackMessageTest extends WP_UnitTestCase
{
    public function testExtractLinkMatchedWithAttachment()
    {
        $data = <<<'EOT'
{
  "client_msg_id": "a651c38b-2bc3-4ce3-8a48-2b131af24887",
  "type": "message",
  "text": "<https://freakshow.fm/fs228-letty-im-datenteich>",
  "user": "UF53JT12T",
  "ts": "1546257135.000400",
  "attachments": [
      {
          "service_name": "Freak Show",
          "title": "FS228 Letty im Datenteich",
          "title_link": "https://freakshow.fm/fs228-letty-im-datenteich",
          "text": "...",
          "fallback": "Freak Show: FS228 Letty im Datenteich",
          "thumb_url": "https://meta.metaebene.me/media/mm/freakshow-logo-1.0.jpg",
          "from_url": "https://freakshow.fm/fs228-letty-im-datenteich",
          "thumb_width": 1400,
          "thumb_height": 1400,
          "service_icon": "https://freakshow.fm/files/2013/07/cropped-freakshow-logo-600x600-180x180.jpg",
          "id": 1,
          "original_url": "https://freakshow.fm/fs228-letty-im-datenteich"
      }
  ]
}
EOT;

        $message = json_decode($data, true);
        $result = Message::extract_links($message);

        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]['link'], 'https://freakshow.fm/fs228-letty-im-datenteich');
        $this->assertEquals($result[0]['title'], 'FS228 Letty im Datenteich');
        $this->assertEquals($result[0]['source'], 'Freak Show');
    }

    public function testLinkWithMissingAttachment()
    {
        $data = <<<'EOT'
{
    "client_msg_id": "5510681d-1c33-41aa-a0ee-62779cd9e8ad",
    "type": "message",
    "text": "Link mit gecancelter expansion <http://www.spiegel.de/politik/ausland/kongos-regierung-kappt-das-internet-nach-praesidentenwahl-a-1245955.html>",
    "user": "UF53JT12T",
    "ts": "1546266336.001000"
}
EOT;

        $message = json_decode($data, true);
        $result = Message::extract_links($message);

        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]['link'], 'http://www.spiegel.de/politik/ausland/kongos-regierung-kappt-das-internet-nach-praesidentenwahl-a-1245955.html');
        $this->assertEquals($result[0]['title'], null);
        $this->assertEquals($result[0]['source'], 'spiegel.de');
    }

    public function testLinkWithPipes()
    {
        // when Slack detects something that looks like an URL it makes a canonical version
        // for example: golem.de is expanded to <https://golem.de|golem.de>
        $data = <<<'EOT'
{
    "client_msg_id": "5510681d-1c33-41aa-a0ee-62779cd9e8ad",
    "type": "message",
    "text": "Link mit pipe <https://golem.de|golem.de>",
    "user": "UF53JT12T",
    "ts": "1546266336.001000"
}
EOT;

        $message = json_decode($data, true);
        $result = Message::extract_links($message);

        $this->assertEquals(count($result), 1);
        $this->assertEquals($result[0]['link'], 'https://golem.de');
        $this->assertEquals($result[0]['title'], null);
        $this->assertEquals($result[0]['source'], 'golem.de');
    }

    public function testMultipleLinksWithAttachments()
    {
        $data = <<<'EOT'
{
  "client_msg_id": "7b5c8c33-be8f-4974-84ea-14ccbc4bc4aa",
  "type": "message",
  "text": "Ein Link hier <https://www.zeit.de/gesellschaft/zeitgeschehen/2018-12/vatikan-papst-franziskus-sprecher-ruecktritt> und da <https://www.zeit.de/2019/01/demokratieverdrossenheit-misstrauen-aufschwung-buerger-generationenkonflikt>",
  "user": "UF53JT12T",
  "ts": "1546277254.000300",
  "attachments": [
      {
          "service_name": "ZEIT ONLINE",
          "title": "Vatikan: Sprecher von Papst Franziskus treten zurück",
          "title_link": "https://www.zeit.de/gesellschaft/zeitgeschehen/2018-12/vatikan-papst-franziskus-sprecher-ruecktritt",
          "text": "...",
          "image_url": "https://img.zeit.de/gesellschaft/2018-12/pressesprecher-papst-ruecktritt-burke-ovejero/wide__1300x731",
          "image_width": 445,
          "image_height": 250,
          "from_url": "https://www.zeit.de/gesellschaft/zeitgeschehen/2018-12/vatikan-papst-franziskus-sprecher-ruecktritt",
          "image_bytes": 159999,
          "service_icon": "https://img.zeit.de/static/img/ZO-ipad-114x114.png",
          "id": 1,
          "original_url": "https://www.zeit.de/gesellschaft/zeitgeschehen/2018-12/vatikan-papst-franziskus-sprecher-ruecktritt"
      },
      {
          "service_name": "ZEIT ONLINE",
          "title": "Demokratieverdrossenheit: Warum trauen so viele der Demokratie nicht, obwohl wir einen Aufschwung erleben?",
          "title_link": "https://www.zeit.de/2019/01/demokratieverdrossenheit-misstrauen-aufschwung-buerger-generationenkonflikt",
          "text": "...",
          "image_url": "https://img.zeit.de/politik/2018-12/demokratie-misstrauen-aufschwung/wide__1300x731",
          "image_width": 445,
          "image_height": 250,
          "from_url": "https://www.zeit.de/2019/01/demokratieverdrossenheit-misstrauen-aufschwung-buerger-generationenkonflikt",
          "image_bytes": 141328,
          "service_icon": "https://img.zeit.de/static/img/ZO-ipad-114x114.png",
          "id": 2,
          "original_url": "https://www.zeit.de/2019/01/demokratieverdrossenheit-misstrauen-aufschwung-buerger-generationenkonflikt"
      }
  ]
}
EOT;

        $message = json_decode($data, true);
        $result = Message::extract_links($message);

        $this->assertEquals(count($result), 2);
        $this->assertEquals($result[0]['link'], 'https://www.zeit.de/gesellschaft/zeitgeschehen/2018-12/vatikan-papst-franziskus-sprecher-ruecktritt');
        $this->assertEquals($result[0]['title'], 'Vatikan: Sprecher von Papst Franziskus treten zurück');
        $this->assertEquals($result[1]['link'], 'https://www.zeit.de/2019/01/demokratieverdrossenheit-misstrauen-aufschwung-buerger-generationenkonflikt');
        $this->assertEquals($result[1]['title'], 'Demokratieverdrossenheit: Warum trauen so viele der Demokratie nicht, obwohl wir einen Aufschwung erleben?');
    }
}
