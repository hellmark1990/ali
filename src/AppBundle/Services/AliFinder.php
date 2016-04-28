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

    /**
     * @param Container $container
     */
    public function __construct($container){
        $this->service_container = $container;
        $this->buzz = $this->service_container->get('buzz');
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

            dump($productContent);
            exit;
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


    public function curlSend($url){

        $proxies = array(); // Declaring an array to store the proxy list

// Adding list of proxies to the $proxies array
        $proxies[] = 'user:password@173.234.11.134:54253';  // Some proxies require user, password, IP and port number
        $proxies[] = 'user:password@173.234.120.69:54253';
        $proxies[] = 'user:password@173.234.46.176:54253';
        $proxies[] = '173.234.92.107';  // Some proxies only require IP
        $proxies[] = '173.234.93.94';
        $proxies[] = '173.234.94.90:54253'; // Some proxies require IP and port number
        $proxies[] = '69.147.240.61:54253';

        // Choose a random proxy
        if (isset($proxies)) {  // If the $proxies array contains items, then
            $proxy = $proxies[array_rand($proxies)];    // Select a random proxy from the array and assign to $proxy variable
        }
        
        $ch = curl_init();  // Initialise a cURL handle

// Setting proxy option for cURL
        if (isset($proxy)) {    // If the $proxy variable is set, then
            curl_setopt($ch, CURLOPT_PROXY, $proxy);    // Set CURLOPT_PROXY with proxy in $proxy variable
        }

// Set any other cURL options that are required
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_COOKIESESSION, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_URL, $url);

        $results = curl_exec($ch);  // Execute a cURL request
        curl_close($ch);    // Closing the cURL handle

        return $results;
    }


}
