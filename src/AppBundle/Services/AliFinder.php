<?php

namespace AppBundle\Services;

use Buzz\Browser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

/**
 * Created by PhpStorm.
 * User: mark
 * Date: 28.04.16
 * Time: 13:21
 */
class AliFinder {

    /**
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    private $service_container;

    /**
     * @var Browser object
     */
    private $buzz;

    private $propertyName = 'Диапазон ачх';
    private $propertyValue = '20 - 20000 Гц';
    private $productsResult = [];
    public $cookie = 'cookies.txt';

    /**
     * @param Container $container
     */
    public function __construct($container){
        $this->service_container = $container;
        $this->buzz = $this->service_container->get('buzz');
        $this->cookie = __DIR__ . '/' . $this->cookie;
    }


    public function find($categoryUrl, $data = []){
        $productsURL = [];

        $this->buzz->getClient()->setTimeout(0);
        $categoryContent = $this->buzz->get($categoryUrl)->getContent();

        $crawler = new Crawler();
        $crawler->addHtmlContent($categoryContent);

        $perPageCount = 0;
        $totalProductsCount = (int)str_replace(',', '', $crawler->filter('strong.search-count')->first()->text());

        $crawler->filter('a.product')->each(function (Crawler $element) use (&$productsURL, &$perPageCount){
            $productsURL[] = $element->attr('href');
            $perPageCount++;
        });

        $pagesCount = (int)ceil($totalProductsCount / $perPageCount);
        $paginationLink = $crawler->filter('.ui-pagination-navi > a')->first()->attr('href');

//        for ($i = 2; $i <= $pagesCount; $i++) {
//            $paginationLinkToPage = str_replace('2.html', $i . '.html', $paginationLink);
//            $categoryPageContent = $this->buzz->get($paginationLinkToPage)->getContent();
//
//            $crawler = new Crawler();
//            $crawler->addHtmlContent($categoryPageContent);
//            $pageProductsCount = (int)str_replace(',', '', $crawler->filter('strong.search-count')->first()->text());
//
//            $crawler->filter('a.product')->each(function (Crawler $element) use (&$productsURL, &$perPageCount){
//                $productsURL[] = $element->attr('href');
//            });
//
//            if ($i == 3) {
//                break;
//            }
//
//            if (!$pageProductsCount) {
//                break;
//            }
//        }

        $this->processProducts($productsURL);

        dump($this->productsResult);
        exit;
    }


    public function processProducts($productsURL){
        foreach ($productsURL as $productUrl) {
            $productContent = $this->curlSend($productUrl);

//            $productContent = shell_exec('curl --header "X-Forwarded-For: 1.2.3.4" "' . $productUrl . '""');

//            dump($productContent);
//            exit;
            echo $productContent;
            exit;

            $crawler = new Crawler();
            $crawler->addHtmlContent($productContent);

            $crawler->filter('span.propery-title')->each(function (Crawler $property) use ($productUrl){
                $propertyName = str_replace(':', '', $property->text());

                if ($propertyName == $this->propertyName) {
                    $propertyValue = $property->nextAll()->first()->text();
//                    if($propertyValue == $this->propertyValue){
                    $this->productsResult[] = $productUrl;
                    dump($this->productsResult);
                    exit;
//                    }
                }
            });

        }

    }


    private function curlSend($url){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); // возвращает веб-страницу
        curl_setopt($ch, CURLOPT_HEADER, 0); // не возвращает заголовки
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1); // переходит по редиректам
        curl_setopt($ch, CURLOPT_ENCODING, ""); // обрабатывает все кодировки
        curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // useragent
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); // таймаут соединения
        curl_setopt($ch, CURLOPT_TIMEOUT, 120); // таймаут ответа
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10); // останавливаться после 10-ого редиректа
        curl_setopt($ch, CURLOPT_COOKIESESSION, 1);
        curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $this->cookie);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $this->cookie);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        $res = curl_exec($ch);
        curl_close($ch);

        return $res;
    }


}
