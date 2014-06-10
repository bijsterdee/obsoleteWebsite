<?php

namespace FaynticServices\Website\Controller;

use FaynticServices\Website\Controller;

class Dashboard extends Controller
{
    protected function handleDefault()
    {
        $this->setTemplate('Dashboard.twig');
//
//        /** @var FormFactory $formFactory */
//        $formFactory = $this->container->get('form')->getFormFactory();
//
//        $form = $formFactory->createBuilder()
//            ->add('first', 'text', array(
//                'constraints' => array(
//                    new NotBlank()
//                ),
//            ))
//            ->add('lastName', 'text')
//            ->add('gender', 'choice', array(
//                'choices' => array('m' => 'Male', 'f' => 'Female'),
//            ))
//            ->add('newsletter', 'checkbox', array(
//                'required' => false,
//            ))
//            ->getForm();
//
//        if (isset($_POST[$form->getName()])) {
//            $form->submit($_POST[$form->getName()]);
//
//            if ($form->isValid()) {
//                var_dump('VALID', $form->getData());
//                die;
//            }
//        }
//        $this->template['form'] = $form->createView();
    }
}
