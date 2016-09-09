use Guzzle\Http\Client;
use Symfony\Component\DomCrawler\Crawler;

Route::get('parseOlx', function () {
    $search_keywords = ['samsung', 'iphone','lenovo','Fly'];
    function searchSimilar($text, $keywords)
    {
        $found = false;
        for ($x = 0, $count = count($keywords); $x < $count; $x++) {
            if (strripos(strtolower($text), strtolower($keywords[$x]))) {
                $found = true;
//                break;
            }

        }
        return $found;
    }

    $client = new Client('http://www.olx.ua');
    $pages = [
        '/elektronika/telefony/mobilnye-telefony/kiev/?search%5Border%5D=created_at%3Adesc&view=list',
        '/elektronika/telefony/mobilnye-telefony/kiev/?search%5Border%5D=created_at%3Adesc&view=list&page=2',
        '/elektronika/telefony/mobilnye-telefony/kiev/?search%5Border%5D=created_at%3Adesc&view=list&page=3',
//        '/elektronika/telefony/mobilnye-telefony/kiev/?search%5Border%5D=created_at%3Adesc&view=list&page=4',
    ];
    dump("Products viewed for all time -" . count(session()->get('olx_products')));
    $result = array();
    $totalFound = 0;
    $totalProducts = 0;

    foreach ($pages as $page) {
        $request = $client->get($page);
        $response = $request->send();
        $crawler = new Crawler($response->getBody(true));
        $filter = $crawler->filter('#offers_table td.offer');

        $session_olx_products = session()->get('olx_products') ?: [];

        if (iterator_count($filter) > 1) {

            // iterate over filter results

            foreach ($filter as $i => $content) {

                // create crawler instance for result
                $crawler = new Crawler($content);

                // extract the values needed

                $id = $crawler->filter('.breakword')->attr('data-id');
                $title = $crawler->filter('.detailsLink strong')->text();
                $found = searchSimilar($title, $search_keywords);
//                dump($title);

                if ($found && in_array($id, $session_olx_products) == false) {
                    $session_olx_products[] = $id;
                    session()->put('olx_products', $session_olx_products);

                    $img = $crawler->filter('.thumb .fleft');
                    $result[$i] = array(
                        'id' => $id,
                        'title' => $title,
                        'link' => $crawler->filter('.detailsLink')->attr('href'),
                        'img' => ($img ? $img->attr('src') : null),
                        'price' => $crawler->filter('p.price strong')->text(),
                    );

                    $totalFound++;
                }
                $totalProducts++;

            }
        } else {
            throw new RuntimeException('Got empty result processing the dataset!');
        }
    }
    dump('Search keywords - ' . implode(',', $search_keywords));
    dump('Found products that matches query - ' . $totalFound);
    dump('Total products - ' . $totalProducts);
    if($totalFound){
        // SEND TO TELEGRAM BOT
    }
    dump($result);


});
