<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\DomCrawler\Crawler;

class ScrapeBiddings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrape {url-index : A key for a pre defined search or an URL for a new one} 
                            {--f|filter= : A CSS query for elements to be scraped}
                            {--html : Print in raw html format}
                            {--s|submit= : The title of a submit button of a form. The scraping will be performed on the returned page}
                            {--d|data= : A JSON string with key values pairs which represents the input names and values of the form}
                            {--a|amount : Just display the amount of results in a scalar value}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Scrape data from given URLs';

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
        }else{
            $crawler = $this->request( $url );
            if( $filter = $this->option('filter') ){
                $crawler = $crawler->filter($filter);
            }
            $this->response( $crawler );
        }
    }

    private function request( $url )
    {
        
        $crawler = \Goutte::request('GET', $url);
        $submit = $this->option('submit');
        if( $submit ){
            $form = $crawler->selectButton( $submit )->form();
            $data = json_decode($this->option('data'), true) ?? [];
            $crawler = (new Client())->submit($form, $data);
        }
        return $crawler;
    }

    private function out( Crawler $content )
    {
        if( $this->option('html') ){
            $this->line( $content->html() );
        }else{
            $this->line( $content->text() );
        }
    }

    private function response( Crawler $crawler )
    {
        $count = $crawler->count();
        if( $this->option('amount') ){
            $this->line( $count );
            exit();
        }

        if( $count > 1 ){
            $crawler->each(function($item){
                $this->out($item);
            });
        }else if( $count > 0 ){
            $this->out($crawler);
        }else{
            dd( $crawler );
        }
    }

    private function ecompras()
    {
        $crawler = \Goutte::request('GET', $this->urls['ecompras']);
        // TODO: understand what is that SSL issue and fix it
        // $goutteClient = new Client();
        // $guzzleClient = new GuzzleClient(['curl' => [CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false]]);
        
        // $crawler = $goutteClient->setClient($guzzleClient)->request('GET', $this->urls['ecompras']);
        dd($crawler);
        $crawler->filter('.borda-verde a.tribuchet-11-escuro')->each(function ($node) {
            $this->line($node->text());
        });
    }


    private function cnpq()
    {
        //http://www.cnpq.br/web/guest/licitacoes?p_p_id=licitacoescnpqportlet_WAR_licitacoescnpqportlet_INSTANCE_BHfsvMBDwU0V&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view&p_p_col_id=column-2&p_p_col_pos=1&p_p_col_count=2&pagina=" + pagina + "&delta=10" + "&registros=1500
        $crawler = \Goutte::request('GET', $this->urls['cnpq']);
        $pagination = $crawler->filter('ul.lfr-pagination-buttons li:nth-child(3)');
        $page = 0;

        $content = [];
        $bar = $this->output->createProgressBar(150);
        while( $crawler->filter('ul.lfr-pagination-buttons li:nth-child(3).disabled')->count() == 0 ){
            $biddings = $crawler->filter('.resultado-licitacao table tr div.licitacoes');
            $biddings->each(function($node) use(&$content){
                $title          = $node->filter('h4.titLicitacao')->text();
                $description    = $node->filter('.cont_licitacoes')->text();
                $starting       = $node->filter('.data_licitacao span:first-child')->text();
                $posts          = $node->filter('.data_licitacao span:last-child')->text();
                try{
                    $content[] = [$title, $description, $starting, $posts];
                }catch(Exception $er){
                    $this->error($er->message());
                    die();
                }
            });
            $bar->advance();
            $page++;
            $crawler = \Goutte::request('GET', 'http://www.cnpq.br/web/guest/licitacoes?p_p_id=licitacoescnpqportlet_WAR_licitacoescnpqportlet_INSTANCE_BHfsvMBDwU0V&p_p_lifecycle=0&p_p_state=normal&p_p_mode=view&p_p_col_id=column-2&p_p_col_pos=1&p_p_col_count=2&pagina='.$page.'&delta=10&registros=1500');
        }
        $bar->finish();
        $this->line("\n");
        $content = array_merge([['Licitação','Descrição', 'Abertura', 'Publicações']], $content);
        $this->write('licitações', $content);
        return;
    } 

    private function write( $filename, $content )
    {
        \Excel::create($filename, function($excel) use ($content){
            $excel->sheet('data', function($sheet) use ($content){
                $sheet->fromArray($content);
            });
        })->store('xls');
    }
}
