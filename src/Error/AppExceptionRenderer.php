<?php
namespace Cors\Error;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cors\Routing\Middleware\CorsMiddleware;

function get_dynamic_parent()
{
    return Configure::read('Error.baseExceptionRenderer'); // return what you need
}
class_alias(get_dynamic_parent(), 'Cors\Error\BaseExceptionRenderer');

class AppExceptionRenderer extends BaseExceptionRenderer
{

    /**
     * Returns the current controller.
     *
     * @return \Cake\Controller\Controller
     */
    protected function _getController(): Controller
    {
        $controller = parent::_getController();
        $cors = new CorsMiddleware();
        $response = $cors->getResponseWithHeaders($controller->getRequest(), $controller->getResponse());
        $controller->setResponse($response);

        return $controller;
    }
}
