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

## Usage

Type:

    php artisanscrape https://kaabsimas.github.io
This code should print on console the raw text of given url. To filter with an CSS Quer, use the `-f` or `--filter` option:

    php artisan scrape https://kaabsimas.github.io -f ".nav-item"
	#it returns

       Sobre
       Experiência
       Habilidades
       Interesses

If you wish to get raw HTML code include the `--html` option

    php artisan scrape https://kaabsimas.github.io -f ".nav-item" --html
    #which returns
    <a class="nav-link js-scroll-trigger" href="#about">Sobre</a>
    <a class="nav-link js-scroll-trigger" href="#experience">Experiência</a>
	<a class="nav-link js-scroll-trigger" href="#skills">Habilidades</a>
    <a class="nav-link js-scroll-trigger" href="#interests">Interesses</a>

## Forms
The scrape command has also two options to perform your search in a returned page from a submited form:

    php artisan scrape http://www.scf2.sebrae.com.br/portalcf/ -f '.form-searchresult' -s 'Pesquisar' -d '{"ctl00$ContentPlaceHolder1$ddSituacao":2, "ctl00$ContentPlaceHolder1$ddModalidade":6, "ctl00$ContentPlaceHolder1$ddAno":2018}'

Here we use the `-s` or `--submit` option with the value of a submit button, 'Pesquisar'.
The `-d` or `--data` option is used to provide the form input values, in the shape of an valid JSON object. 


Next plans are to implement javascript execution, to run through paginations where it is required, and support specific url configured classes with custom methods. 