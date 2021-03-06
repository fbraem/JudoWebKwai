<?php

namespace REST\Teams\Actions;

use Interop\Container\ContainerInterface;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Domain\Team\TeamsTable;
use Domain\Team\TeamTransformer;

use Core\Responses\NotFoundResponse;
use Core\Responses\ResourceResponse;

use Cake\Datasource\Exception\RecordNotFoundException;

class TeamReadAction
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $parameters = $request->getAttribute('parameters');
        $contain = [
            'Season',
            'TeamType'
        ];
        if (isset($parameters['include'])) {
            foreach ($parameters['include'] as $include) {
                if ($include == 'members') {
                    $contain[] = 'Members';
                    $contain[] = 'Members.Person';
                }
            }
        }

        try {
            $response = (new ResourceResponse(
                TeamTransformer::createForItem(
                    TeamsTable::getTableFromRegistry()->get(
                        $args['id'],
                        [
                            'contain' => $contain
                        ]
                    )
                )
            ))($response);
        } catch (RecordNotFoundException $rnfe) {
            $response = (new NotFoundResponse(_("Team doesn't exist")))($response);
        }

        return $response;
    }
}
