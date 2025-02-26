<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use SimpleXMLElement;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;

class NewsController extends Controller
{
	
    public static function fetchNews()
    {
        $rssUrl = "https://www.trthaber.com/sondakika_articles.rss";
        $newsItems = [];

        $response = Http::withHeaders([
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
])->timeout(10)->get($rssUrl);

        if ($response->successful()) {
            try {
                $xml = new SimpleXMLElement($response->body());

                if (isset($xml->channel->item)) {
                    foreach ($xml->channel->item as $item) {

                        $newsItems[] = [
                            'title'       => (string) $item->title,
                            'link'        => (string) $item->link,
                            'description' => strip_tags((string) $item->description),
                            'pubDate'     => (string) ($item->pubDate ?? ''),
                        ];
                    }
                }
            } catch (\Exception $e) {
                return [];
            }
        }

        // ✅ En fazla 5 haber al
        return array_slice($newsItems, 0, 5);
    }

    public static function fetchMalatyaNews()
    {
        $url = "https://www.malatyanethaber.com.tr/rss/haberler/gundem/";
        $response = Http::get($url);

        if ($response->failed()) {
            return [];
        }

        $xml = new SimpleXMLElement($response->body());
        $newsItems = [];

        foreach ($xml->channel->item as $item) {
            $title = (string) $item->title;
            $link = (string) $item->link;
            $description = (string) $item->description;
            $image = null;

            // ✅ Eğer resim varsa çekelim
            if (isset($item->enclosure) && $item->enclosure['url']) {
                $image = (string) $item->enclosure['url'];
            }

            $newsItems[] = [
                'title'       => $title,
                'link'        => $link,
                'description' => strip_tags($description),
                'image'       => $image
            ];
        }

        // ✅ En fazla 5 haber döndür
        return array_slice($newsItems, 0, 5);
    }

    public static function fetchDoganSehirNews()
    {
        $url = "https://www.44medya.com/rss/son-dakika-dogansehir-haberleri";
        $response = Http::get($url);

        if ($response->failed()) {
            return [];
        }

        $xml = new SimpleXMLElement($response->body());
        $newsItems = [];

        foreach ($xml->channel->item as $item) {
            $title = (string) $item->title;
            $link = (string) $item->link;
            $description = (string) $item->description;
            

            $newsItems[] = [
                'title'       => $title,
                'link'        => $link,
                'description' => strip_tags($description),
            ];
        }

        // ✅ En fazla 5 haber döndür
        return array_slice($newsItems, 0, 5);
    }
	
	
}
