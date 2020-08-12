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
        $numberOfPages;
        //$numberOfPages = $this->request->get('page'); //request na svoj page, default je page
        $numberOfContentPerPage = $this->configFactory->get('contentPerPage');

        $offset = $numberOfPages * $numberOfContentPerPage;

        $searchfield = !empty($this->requestStack->get('search_field')) ? $this->requestStack->get('search_field') : '';

        $ids = $this->getMovieId($searchfield);
        $movieslist = $this->loadMovieList($ids);

        return array(
            '#theme' => 'praksa_movies',
            '#movieslist' => $movieslist
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
                'image' => $data->get('field_movie_image')->entity->uri->value
            );
        }

        return $movies;

    }
    /*Uzimam ID filmova. Posaljem neku vrstu upita u bazu, tj entity query sa odredjenim uslovima posaljem upit u bazu*/
    private function getMovieId($searchfield) {
        $query_result = [];
        
        if(!empty($searchfield)) {
            $query_result = $this->entityQuery->get('node')
            ->condition('type', 'movies')
            ->condition('title', $searchfield, 'CONTAINS')
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

}