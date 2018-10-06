<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

use Behat\Mink\Element\ElementInterface;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Element\DocumentElement;

use \App\Components\Scraper\Scraper;
use \App\Components\Scraper\Configuration;

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
    * The scraper instance
    *
    * @var \App\Components\Scraper\Scraper
    */
    private $scraper;

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
        $this->scraper = new Scraper( new Configuration( $this->options() ) );

        $url = $this->argument('url-index');
        
        $page = $this->request( $url );
        if( $filter = $this->option('filter') ){
            $page = $page->findAll('css', $filter);
        }
        $this->response( $page );
    }

    private function request( $url ): DocumentElement
    {
        
        $page = $this->scraper->visit($url);
        $submit = $this->option('submit');
        if( $submit ){
            $forms = $page->findAll('css', 'form');
            $form = array_map(function($form) use ($submit){
                if( $form->has('css', "input[type=\"submit\"][value=\"{$submit}\"]") )
                    return $form;
            }, $forms)[0];

            $data = json_decode($this->option('data'), true) ?? [];
            foreach( $data as $input => $value ){
                $input = $form->findField( $input );
                $input->setValue( $value );
            }
            $page = $this->scraper->submit($form);
            dd( $page );
            die();
        }
        return $page;
    }

    private function out( ElementInterface $content )
    {
        if( $this->option('html') ){
            $this->line( $content->getOuterHtml() );
        }else{
            $this->line( $content->getText() );
        }
    }

    private function response( $element )
    {
        $count = count($element);
        if( $this->option('amount') ){
            $this->line( $count );
            exit();
        }

        if( $count > 0 ){
            foreach( $element as $item){
                $this->out($item);
            };
        }else{
            dd( $element );
        }
    }

    /*private function ecompras()
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
    }*/


    /*private function cnpq()
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
    } */

    private function write( $filename, $content )
    {
        \Excel::create($filename, function($excel) use ($content){
            $excel->sheet('data', function($sheet) use ($content){
                $sheet->fromArray($content);
            });
        })->store('xls');
    }
}
