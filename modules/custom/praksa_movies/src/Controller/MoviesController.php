<?php


namespace Drupal\praksa_movies\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route; 



class MoviesController extends ControllerBase{

    public function movies() {

        $items = array('Article one', 'Article two', 'Article 3');
            //'name' => 'Article one',
            //'name' => 'Article two',
            //'name' => 'Article three'
        //);

        return array(
            '#theme' => 'praksa_movies',
            '#items' => $items,
            '#title' => 'Our article list'
        );

        /*
        return array(
            '#title' => 'Welcome!',
            '#markup' => 'This is some content!',
            '#theme' => 'praksa_movies'
        );*/
    }

}