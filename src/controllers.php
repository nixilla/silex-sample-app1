<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints as Assert;

$app->get('/', function () use ($app) {
    return $app['twig']->render('index.html', array());
})
->bind('homepage')
;

$app->match('/contact', function (Request $request) use ($app) {

  $form = $app['form.factory']->createBuilder('form')
    ->add('name', 'text', array(
      'constraints' => [new Assert\NotBlank(), new Assert\Length(array('min' => 5))]
    ))
    ->add('email', 'text', array(
      'constraints' => [new Assert\NotBlank(), new Assert\Email()]
    ))
    ->add('gender', 'choice', array(
      'choices' => array(1 => 'male', 2 => 'female'),
      'expanded' => true,
      'constraints' => new Assert\Choice(array(1, 2)),
    ))
    ->getForm();

  if ('POST' == $request->getMethod()) {
    $form->bind($request);

    if ($form->isValid()) {
      $data = $form->getData();

      try
      {
        $app['db']->insert('contact', [ 'name' => $data['name'], 'email' => $data['email']]);
      }
      catch(PDOException $e)
      {
        $app['db']->query('CREATE TABLE contact (name VARCHAR, email VARCHAR)');
        $app['db']->insert('contact', [ 'name' => $data['name'], 'email' => $data['email']]);
      }

    }
  }


  return $app['twig']->render('contact.html', array('form' => $form->createView()));

});

$app->get('/entries', function () use ($app) {

  return $app['twig']->render('entries.html', [ 'entries' => $app['db']->fetchAll('SELECT * FROM contact') ] );
});

$app->get('/{name}', function ($name) use ($app) {
  return $app['twig']->render($name.'.html', array());
});


$app->error(function (\Exception $e, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    $page = 404 == $code ? '404.html' : '500.html';

    return new Response($app['twig']->render($page, array('code' => $code)), $code);
});
