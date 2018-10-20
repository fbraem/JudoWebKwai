<?php

namespace REST\Categories\Actions;

use Interop\Container\ContainerInterface;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

use Cake\Datasource\Exception\RecordNotFoundException;

use Domain\Category\CategoriesTable;
use Domain\Category\CategoryTransformer;
use REST\Categories\CategoryInputValidator;

class UpdateAction
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
            return $response->withStatus(404, _("Category doesn't exist"));
        }

        $data = $request->getParsedBody();

        $validator = new CategoryInputValidator();
        if (! $validator->validate($data)) {
            return $validator->unprocessableEntityResponse($response);
        }

        $attributes = \JmesPath\search('data.attributes', $data);

        if (array_key_exists('name', $attributes)) {
            $category->name = $attributes['name'];
        }
        if (array_key_exists('short_description', $attributes)) {
            $category->short_description = $attributes['short_description'];
        }
        if (array_key_exists('description', $attributes)) {
            $category->description = $attributes['description'];
        }
        if (array_key_exists('remark', $attributes)) {
            $category->remark = $attributes['remark'];
        }
        $category->user = $request->getAttribute('clubman.user');

        $categoriesTable->save($category);

        return (new \Core\ResourceResponse(
            CategoryTransformer::createForItem($category)
        ))($response);
    }
}
