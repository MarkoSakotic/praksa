<?php


namespace Drupal\praksa_movies\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\Entity\Query\EntityManager; 
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Config\ConfigFactory;


class MoviesController extends ControllerBase{

    protected $entityQuery;
    protected $entityTypeManager;
    protected $requestStack;
    protected $configFactory;

    /*
    Konstruktor sa parametrima, pre toga kreiram protected varijable koje se odnose na te entity query, koje prosledim tu,
    DI se odnosi na te protected varijable
    config_factory getujem ime polja
    */
    public function __construct($entityQuery, $entityTypeManager, RequestStack $requestStack, ConfigFactory $config_factory) {
        $this->entityQuery = $entityQuery;
        $this->entityTypeManager = $entityTypeManager;
        $this->requestStack = $requestStack->getCurrentRequest();
        $this->configFactory = $config_factory->get('praksa_movies.settings');

    }

    public static function create(ContainerInterface $container) {
        return new static(
            $container->get('entity.query'),
            $container->get('entity_type.manager'),
            $container->get('request_stack'),
            $container->get('config.factory')
        );
    
    } 

    public function movies() {
        
        $searchfield; //koristim request stack, pozivam request, dodam u konstruktor
        $offset;
        $numberOfPages = $this->requestStack->get('page'); //request na svoj page, iz twiga je to page u hrefu formiras i tu uhvatis,default je page
        $numberOfContentPerPage = $this->configFactory->get('contentPerPage');
        $currentFilter = !empty($this->requestStack->get('filter')) ? $this->requestStack->get('filter') : ''; //konkatenacija url, formiram jednu funkciju koja ce da izboriji sve filmove, tipa getmoviesId, 


        $offset = $numberOfPages * $numberOfContentPerPage;

        $searchfield = !empty($this->requestStack->get('searchfield')) ? $this->requestStack->get('searchfield') : '';

        $ids = $this->getMovieId($searchfield, $numberOfContentPerPage, $currentFilter, $offset);
        $movieslist = $this->loadMovieList($ids);
        $genrelist = $this->getGenres();
        $total = $this->getTotal($searchfield, $numberOfContentPerPage, $currentFilter);

        return array(
            '#theme' => 'praksa_movies',
            '#movieslist' => $movieslist,
            '#genre' => $genrelist,
            '#pager' => [
                'currentFilter' => $currentFilter,
                'total' => $total
            ],
        );
    }

    /*
    Ova f-ja mi je da učitam filmove, na osnovu ID-a
    */
    private function loadMovieList($ids) {
        $moviesList = $this->entityTypeManager->getStorage('node')->loadMultiple($ids);
       
        return $this->getMoviesData($moviesList);
    
    }

    /*
    Uz pomoć ove f-je uzimam podatke o filmovima i stavljam njihov title, description i image u niz movies[] 
    foreach works only on arrays
    */
    private function getMoviesData($moviesData) {

        $movies = [];

        foreach($moviesData as $data){
            
            $movies[] = array(
                'title' => $data->title->value,
                'description' => $data->field_description->value,
                'image' => $data->get('field_movie_image')->entity->uri->value,
                'genre' => $data->get('field_movie_genre')->entity->getName()
            );
        }

        return $movies;

    }
    /*
    Uzimam ID filmova. Posaljem neku vrstu upita u bazu,
    tj entity query sa odredjenim uslovima posaljem upit u bazu
    */
    private function getMovieId($searchfield, $numberOfContentPerPage, $genre, $offset) {
       
        $query_result = [];
        
        if(!empty($searchfield) && !empty($genre)) {
            $query_result = $this->entityQuery->get('node')
            ->condition('type', 'movies')
            ->condition('title', $searchfield, 'CONTAINS')
            ->condition('field_movie_genre', $genre) //da li u field genre da li postoji taj $genre
            ->range($offset, $numberOfContentPerPage)
            ->execute();
        }
        else if(!empty($searchfield) && empty($genre)) {
            $query_result = $this->entityQuery->get('node')
            ->condition('type', 'movies')
            ->condition('title', $searchfield, 'CONTAINS')
            ->range($offset, $numberOfContentPerPage)
            ->execute();
        }
        else if(empty($searchfield) && !empty($genre)) {
            $query_result = $this->entityQuery->get('node')
            ->condition('type', 'movies')
            ->condition('field_movie_genre', $genre)
            ->range($offset, $numberOfContentPerPage)
            ->execute();
        }
        else {
            $query_result = $this->entityQuery->get('node')
            ->condition('type', 'movies')
            ->range($offset, $numberOfContentPerPage)
            ->execute();
        }

        return $query_result;

    }

    /*
    jedna private fja da getujem zanrove, ulazim u
     taksonomiju da uzmem sve te version id, onda ih stavljam u odredjen niz,
    tj array, gde uzimam njihov id i name, i to sacuvam da bih koristio kasnije
    */
    private function getGenres() {

        $vid = 'genre'; //Vocabulary ID to retrieve terms for.
        $terms = $this->entityTypeManager()->getStorage('taxonomy_term')->loadTree($vid);
        $term_data = [];
        foreach ($terms as $term) {
            $term_data[] = array(
                'id' => $term->tid,
                'name' => $term->name
            );
        }

        return $term_data;
    }
    
    /*
    count broji sve filmove, getTotal(),  count izboriji filmove po uslovima, getTotal  rasporedi filmove od 0 do totala,
    ako je broj filmova manji od 1 vrati to, inace zaokruzi ceil funkcija
    */
    private function getTotal($searchfield, $numberOfContentPerPage, $genre) {

        $total_result = [];
        
        if(!empty($searchfield) && !empty($genre)) {
            $total_result = $this->entityQuery->get('node')
            ->condition('type', 'movies')
            ->condition('title', $searchfield, 'CONTAINS')
            ->condition('field_movie_genre', $genre) //da li u field genre da li postoji taj $genre
            ->count() //For count queries, execute() returns the number entities found
            ->execute();
        }
        else if(!empty($searchfield) && empty($genre)) {
            $total_result = $this->entityQuery->get('node')
            ->condition('type', 'movies')
            ->condition('title', $searchfield, 'CONTAINS')
            ->count()
            ->execute();
        }
        else if(empty($searchfield) && !empty($genre)) {
            $total_result = $this->entityQuery->get('node')
            ->condition('type', 'movies')
            ->condition('field_movie_genre', $genre)
            ->count()
            ->execute();
        }
        else {
            $total_result = $this->entityQuery->get('node')
            ->condition('type', 'movies')
            ->count()
            ->execute();
        }

        if(($total_result / $numberOfContentPerPage <= 1)) {
            return $total_result / $numberOfContentPerPage;
        }
        else {
            return ceil($total_result / $numberOfContentPerPage);
        }

    }

}