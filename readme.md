# nr-challenge

This is a basic web scraper developed in a laravel command


## Requirements

laravel 5.3 
PHP between 5.6.4 & 7.1.*

## Installation

Simply run

    git clone https://github.com/kaabsimas/nr-challenge.git
    cd nr-challenge
    composer install

## Switch to another file

All your files are listed in the file explorer. You can switch from one to another by clicking a file in the list.

## Usage

Type
	
	php artisan scrape cnpq

For now it's all, it gonna crawl into all pages of http://www.cnpq.br/web/guest/licitacoes and save it's biddings data into an excel file inside storage/export

Soon it'll have the sebrae and ecompras options, to search through www.scf2.sebrae.com.br/portalcf/iniciallicitacoes.aspx and https://www.compras.df.gov.br/publico/item_em_andamento.asp respectivelly. Future plans are to provide an URL and page specific informations to make the crawl.