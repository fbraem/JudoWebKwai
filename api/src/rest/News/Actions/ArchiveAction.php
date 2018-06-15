<?php

namespace REST\News\Actions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Aura\Payload\Payload;

use Core\Responders\Responder;
use Core\Responders\SimpleJSONResponder;

class ArchiveAction implements \Core\ActionInterface
{
    public function __invoke(RequestInterface $request, Payload $payload) : ResponseInterface
    {
        $query = \Domain\News\NewsStoriesTable::getTableFromRegistry()->find();
        $query->select([
            'year' => $query->func()->year([
                'publish_date' => 'identifier'
            ]),
            'month' => $query->func()->month([
                'publish_date' => 'identifier'
            ]),
            'count' => $query->func()->count('id')
        ]);

        // Don't count news stories which are not yet published
        $query->where(function ($exp, $q) {
            return $exp->lt('publish_date', \Carbon\Carbon::now('UTC')->toDateTimeString());
        });
        // News stories must be enabled
        $query->where(['NewsStories.enabled' => true]);

        $query->group(['year', 'month']);
        $query->order(['year' => 'DESC', 'month' => 'DESC']);

        $archive = $query->all();

        return (
            new SimpleJSONResponder(
                new Responder(),
                $archive
            )
        )->respond();
    }
}
