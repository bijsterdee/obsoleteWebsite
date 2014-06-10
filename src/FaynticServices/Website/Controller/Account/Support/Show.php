<?php

namespace FaynticServices\Website\Controller\Account\Support;

use FaynticServices\Website\Controller\Account\Support;
use FaynticServices\Website\Model\AccountSupportQuery;

class Show extends Support
{
    private $authors = array();

    public function handleAddComment()
    {
        $ticketId = filter_input(INPUT_POST, 'ticket_id');
        try {
            $comment = $this->zendesk->tickets()->update([
                'id' => $ticketId,
                'comment' => [
                    'author_id' => $this->zendeskUserId,
                    'body' => filter_input(INPUT_POST, 'message')
                ]
            ]);
            $this->setStatus('Bericht is succesvol toegevoegd', self::STATUS_SUCCESS);
        } catch (\Exception $exception) {
            $this->setStatus('Het bericht kon niet worden toegevoegd', self::STATUS_WARNING);
        } finally {
            $this->redirect("/account/support/show/{$ticketId}/");
        }
    }

    public function handleDefault($ticketId = null)
    {

        $ticket = $this->zendesk->tickets()->find(['id' => $ticketId])->ticket;
        if ($ticket->requester_id !== $this->zendeskUserId) {
            $this->setStatus('Deze ticket valt niet onder uw account');
            $this->redirect('/account/support/');
        }

        $comments = $this->zendesk->tickets()->comments()->findAll(['ticket_id' => $ticketId])->comments;

        $this->setTemplate('Account/support/show.twig', [
            'ticket' => $ticket,
            'comments' => $this->getAuthorFromId($comments)
        ]);
    }

    private function getAuthorFromId(array $entries = array())
    {
        foreach ($entries as &$entry) {
            if (!isset($this->authors[$entry->author_id])) {
                $accountSupport = AccountSupportQuery::create()->findOneByZendeskUserId($entry->author_id);

                if ($accountSupport) {
                    $this->authors[$entry->author_id] = $accountSupport->getAccount()->getAccountContact()->getName();
                } else {
                    $this->authors[$entry->author_id] = null;
                }
            }
            $entry->author_name = $this->authors[$entry->author_id];
        }
        return $entries;
    }
}
