<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Storage;

class ScrapeBiddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape {url-index?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape data from given URLs';

    /**
    * A global variable to hold scraped data
    *
    * @var string
    */
    protected $content = '';

    /**
    * A list of known URLs to scrap.
    *
    * @var array
    */
    protected $urls = [
        'sebrae'    => 'www.scf2.sebrae.com.br/portalcf/iniciallicitacoes.aspx',
        'cnpq'      => 'http://www.cnpq.br/web/guest/licitacoes',
        'ecompras'  => 'https://www.compras.df.gov.br/publico/item_em_andamento.asp',
    ];

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $url = $this->argument('url-index');
        if($url && array_key_exists($url, $this->urls))
        {
            switch ($url) {
                case 'ecompras':
                    $this->ecompras();
                    break;
                case 'cnpq':
                    $this->cnpq();
                    break;
                default:
                    # code...
                    break;
            }
        }
    }

    private function ecompras()
    {
        $crawler = \Goutte::request('GET', $this->urls['ecompras']);
        // TODO: understand what is that SSL issue and fix it
        // $goutteClient = new Client();
        // $guzzleClient = new GuzzleClient(['curl' => [CURLOPT_SSLVERSION => CURL_SSLVERSION_MAX_DEFAULT]]);
        
        // $goutteClient->setClient($guzzleClient)->request('GET', $this->urls['cnpq']);
        // dd($crawler);
        $crawler->filter('.borda-verde a.tribuchet-11-escuro')->each(function ($node) {
            $this->line($node->text());
        });
    }

    private function cnpq()
    {
        //http://www.cnpq.br/web/guest/licitacoes?p_p_id=licitacoescnpqportlet_WAR_licitacoescnpqportlet_INSTANCE_BHfsvMBDwU0V&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view&p_p_col_id=column-2&p_p_col_pos=1&p_p_col_count=2&pagina=" + pagina + "&delta=10" + "&registros=1500
        $client = new Client();
        $crawler = $client->request('GET', $this->urls['cnpq']);
        $pagination = $crawler->filter('ul.lfr-pagination-buttons li:nth-child(3)');
        $page = 0;

        $this->content = '';
        $bar = $this->output->createProgressBar(150);
        while( $crawler->filter('ul.lfr-pagination-buttons li:nth-child(3).disabled')->count() == 0 ){
            $biddings = $crawler->filter('.resultado-licitacao table tr h4.titLicitacao');
            $biddings->each(function($node) use($client, $biddings){
                try{
                    $this->content .= $node->text() . "\n";
                }catch(Exception $er){
                    $this->error($er->message());
                    die();
                }
            });
            $bar->advance();
            $page++;
            $crawler = $client->request('GET', 'http://www.cnpq.br/web/guest/licitacoes?p_p_id=licitacoescnpqportlet_WAR_licitacoescnpqportlet_INSTANCE_BHfsvMBDwU0V&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view&p_p_col_id=column-2&p_p_col_pos=1&p_p_col_count=2&pagina='.$page.'&delta=10&registros=1500');
        }
        $bar->finish();
        $this->line("\n");
        Storage::put('licitações.txt', $this->content);
        return;
    } 
}
