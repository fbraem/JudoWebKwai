<?php

namespace REST\Categories\Actions;

use Interop\Container\ContainerInterface;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use League\Fractal\Manager;
use League\Fractal\Serializer\JsonApiSerializer;

use Cake\Datasource\Exception\RecordNotFoundException;

use Domain\Category\CategoriesTable;
use Domain\Category\CategoryTransformer;
use REST\Categories\CategoryValidator;

class UpdateCategoryAction
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $request, Response $response, $args)
    {
        $categoriesTable = CategoriesTable::getTableFromRegistry();
        try {
            $category = $categoriesTable->get($args['id']);
        } catch (RecordNotFoundException $rnfe) {
            return $response.withStatus(404, _("Category doesn't exist"));
        }

        $data = $request->getParsedBody();

        $validator = new CategoryValidator();
        if (! $validator->validate($data)) {
            return $response
                ->withStatus(422)
                ->withHeader('content-type', 'application/vnd.api+json')
                ->getBody()
                ->write($validator->toJSON());
        }

        $attributes = \JmesPath\search('data.attributes', $data);

        if (array_key_exists('name', $attributes)) {
            $category->name = $attributes['name'];
        }
        if (array_key_exists('description', $attributes)) {
            $category->description = $attributes['description'];
        }
        if (array_key_exists('remark', $attributes)) {
            $category->remark = $attributes['remark'];
        }
        $category->user = $request->getAttribute('clubman.user');

        $categoriesTable->save($category);

        $resource = CategoryTransformer::createForItem($category);

        $fractal = new Manager();
        $fractal->setSerializer(new JsonApiSerializer(/*$this->baseURL*/));
        $data = $fractal->createData($resource)->toJson();

        return $response
            ->withStatus(201)
            ->withHeader('content-type', 'application/vnd.api+json')
            ->getBody()
            ->write($data);
    }
}
