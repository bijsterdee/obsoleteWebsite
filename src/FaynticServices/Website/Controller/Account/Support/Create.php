<?php

namespace FaynticServices\Website\Controller\Account\Support;

use FaynticServices\Website\Controller\Account\Support;

class Create extends Support
{
    public function handlePostDefault()
    {
        try {
            $ticket = $this->zendesk->tickets()->create(array(
                'requester_id' => $this->zendeskUserId,
                'submitter_id' => $this->zendeskUserId,
                'subject' => filter_input(INPUT_POST, 'subject'),
                'comment' => array(
                    'body' => filter_input(INPUT_POST, 'comment')
                ),
                'priority' => 'normal'
            ));
            $this->setStatus('Uw ticket is succesvol verzonden.', self::STATUS_SUCCESS);
            $this->redirect("/account/support/show/{$ticket->ticket->id}/");
        } catch (\Exception $exception) {
            $this->setStatus("Uw ticket kon helaas niet worden aangemaakt.", self::STATUS_DANGER);
            $this->redirect("/account/support/create/");
        }
    }

    public function handleDefault()
    {
        $this->setTemplate('Account/support/create.twig', [

        ]);
    }
}
