<?php

namespace REST\Trainings\Actions;

use Interop\Container\ContainerInterface;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Domain\Training\CoachesTable;
use Domain\Training\CoachTransformer;

use Respect\Validation\Validator as v;

use Core\Validators\ValidationException;
use Core\Validators\InputValidator;
use Core\Validators\EntityExistValidator;

use Core\Responses\UnprocessableEntityResponse;
use Core\Responses\ResourceResponse;

class CoachUpdateAction
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();

        try {
            $coachesTable = CoachesTable::getTableFromRegistry();
            $coach = $coachesTable->get($args['id'], [
                'contain' => ['Member', 'Member.Person']
            ]);

            (new InputValidator(
                [
                    'data.attributes.diploma' => [ v::length(1, 255), true ],
                    'data.attributes.active' => [ v::boolType(), true ],
                ]
            ))->validate($data);

            $coachesTable = CoachesTable::getTableFromRegistry();

            $attributes = \JmesPath\search('data.attributes', $data);

            $coach->name = $attributes['name'];
            $coach->diploma = $attributes['diploma'];
            $coach->description = $attributes['description'];
            $coach->active = $attributes['active'] ?? true;
            $coach->remark = $attributes['remark'];

            $coach->user = $request->getAttribute('clubman.user');

            $coachesTable->save($coach);

            $response = (new ResourceResponse(
                CoachTransformer::createForItem($coach)
            ))($response)->withStatus(201);
        } catch (RecordNotFoundException $rnfe) {
            $response = (new NotFoundResponse(_("Coach doesn't exist")))($response);
        } catch (ValidationException $ve) {
            $response = (new UnprocessableEntityResponse(
                $ve->getErrors()
            ))($response);
        }

        return $response;
    }
}
