<?php
namespace Drupal\praksa_movies\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PraksaMoviesForm extends ConfigFormBase
{

  public function getFormId()
  {
    return 'praksa_movies_form';
  }

  protected function getEditableConfigNames() {
    return ['praksa_movies.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('praksa_movies.settings');
    $form['contentPerPage'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Number of results per page: '),
      '#default_value' => $config->get('contentPerPage') ? $config->get('contentPerPage') : '1',
    ];
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $this->config('praksa_movies.settings')
    ->set('contentPerPage', $form_state->getValue('contentPerPage'))
    ->save();

    parent::submitForm($form, $form_state);
  }

  }